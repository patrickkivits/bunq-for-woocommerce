<?php
/**
 * The WooCommerce payment gateway.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Gateway;

use Exception;
use PatrickKivits\BunqForWooCommerce\Bunq\ApiClient;
use PatrickKivits\BunqForWooCommerce\Bunq\ApiException;
use PatrickKivits\BunqForWooCommerce\Bunq\OAuthClient;
use PatrickKivits\BunqForWooCommerce\Logger;
use PatrickKivits\BunqForWooCommerce\Orders\OrderLock;
use PatrickKivits\BunqForWooCommerce\Orders\PaymentMonitor;
use PatrickKivits\BunqForWooCommerce\Plugin;
use PatrickKivits\BunqForWooCommerce\Support\Money;
use PatrickKivits\BunqForWooCommerce\Support\Url;
use Throwable;
use WC_Order;
use WC_Payment_Gateway;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Lets customers pay through a bunq.me payment request on the merchant's bunq account.
 */
class Gateway extends WC_Payment_Gateway {

	/**
	 * The gateway id. The settings are stored under it (woocommerce_bunq_settings) and orders refer to it.
	 */
	const ID = 'bunq';

	const LAST_ERROR_TRANSIENT     = 'wc_bunq_gateway.last_error';
	const LAST_SUCCESS_TRANSIENT   = 'wc_bunq_gateway.last_success';
	const BANK_ACCOUNTS_TRANSIENT  = 'wc_bunq_gateway.bunq_get_bank_accounts';
	const CONTEXT_FAILED_TRANSIENT = 'wc_bunq_gateway.context_failed';
	const CURRENCY                 = 'EUR';
	const REFRESH_ACCOUNTS_ACTION  = 'bunq_refresh_accounts';
	const DISCONNECT_ACTION        = 'bunq_disconnect';

	/**
	 * The value of the "save" button that saves the settings and then starts the authorization at bunq.
	 */
	const CONNECT_ACTION = 'bunq_connect';

	/**
	 * The checkout field with the payment method the customer chose (direct gateway).
	 */
	const PAYMENT_METHOD_FIELD = 'wc_bunq_gateway_payment_method';

	/**
	 * The API keys and API contexts (which hold the private key and the session token). Kept apart from the settings:
	 * this option is not autoloaded and none of it is a settings field, so it is neither shown on the settings page
	 * nor in the WooCommerce REST API, and cannot be overwritten through either.
	 */
	const CREDENTIALS_OPTION = 'bunq_for_woocommerce_credentials';

	/**
	 * The settings keys whose values live in the credentials option (they were settings up to version 1.6).
	 */
	const CREDENTIAL_KEYS = array( 'api_key', 'api_context', 'test_api_key', 'test_api_context' );

	const META_PAYMENT_REQUEST_ID     = 'bunq_payment_request_id';
	const META_PAYMENT_REQUEST_STATUS = 'bunq_payment_request_status';
	const META_PAYMENT_ID             = 'bunq_payment_id';
	const META_MONETARY_ACCOUNT_ID    = 'bunq_monetary_account_id';
	const META_PAYMENT_REQUEST_URL    = 'bunq_payment_request_url';
	const META_PAYER_NAME             = 'bunq_payer_name';
	const META_PAYER_IBAN             = 'bunq_payer_iban';
	const META_PAYMENT_TYPE           = 'bunq_payment_type';

	/**
	 * The API key of the active mode.
	 *
	 * @var string
	 */
	public $api_key;

	/**
	 * Whether the bunq sandbox is used.
	 *
	 * @var bool
	 */
	public $testmode;

	/**
	 * The selected bank account setting.
	 *
	 * @var string
	 */
	public $monetary_account_bank_id;

	/**
	 * The API context of the active mode.
	 *
	 * @var string
	 */
	public $api_context;

	/**
	 * The OAuth client id of the active mode.
	 *
	 * @var string
	 */
	public $oauth_client_id;

	/**
	 * The OAuth client secret of the active mode.
	 *
	 * @var string
	 */
	public $oauth_client_secret;

	/**
	 * Whether the customer picks the payment method at checkout.
	 *
	 * @var bool
	 */
	public $direct_gateway;

	/**
	 * Whether iDEAL | Wero, card and Bancontact are offered as payment options of their own (MethodGateway) instead
	 * of this one.
	 *
	 * @var bool
	 */
	public $separate_gateways;

	/**
	 * The bunq API.
	 *
	 * @var ApiClient
	 */
	protected ApiClient $api;

	/**
	 * The bunq OAuth authorization.
	 *
	 * @var OAuthClient
	 */
	protected OAuthClient $oauth;

	/**
	 * Constructor. WooCommerce creates the gateway without arguments.
	 *
	 * @param ApiClient|null   $api   The bunq API; the plugin's by default.
	 * @param OAuthClient|null $oauth The bunq OAuth authorization.
	 */
	public function __construct( ?ApiClient $api = null, ?OAuthClient $oauth = null ) {
		$this->api   = $api ? $api : Plugin::instance()->api_client();
		$this->oauth = $oauth ? $oauth : new OAuthClient();

		$this->id                 = self::ID;
		$this->method_title       = 'bunq';
		$this->method_description = __( 'bunq payment gateway for WooCommerce', 'bunq-for-woocommerce' );
		$this->supports           = array(
			'products',
			'refunds',
		);

		// "Refresh bank accounts" button on the settings page: drop the cached list before the fields are built.
		if ( is_admin() && isset( $_GET[ self::REFRESH_ACCOUNTS_ACTION ] ) && current_user_can( 'manage_woocommerce' )
			&& wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), self::REFRESH_ACCOUNTS_ACTION ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_key().
		) {
			delete_transient( self::BANK_ACCOUNTS_TRANSIENT );
		}

		$this->init_form_fields();
		$this->init_settings();

		$this->title                    = $this->get_option( 'title' );
		$this->description              = $this->get_option( 'description' );
		$this->enabled                  = $this->get_option( 'enabled' );
		$this->testmode                 = 'yes' === $this->get_option( 'testmode' );
		$this->api_key                  = (string) $this->get_setting( $this->testmode ? 'test_api_key' : 'api_key' );
		$this->oauth_client_id          = $this->testmode ? $this->get_option( 'test_oauth_client_id' ) : $this->get_option( 'oauth_client_id' );
		$this->oauth_client_secret      = $this->testmode ? $this->get_option( 'test_oauth_client_secret' ) : $this->get_option( 'oauth_client_secret' );
		$this->api_context              = (string) $this->get_setting( 'api_context' );
		$this->monetary_account_bank_id = $this->get_option( 'monetary_account_bank_id' );
		$this->direct_gateway           = 'yes' === $this->get_option( 'direct_gateway' );
		$this->separate_gateways        = 'yes' === $this->get_option( 'separate_gateways' );
		$this->has_fields               = $this->direct_gateway;
		$this->icon                     = $this->get_icon_url();

		$this->register_hooks();

		foreach ( array( 'oauth_client_secret', 'test_oauth_client_secret' ) as $key ) {
			Logger::add_secret( $this->get_option( $key ), 'client secret' );
		}
		foreach ( array( 'api_key', 'test_api_key' ) as $key ) {
			Logger::add_secret( $this->get_setting( $key ), 'API key' );
		}
	}

	/**
	 * Hook the settings form and the bunq callback into WooCommerce.
	 */
	protected function register_hooks() {
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_' . CallbackHandler::API_REQUEST, array( $this, 'handle_callback' ) );
	}

	/**
	 * Whether a gateway id is one of the plugin's: this gateway or one of the separate payment method gateways.
	 * Orders paid with any of them are bunq.me payment requests.
	 *
	 * @param string $id Gateway id, for example the payment method of an order.
	 * @return bool
	 */
	public static function is_bunq_gateway_id( $id ) {
		return self::ID === $id || in_array( $id, self::method_gateway_ids(), true );
	}

	/**
	 * The ids of the separate payment method gateways (MethodGateway), such as "bunq_ideal".
	 *
	 * @return string[] Gateway ids by payment method id, in checkout order.
	 */
	public static function method_gateway_ids() {
		$ids = array();

		foreach ( PaymentMethods::SEPARATE as $method ) {
			$ids[ $method ] = self::ID . '_' . $method;
		}

		return $ids;
	}

	/**
	 * Whether this request is the merchant's return from bunq's authorization page: the settings page of the
	 * gateway with "code" and "state", requested by a user who may manage WooCommerce. Anything else must not reach
	 * the token exchange, which would save the API key of whoever's code it was.
	 *
	 * @return bool
	 */
	public static function is_oauth_return() {
		global $pagenow;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- The OAuth state is verified on exchange.
		return is_admin() && ! wp_doing_ajax() && 'admin.php' === $pagenow
			&& isset( $_GET['page'] ) && 'wc-settings' === $_GET['page']
			&& isset( $_GET['section'] ) && self::ID === $_GET['section']
			&& ! empty( $_GET['code'] ) && ! empty( $_GET['state'] )
			&& current_user_can( 'manage_woocommerce' );
		// phpcs:enable
	}

	/**
	 * Handle the redirect back from bunq after the OAuth authorization request and send the merchant back to the
	 * settings page. Ends the request.
	 */
	public function handle_oauth_callback() {
		wp_safe_redirect( $this->complete_oauth_authorization() );
		exit;
	}

	/**
	 * The settings page of the gateway. Also the redirect URI of the OAuth client: the merchant registers exactly this
	 * URL in the bunq app, so it must not depend on how the page was opened.
	 *
	 * @return string
	 */
	public static function settings_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . self::ID );
	}

	/**
	 * The mode name of the test mode setting.
	 *
	 * @param bool $testmode Whether test mode is enabled.
	 * @return string "sandbox" or "live".
	 */
	public static function mode( $testmode ) {
		return $testmode ? 'sandbox' : 'live';
	}

	/**
	 * Exchange the code bunq returned for an access token and create the bunq API context with it. The outcome is
	 * shown on the settings page (admin_options()).
	 *
	 * @return string The settings page URL to return to.
	 */
	public function complete_oauth_authorization() {
		// The redirect URI the authorization was requested with (get_connect_url()), as registered at bunq.
		$oauth_redirect_uri = self::settings_url();

		// Buffer any output (e.g. PHP notices) so the redirect header can still be sent.
		ob_start();

		delete_transient( self::LAST_ERROR_TRANSIENT );
		delete_transient( self::LAST_SUCCESS_TRANSIENT );

		try {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Verified through the OAuth state.
			$access_token = $this->oauth->get_access_token(
				$this->oauth_client_id,
				$this->oauth_client_secret,
				$oauth_redirect_uri,
				$this->testmode,
				sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) ),
				sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) )
			);
			// phpcs:enable

			delete_transient( self::BANK_ACCOUNTS_TRANSIENT );
			$this->set_credential( ( $this->testmode ? 'test_api_key' : 'api_key' ), $access_token );
			$this->refresh_api_context();

			set_transient( self::LAST_SUCCESS_TRANSIENT, __( 'bunq authorization completed. Select your bank account and save the settings.', 'bunq-for-woocommerce' ), 5 * MINUTE_IN_SECONDS );
		} catch ( Throwable $exception ) {
			Logger::log( $exception );
			set_transient( self::LAST_ERROR_TRANSIENT, Logger::format_error( $exception ), 5 * MINUTE_IN_SECONDS );
		}

		ob_end_clean();

		return $oauth_redirect_uri;
	}

	/**
	 * The payment methods the customer may pick, limited to the enabled ones and to those whose bunq limits fit the
	 * total.
	 *
	 * @param float|null $total Cart or order total; null allows every enabled method.
	 * @return array[] See PaymentMethods::all().
	 */
	public function get_allowed_payment_methods( $total = null ) {
		return PaymentMethods::allowed( $this->get_setting( 'enabled_payment_methods' ), $total );
	}

	/**
	 * The amount the customer is about to pay: the order total on the pay-for-order page, otherwise the cart total.
	 *
	 * @return float|null Null when there is no amount to filter on (empty cart, block editor).
	 */
	public function get_checkout_total() {
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
			$order = wc_get_order( absint( get_query_var( 'order-pay' ) ) );
			$total = $order ? (float) $order->get_total() : 0;
		} else {
			$total = function_exists( 'WC' ) && WC()->cart ? (float) WC()->cart->total : 0;
		}

		return $total > 0 ? $total : null;
	}

	/**
	 * The currency the customer is about to pay in: that of the order on the pay-for-order page, otherwise the store
	 * currency (which currency switchers filter for the cart).
	 *
	 * @return string
	 */
	public function get_checkout_currency() {
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
			$order = wc_get_order( absint( get_query_var( 'order-pay' ) ) );

			if ( $order ) {
				return (string) $order->get_currency();
			}
		}

		return function_exists( 'get_woocommerce_currency' ) ? (string) get_woocommerce_currency() : '';
	}

	/**
	 * The payment methods whose logos are shown at checkout: those the customer may pick with the direct gateway,
	 * otherwise every method bunq.me offers.
	 *
	 * @param float|null $total Cart or order total; null for every enabled method.
	 * @return array[] See PaymentMethods::icons().
	 */
	public function get_icons( $total = null ) {
		$methods = $this->direct_gateway ? $this->get_allowed_payment_methods( $total ) : PaymentMethods::all();

		return PaymentMethods::icons( PaymentMethods::in_checkout_order( $methods ) );
	}

	/**
	 * The URL of the first logo, for code that reads the gateway's icon property.
	 *
	 * @return string
	 */
	protected function get_icon_url() {
		$icons = $this->get_icons();

		return $icons ? $icons[0]['src'] : '';
	}

	/**
	 * The logos next to the gateway's title in the classic checkout.
	 *
	 * @return string
	 */
	public function get_icon() {
		$html = '';

		foreach ( $this->get_icons( $this->get_checkout_total() ) as $icon ) {
			$html .= '<img src="' . esc_url( $icon['src'] ) . '" alt="' . esc_attr( $icon['alt'] ) . '" class="bunq-payment-method-icon" />';
		}

		return apply_filters( 'woocommerce_gateway_icon', $html, $this->id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's hook.
	}

	/**
	 * The description and, for the direct gateway, the payment method select at checkout.
	 */
	public function payment_fields() {
		if ( $this->description ) {
			echo wpautop( wp_kses_post( $this->description ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized by wp_kses_post().
		}

		if ( $this->has_fields ) {
			woocommerce_form_field(
				self::PAYMENT_METHOD_FIELD,
				array(
					'type'     => 'select',
					'class'    => array( 'form-row-wide' ),
					'required' => true,
					'options'  => PaymentMethods::options( $this->get_allowed_payment_methods( $this->get_checkout_total() ) ),
				)
			);
		}
	}

	/**
	 * Reject a payment method bunq.me does not know.
	 *
	 * @return bool
	 */
	public function validate_fields() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- WooCommerce verified the checkout nonce; only compared against the known methods.
		if ( ! empty( $_POST[ self::PAYMENT_METHOD_FIELD ] ) && ! PaymentMethods::exists( $_POST[ self::PAYMENT_METHOD_FIELD ] ) ) {
			wc_add_notice( __( 'Payment method invalid', 'bunq-for-woocommerce' ), 'error' );

			return false;
		}

		return true;
	}

	/**
	 * The settings form, below the outcome of the last authorization. The connection status and the buttons are drawn
	 * by generate_title_html().
	 */
	public function admin_options() {
		$notices = array();

		$last_error = get_transient( self::LAST_ERROR_TRANSIENT );
		if ( $last_error ) {
			delete_transient( self::LAST_ERROR_TRANSIENT );
			$notices['error'] = $last_error;
		}

		$last_success = get_transient( self::LAST_SUCCESS_TRANSIENT );
		if ( $last_success ) {
			delete_transient( self::LAST_SUCCESS_TRANSIENT );
			$notices['success'] = $last_success;
		}

		SettingsPage::enqueue_assets();

		echo SettingsPage::notices_html( $notices ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while drawn.

		parent::admin_options();
	}

	/**
	 * Draw the "title" fields that have a view (SettingsFields::get()): the connection status rows, the connect button
	 * and the bunq account heading. The others are section headings.
	 *
	 * @param string $key  Field key.
	 * @param array  $data Field definition.
	 * @return string
	 */
	public function generate_title_html( $key, $data ) {
		$view = $data['view'] ?? '';

		if ( 'connection' === $view ) {
			$testmode = 'yes' === $this->get_option( 'testmode' );

			return SettingsPage::connection_html(
				array(
					'live'    => $this->get_connection( 'live' ),
					'sandbox' => $this->get_connection( 'sandbox' ),
				),
				self::mode( $testmode )
			);
		}

		if ( 'account' === $view ) {
			return SettingsPage::account_heading_html( parent::generate_title_html( $key, $data ) );
		}

		if ( 'connect' === $view ) {
			return SettingsPage::connect_html();
		}

		return parent::generate_title_html( $key, $data );
	}

	/**
	 * The state of a mode's connection with bunq, for the settings page.
	 *
	 * @param string $mode "live" or "sandbox".
	 * @return array {
	 *     @type bool        $connected     Whether there is an API context.
	 *     @type bool        $failed        Whether bunq recently rejected it, or the connection check failed.
	 *     @type string      $error         Why the connection check failed.
	 *     @type bool        $client_saved  Whether the OAuth client id and secret are saved.
	 *     @type string|null $account_name  The bunq user that granted access.
	 *     @type string      $disconnect_url Where the "Disconnect" link goes.
	 * }
	 */
	public function get_connection( $mode ) {
		$prefix      = 'sandbox' === $mode ? 'test_' : '';
		$credentials = $this->get_credentials();
		$api_context = (string) ( $credentials[ $prefix . 'api_context' ] ?? '' );
		$active      = self::mode( 'yes' === $this->get_option( 'testmode' ) ) === $mode;
		$problem     = '' !== $api_context && $active ? ConnectionHealth::problem( $this ) : null;

		return array(
			'connected'      => '' !== $api_context,
			'failed'         => '' !== $api_context && $active && ( $problem || get_transient( self::CONTEXT_FAILED_TRANSIENT ) ),
			'error'          => $problem ? $problem['error'] : '',
			'client_saved'   => '' !== (string) $this->get_option( $prefix . 'oauth_client_id' ) && '' !== (string) $this->get_option( $prefix . 'oauth_client_secret' ),
			'account_name'   => '' !== $api_context ? $this->api->get_account_name( $api_context ) : null,
			'disconnect_url' => wp_nonce_url( add_query_arg( self::DISCONNECT_ACTION, $mode, self::settings_url() ), self::DISCONNECT_ACTION . '_' . $mode ),
		);
	}

	/**
	 * Whether this request is the "Disconnect" link of the settings page.
	 *
	 * @return bool
	 */
	public static function is_disconnect_request() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Verified in handle_disconnect().
		return is_admin() && isset( $_GET[ self::DISCONNECT_ACTION ] )
			&& isset( $_GET['page'] ) && 'wc-settings' === $_GET['page']
			&& isset( $_GET['section'] ) && self::ID === $_GET['section'];
		// phpcs:enable
	}

	/**
	 * Handle the "Disconnect" link and send the merchant back to the settings page. Ends the request.
	 */
	public function handle_disconnect() {
		$mode = sanitize_key( wp_unslash( $_GET[ self::DISCONNECT_ACTION ] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified below.

		if ( ! in_array( $mode, array( 'live', 'sandbox' ), true ) || ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'bunq-for-woocommerce' ), 403 );
		}

		check_admin_referer( self::DISCONNECT_ACTION . '_' . $mode );

		$this->disconnect( $mode );

		wp_safe_redirect( self::settings_url() );
		exit;
	}

	/**
	 * Forget a mode's authorization (API key and API context). The OAuth client stays, so that connecting again
	 * takes one click.
	 *
	 * @param string $mode "live" or "sandbox".
	 */
	public function disconnect( $mode ) {
		$prefix = 'sandbox' === $mode ? 'test_' : '';

		$this->set_credential( $prefix . 'api_context', '' );
		$this->set_credential( $prefix . 'api_key', '' );
		delete_transient( self::BANK_ACCOUNTS_TRANSIENT );
		delete_transient( self::CONTEXT_FAILED_TRANSIENT );
		delete_transient( self::LAST_ERROR_TRANSIENT );
		ConnectionHealth::forget( $mode );

		Logger::log( 'Disconnected from bunq ' . ( 'sandbox' === $mode ? 'sandbox' : 'production' ), 'info' );

		set_transient(
			self::LAST_SUCCESS_TRANSIENT,
			'sandbox' === $mode
				? __( 'Disconnected from the bunq sandbox. To also revoke the access at bunq, remove the connection in the bunq app.', 'bunq-for-woocommerce' )
				: __( 'Disconnected from bunq. To also revoke the access at bunq, remove the connection in the bunq app.', 'bunq-for-woocommerce' ),
			5 * MINUTE_IN_SECONDS
		);
	}

	/**
	 * Where the "Connect to bunq" button leads once the settings are saved: the authorization page at bunq for the
	 * saved OAuth client of the active mode.
	 *
	 * @return string|null Null when the OAuth client is incomplete; the error is shown on the settings page.
	 */
	public function get_connect_url() {
		$testmode      = 'yes' === $this->get_option( 'testmode' );
		$prefix        = $testmode ? 'test_' : '';
		$client_id     = (string) $this->get_option( $prefix . 'oauth_client_id' );
		$client_secret = (string) $this->get_option( $prefix . 'oauth_client_secret' );

		if ( '' === $client_id || '' === $client_secret ) {
			set_transient( self::LAST_ERROR_TRANSIENT, __( 'Enter the Client ID and Client Secret of your bunq OAuth client, then click "Connect to bunq".', 'bunq-for-woocommerce' ), 5 * MINUTE_IN_SECONDS );

			return null;
		}

		return $this->oauth->get_authorization_url( $client_id, $client_secret, self::settings_url(), $testmode );
	}

	/**
	 * Send the merchant to bunq after "Connect to bunq" saved the settings (woocommerce_settings_saved). Ends the
	 * request, unless the OAuth client is incomplete.
	 */
	public function redirect_to_bunq() {
		$url = $this->get_connect_url();

		if ( ! $url ) {
			return;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );
		add_filter(
			'allowed_redirect_hosts',
			function ( $hosts ) use ( $host ) {
				$hosts[] = $host;

				return $hosts;
			}
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * (Re)create the bunq API context from the saved API key.
	 *
	 * @param bool $register_callback Whether to register the callback URL at bunq as well.
	 * @throws Throwable When the API context could not be created (e.g. bunq rejected the key).
	 */
	public function refresh_api_context( $register_callback = true ) {
		$testmode = 'yes' === $this->get_setting( 'testmode' );
		$api_key  = $testmode ? $this->get_setting( 'test_api_key' ) : $this->get_setting( 'api_key' );

		if ( ! $api_key ) {
			return;
		}

		// Creating the context talks to bunq (installation, device-server, session-server). Let errors bubble up so
		// the caller can show them to the admin instead of silently leaving the API context empty.
		$this->set_credential( ( $testmode ? 'test_api_context' : 'api_context' ), $this->api->create_api_context( $api_key, $testmode ) );
		delete_transient( self::CONTEXT_FAILED_TRANSIENT );
		ConnectionHealth::record( self::mode( $testmode ) );
		Logger::log( 'bunq API context created for ' . ( $testmode ? 'sandbox' : 'production' ), 'info' );

		if ( $register_callback ) {
			$this->register_callback();
		}
	}

	/**
	 * Register the callback URL at bunq for the selected bank account, so its payments are confirmed automatically.
	 * bunq keeps the callbacks per bank account, so this runs again when the merchant selects another one. Failures
	 * are logged: payments still work, only the automatic payment confirmation is affected.
	 */
	public function register_callback() {
		// A callback URL that only resolves locally (development sites) cannot be reached by bunq, so skip it there.
		$callback_url = CallbackHandler::url();

		if ( Url::is_local( $callback_url ) ) {
			Logger::log( 'Skipped registering the bunq callback URL ' . $callback_url . ' because bunq cannot reach it.', 'info' );

			return;
		}

		try {
			$this->call_bunq(
				function () {
					$this->api->create_notification_filters( $this->get_monetary_account_bank_id() );
				}
			);
			Logger::log( 'bunq callback URL registered: ' . $callback_url, 'info' );
		} catch ( Throwable $exception ) {
			Logger::log( $exception, 'warning' );
		}
	}

	/**
	 * Save the settings. Forgets the authorization of a mode whose OAuth client was removed, and goes on to bunq when
	 * the settings were saved with "Connect to bunq".
	 */
	public function process_admin_options() {
		$previous_account = $this->get_monetary_account_bank_id();

		parent::process_admin_options();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verified the settings nonce.
		if ( isset( $_POST['save'] ) && self::CONNECT_ACTION === $_POST['save'] ) {
			add_action( 'woocommerce_settings_saved', array( $this, 'redirect_to_bunq' ) );
		}

		// Up to version 1.7 only the active mode's fields were in the form, so the other mode's keys can be absent in
		// the saved settings: a missing key counts as "not set".
		if ( empty( $this->settings['test_oauth_client_id'] ) || empty( $this->settings['test_oauth_client_secret'] ) ) {
			$this->set_credential( 'test_api_context', '' );
			$this->set_credential( 'test_api_key', '' );
			delete_transient( self::BANK_ACCOUNTS_TRANSIENT );
			ConnectionHealth::forget( 'sandbox' );
		}

		if ( empty( $this->settings['oauth_client_id'] ) || empty( $this->settings['oauth_client_secret'] ) ) {
			$this->set_credential( 'api_context', '' );
			$this->set_credential( 'api_key', '' );
			delete_transient( self::BANK_ACCOUNTS_TRANSIENT );
			ConnectionHealth::forget( 'live' );
		}

		// The callback was registered for the bank account selected when connecting, often before one was selected.
		if ( $this->get_monetary_account_bank_id() !== $previous_account && $this->get_setting( 'api_context' ) ) {
			$this->register_callback();
		}
	}

	/**
	 * Read a setting as saved, mapping "api_context", "oauth_client_id" and "oauth_client_secret" to the active
	 * mode's setting.
	 *
	 * @param string $key Setting.
	 * @return mixed Null when it is not set, or when the settings were never saved.
	 */
	public function get_setting( $key ) {
		$this->init_settings();

		if ( ! isset( $this->settings['testmode'] ) ) {
			return null;
		}

		$testmode = 'yes' === $this->settings['testmode'];

		if ( in_array( $key, array( 'api_context', 'oauth_client_id', 'oauth_client_secret' ), true ) && $testmode ) {
			$key = 'test_' . $key;
		}

		if ( in_array( $key, self::CREDENTIAL_KEYS, true ) ) {
			$credentials = $this->get_credentials();

			return $credentials[ $key ] ?? null;
		}

		return $this->settings[ $key ] ?? null;
	}

	/**
	 * Load the settings, and move API keys and API contexts that an earlier version stored among them to the
	 * credentials option.
	 */
	public function init_settings() {
		parent::init_settings();

		$found = array_intersect_key( $this->settings, array_flip( self::CREDENTIAL_KEYS ) );

		if ( ! $found ) {
			return;
		}

		update_option( self::CREDENTIALS_OPTION, array_merge( $this->get_credentials(), $found ), false );
		$this->settings = array_diff_key( $this->settings, $found );
		update_option( $this->get_option_key(), $this->settings );
	}

	/**
	 * The stored API keys and API contexts.
	 *
	 * @return array Values by CREDENTIAL_KEYS key.
	 */
	private function get_credentials() {
		$credentials = get_option( self::CREDENTIALS_OPTION, array() );

		return is_array( $credentials ) ? $credentials : array();
	}

	/**
	 * Store an API key or API context.
	 *
	 * @param string $key   One of CREDENTIAL_KEYS.
	 * @param string $value The value.
	 */
	private function set_credential( $key, $value ) {
		$credentials         = $this->get_credentials();
		$credentials[ $key ] = $value;
		update_option( self::CREDENTIALS_OPTION, $credentials, false );

		if ( 'api_key' === $key || 'test_api_key' === $key ) {
			Logger::add_secret( $value, 'API key' );
		}
	}

	/**
	 * Render a secret (the OAuth client secret) without its value, so a saved secret does not end up in the page.
	 *
	 * @param string $key  Field key.
	 * @param array  $data Field definition.
	 * @return string
	 */
	public function generate_password_html( $key, $data ) {
		$saved = $this->settings[ $key ] ?? '';

		if ( '' !== (string) $saved ) {
			$data['placeholder'] = '••••••••••••••••';
			$data['description'] = __( 'Saved. Leave empty to keep it, or paste a new secret to replace it.', 'bunq-for-woocommerce' );
		}

		$this->settings[ $key ] = '';
		$html                   = parent::generate_password_html( $key, $data );
		$this->settings[ $key ] = $saved;

		return $html;
	}

	/**
	 * Keep the saved secret when the field is left empty: it is rendered empty (generate_password_html()).
	 *
	 * @param string      $key   Field key.
	 * @param string|null $value Posted value.
	 * @return string
	 */
	public function validate_password_field( $key, $value ) {
		$value = parent::validate_password_field( $key, $value );

		return '' === $value ? (string) $this->get_option( $key ) : $value;
	}

	/**
	 * Leave the secrets out of the gateway in the WooCommerce REST API (woocommerce_rest_prepare_payment_gateway).
	 *
	 * @param \WP_REST_Response   $response The response.
	 * @param \WC_Payment_Gateway $gateway  The gateway.
	 * @return \WP_REST_Response
	 */
	public static function hide_secrets_from_rest( $response, $gateway ) {
		$data = $response->get_data();

		if ( ! $gateway instanceof self || ! is_array( $data ) || empty( $data['settings'] ) ) {
			return $response;
		}

		foreach ( $data['settings'] as $key => $setting ) {
			if ( isset( $setting['type'] ) && 'password' === $setting['type'] ) {
				$data['settings'][ $key ]['value'] = '';
			}
		}

		$response->set_data( $data );

		return $response;
	}

	/**
	 * Keep the saved secrets when a REST API update sends them empty, as the API hands them out
	 * (woocommerce_gateway_bunq_settings_values).
	 *
	 * @param array               $settings The settings to save.
	 * @param \WC_Payment_Gateway $gateway  The gateway.
	 * @return array
	 */
	public static function keep_secrets_on_rest_update( $settings, $gateway ) {
		if ( ! $gateway instanceof self || ! is_array( $settings ) ) {
			return $settings;
		}

		$saved = get_option( $gateway->get_option_key(), array() );

		foreach ( $gateway->get_form_fields() as $key => $field ) {
			if ( 'password' === $field['type'] && isset( $settings[ $key ] ) && '' === $settings[ $key ] && ! empty( $saved[ $key ] ) ) {
				$settings[ $key ] = $saved[ $key ];
			}
		}

		return $settings;
	}

	/**
	 * The selected bunq monetary account.
	 *
	 * @return int|null Null for the account's default.
	 */
	public function get_monetary_account_bank_id() {
		$monetary_account_bank_id = intval( $this->get_setting( 'monetary_account_bank_id' ) );

		return $monetary_account_bank_id > 0 ? $monetary_account_bank_id : null;
	}

	/**
	 * The bunq monetary account of an order's payment request: the one it was created on, which stays right when the
	 * merchant selects another bank account later.
	 *
	 * @param WC_Order $order The order.
	 * @return int|null Null for the account's default.
	 */
	public function get_order_monetary_account_id( $order ) {
		$monetary_account_id = intval( $order->get_meta( self::META_MONETARY_ACCOUNT_ID ) );

		return $monetary_account_id > 0 ? $monetary_account_id : $this->get_monetary_account_bank_id();
	}

	/**
	 * Define the settings form. The bank accounts are only looked up on the admin side.
	 */
	public function init_form_fields() {
		// The bank account list is only needed to render the settings page, and fetching it costs a bunq API call.
		// Outside admin the select simply has no options; the field definitions themselves are always set so that
		// get_option() can fall back to each field's default on the frontend and under WP-CLI.
		$bank_accounts = array();

		if ( is_admin() ) {
			$bank_accounts = get_transient( self::BANK_ACCOUNTS_TRANSIENT );

			if ( ! $bank_accounts ) {
				$bank_accounts = $this->get_bank_accounts();

				// A real account list is kept for an hour; an error or "not set up yet" result only briefly, so it does
				// not stick around after the merchant fixed the cause.
				set_transient( self::BANK_ACCOUNTS_TRANSIENT, $bank_accounts, count( $bank_accounts ) > 1 ? HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS );
			}
		}

		// The test mode setting decides about the others; it falls back to the default of its field.
		$this->form_fields = SettingsFields::base( $bank_accounts );
		$connected         = (bool) $this->get_setting( 'api_context' );

		$this->form_fields = SettingsFields::get( $bank_accounts, $connected );

		if ( $connected ) {
			$refresh_url = wp_nonce_url( add_query_arg( self::REFRESH_ACCOUNTS_ACTION, '1', self::settings_url() ), self::REFRESH_ACCOUNTS_ACTION );

			$this->form_fields['monetary_account_bank_id']['description'] .= ' <a href="' . esc_url( $refresh_url ) . '">' . esc_html__( 'Refresh bank accounts', 'bunq-for-woocommerce' ) . '</a>';
		}
	}

	/**
	 * The bank accounts of the connected bunq user, as options for the settings select.
	 *
	 * @return array See ApiClient::get_bank_accounts().
	 */
	private function get_bank_accounts() {
		$rejected = array( '' => __( 'bunq rejected the saved connection. Connect again.', 'bunq-for-woocommerce' ) );

		if ( ! $this->get_setting( 'api_context' ) ) {
			return $this->api->get_bank_accounts( false );
		}

		if ( ! $this->load_api_context() ) {
			return $rejected;
		}

		try {
			return $this->call_bunq(
				fn() => $this->api->get_bank_accounts( true )
			);
		} catch ( Throwable $exception ) {
			Logger::log( $exception );

			return $rejected;
		}
	}

	/**
	 * Only offer the gateway at checkout when the bunq authorization has been completed; without an API context no
	 * payment request can be created. bunq.me payment requests are in euros only, so it is hidden for other
	 * currencies as well.
	 *
	 * @return bool
	 */
	public function is_available() {
		return $this->is_available_for_total( $this->get_checkout_total() );
	}

	/**
	 * Whether the gateway is offered for an amount (see is_available()). The block checkout asks for any amount and
	 * checks the cart total itself, as the total can change on the checkout page.
	 *
	 * @param float|null $total Cart or order total; null for any amount.
	 * @return bool
	 */
	public function is_available_for_total( $total ) {
		if ( ! parent::is_available() || ! $this->is_offered( $total ) ) {
			return false;
		}

		if ( self::CURRENCY !== $this->get_checkout_currency() ) {
			return false;
		}

		return (bool) $this->get_setting( 'api_context' ) && ! get_transient( self::CONTEXT_FAILED_TRANSIENT );
	}

	/**
	 * Whether this gateway is offered at checkout as far as the settings go: not when the payment methods are
	 * offered as gateways of their own.
	 *
	 * @param float|null $total Cart or order total; null for any amount. Not used: bunq.me offers the methods that fit.
	 * @return bool
	 */
	protected function is_offered( $total ) {
		return ! $this->separate_gateways;
	}

	/**
	 * The amounts the gateway accepts, for the block checkout to check the cart total against.
	 *
	 * @return array|null Keys: min, max; null for any amount.
	 */
	public function get_amount_limits() {
		return null;
	}

	/**
	 * The payment method bunq.me should open directly: the one the customer picked with the direct gateway.
	 *
	 * @return string|null bunq.me's name for it; null to let the customer choose on bunq.me.
	 */
	protected function get_requested_bunqme_payment_method() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verified the checkout nonce.
		if ( empty( $_POST[ self::PAYMENT_METHOD_FIELD ] ) ) {
			return null;
		}

		return PaymentMethods::bunqme_name( sanitize_text_field( wp_unslash( $_POST[ self::PAYMENT_METHOD_FIELD ] ) ) );
		// phpcs:enable
	}

	/**
	 * Create a bunq.me payment request for the order and send the customer there.
	 *
	 * @param int $order_id Order id.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		try {
			// The block checkout does not call validate_fields(); bunqme_name() only knows the valid methods.
			$bunqme_payment_method = $this->get_requested_bunqme_payment_method();

			// A second attempt for the same order (the customer came back from bunq.me) reuses the open payment request,
			// or cancels it: a payment through a forgotten request would not be matched to the order any more.
			$link = $this->get_payment_link( $order );

			// The customer paid the earlier request already.
			if ( null === $link['url'] ) {
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			}

			// Direct gateway: bunq.me opens the chosen method straight away when it is passed as ?paymentMethod= (it
			// used to be a path segment, which bunq.me now ignores).
			$redirect = $bunqme_payment_method
				? add_query_arg( 'paymentMethod', $bunqme_payment_method, $link['url'] )
				: $link['url'];

			return array(
				'result'   => 'success',
				'redirect' => $redirect,
			);
		} catch ( Throwable $exception ) {
			// Log the real cause for the merchant; the customer only gets a generic message.
			Logger::log( $exception );
			wc_add_notice( __( 'The payment could not be started with bunq. Please try again or choose another payment method.', 'bunq-for-woocommerce' ), 'error' );

			return array( 'result' => 'failure' );
		}
	}

	/**
	 * Create a bunq.me payment request for the order and remember it on the order: the payment request id for the
	 * callback and the checks, and a background check as fallback for a callback that never arrives.
	 *
	 * @param WC_Order $order The order.
	 * @return array The share URL ("url") and the id ("id") of the request.
	 * @throws Throwable When bunq could not be reached or did not return a payment request.
	 */
	private function create_order_payment_request( $order ) {
		$payment_request = $this->call_bunq(
			fn() => $this->api->create_payment_request(
				$order->get_total(),
				$order->get_currency(),
				'#' . $order->get_order_number(),
				$this->get_return_url( $order ),
				$this->get_monetary_account_bank_id()
			)
		);

		if ( ! $payment_request || empty( $payment_request['id'] ) || empty( $payment_request['url'] ) ) {
			throw new Exception( 'bunq did not return a payment request for order #' . $order->get_order_number() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Only logged.
		}

		$order->add_order_note(
			sprintf(
				/* translators: %s: bunq payment request id */
				__( 'bunq payment request %s created', 'bunq-for-woocommerce' ),
				$payment_request['id']
			)
		);
		$order->update_meta_data( self::META_PAYMENT_REQUEST_ID, $payment_request['id'] );
		$order->update_meta_data( self::META_PAYMENT_REQUEST_URL, $payment_request['url'] );
		// The account the request lives on, to look it up there after the merchant selects another bank account.
		$monetary_account_id = ! empty( $payment_request['monetary_account_id'] ) ? (int) $payment_request['monetary_account_id'] : $this->get_monetary_account_bank_id();
		if ( $monetary_account_id ) {
			$order->update_meta_data( self::META_MONETARY_ACCOUNT_ID, $monetary_account_id );
		} else {
			$order->delete_meta_data( self::META_MONETARY_ACCOUNT_ID );
		}
		// The final state of an earlier payment request of the order does not apply to this one.
		$order->delete_meta_data( self::META_PAYMENT_REQUEST_STATUS );
		$order->save();

		// Fallback for a callback that never arrives: re-check the payment request in the background.
		PaymentMonitor::schedule_check( $order->get_id() );

		return $payment_request;
	}

	/**
	 * A bunq.me link the customer can pay the order with: at checkout, and for a payment link the merchant sends (see
	 * Orders\PaymentLink). The order's payment request is reused while it can still be paid and asks for the order
	 * total; otherwise it is cancelled and a new one is created.
	 *
	 * @param WC_Order $order An order that needs payment.
	 * @return array Keys: "url" (the bunq.me link; null when a payment already came in) and "outcome": "created",
	 *               "reused", or the outcome of check_payment_status() when a payment already came in.
	 * @throws Throwable When bunq could not be reached or did not return a payment request.
	 */
	public function get_payment_link( $order ) {
		$payment_request_id = intval( $order->get_meta( self::META_PAYMENT_REQUEST_ID ) );

		// bunq reported the earlier request as cancelled or expired: it can no longer be paid.
		if ( $payment_request_id && ! $order->get_meta( self::META_PAYMENT_REQUEST_STATUS ) ) {
			$monetary_account_id = $this->get_order_monetary_account_id( $order );
			$payment_request     = $this->call_bunq(
				fn() => $this->api->get_payment_request( $payment_request_id, $monetary_account_id )
			);

			// The customer paid the earlier request: record that instead of asking for the money again.
			if ( ! empty( $payment_request['result_inquiries'] ) ) {
				return array(
					'url'     => null,
					'outcome' => $this->check_payment_status( $order ),
				);
			}

			$status = (string) ( $payment_request['status'] ?? '' );

			// Still open (bunq reports open requests as ACTIVE or WAITING_FOR_PAYMENT).
			if ( ! in_array( $status, array( 'CANCELLED', 'EXPIRED' ), true ) ) {
				$amount = $payment_request['bunqme_tab_entry']['amount_inquired'] ?? null;

				if ( ! empty( $payment_request['bunqme_tab_share_url'] ) && isset( $amount['value'], $amount['currency'] )
					&& $amount['currency'] === $order->get_currency() && Money::amounts_match( $amount['value'], $order->get_total() )
				) {
					return array(
						'url'     => (string) $payment_request['bunqme_tab_share_url'],
						'outcome' => 'reused',
					);
				}

				// The order total changed since: the old link must not be paid any more.
				$this->cancel_payment_request( $payment_request_id, $monetary_account_id );
				$order->add_order_note(
					sprintf(
						/* translators: %s: bunq payment request id */
						__( 'bunq payment request %s cancelled', 'bunq-for-woocommerce' ),
						$payment_request_id
					)
				);
			}
		}

		$payment_request = $this->create_order_payment_request( $order );

		return array(
			'url'     => $payment_request['url'],
			'outcome' => 'created',
		);
	}

	/**
	 * Refund (part of) the order by sending the money back to the account the bunq payment came from.
	 *
	 * @param int        $order_id Order id.
	 * @param float|null $amount   Amount to refund.
	 * @param string     $reason   Refund reason.
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order      = wc_get_order( $order_id );
		$payment_id = $order ? intval( $order->get_meta( self::META_PAYMENT_ID ) ) : 0;

		if ( ! $payment_id ) {
			return new WP_Error( 'bunq_refund', __( 'This order has no bunq payment that can be refunded.', 'bunq-for-woocommerce' ) );
		}

		if ( ! $amount || (float) $amount <= 0 ) {
			return new WP_Error( 'bunq_refund', __( 'The refund amount must be greater than zero.', 'bunq-for-woocommerce' ) );
		}

		try {
			$monetary_account_id = $this->get_order_monetary_account_id( $order );

			$payment = $this->call_bunq(
				fn() => $this->api->get_payment( $payment_id, $monetary_account_id )
			);
			$iban    = $payment['counterparty_alias']['iban'] ?? null;

			if ( ! $iban ) {
				return new WP_Error( 'bunq_refund', __( 'The bunq payment for this order has no IBAN to refund to (for example a card payment). Refund it from the bunq app instead.', 'bunq-for-woocommerce' ) );
			}

			$description = sprintf(
				/* translators: 1: order number, 2: shop name */
				__( 'Refund order %1$s %2$s', 'bunq-for-woocommerce' ),
				$order->get_order_number(),
				get_bloginfo( 'name' )
			);
			if ( $reason ) {
				$description .= ': ' . $reason;
			}

			$account_holder = $payment['counterparty_alias']['display_name'] ?? '';
			if ( ! $account_holder ) {
				$account_holder = $order->get_formatted_billing_full_name();
			}

			$refund_payment_id = $this->call_bunq(
				fn() => $this->api->create_refund( (float) $amount, $order->get_currency(), $iban, $account_holder, $description, $monetary_account_id )
			);

			$order->add_order_note(
				sprintf(
					/* translators: 1: refunded amount, 2: bunq draft payment id */
					__( 'bunq refund of %1$s created as draft payment %2$s. Approve it in the bunq app to send the money.', 'bunq-for-woocommerce' ),
					wc_price( $amount, array( 'currency' => $order->get_currency() ) ),
					$refund_payment_id
				)
			);
			Logger::log( 'bunq draft payment ' . $refund_payment_id . ' of ' . $order->get_currency() . ' ' . $amount . ' created for the refund of order #' . $order->get_order_number() . ', waiting for approval in the bunq app', 'info' );

			return true;
		} catch ( Throwable $exception ) {
			Logger::log( $exception );

			return new WP_Error(
				'bunq_refund',
				sprintf(
					/* translators: %s: error message from bunq */
					__( 'bunq refund failed: %s', 'bunq-for-woocommerce' ),
					Logger::format_error( $exception )
				)
			);
		}
	}

	/**
	 * The callback from bunq (woocommerce_api_wc_bunq_gateway). Ends the request.
	 */
	public function handle_callback() {
		$handler = new CallbackHandler( $this );
		$handler->handle( (string) file_get_contents( 'php://input' ) );

		exit;
	}

	/**
	 * Fetch the bunq payment request of an order and bring the order in line with it: complete the order when a
	 * matching payment came in, cancel it when bunq cancelled or expired the request.
	 *
	 * @param WC_Order $order The order.
	 * @return string One of "paid", "on-hold" (a payment with another amount came in), "cancelled", "pending" or
	 *                "settled" (the order did not need payment).
	 * @throws Throwable When bunq could not be reached.
	 */
	public function check_payment_status( $order ) {
		if ( ! $order->needs_payment() ) {
			return 'settled';
		}

		$payment_request_id = intval( $order->get_meta( self::META_PAYMENT_REQUEST_ID ) );

		if ( ! $payment_request_id ) {
			throw new Exception( 'Order #' . $order->get_order_number() . ' has no bunq payment request' ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Only logged.
		}

		// The callback, the customer's return and the scheduled check can run at the same time; let one of them do
		// the work.
		if ( ! OrderLock::acquire( $order->get_id(), 30 ) ) {
			// The other check may have asked bunq just before this payment came in.
			PaymentMonitor::schedule_follow_up_check( $order->get_id(), 30 );

			return 'pending';
		}

		try {
			// Another check may have finished after this request loaded the order; recording its payment again
			// would duplicate the payment meta data, the order note and the payment completion.
			if ( ! OrderLock::stored_order_needs_payment( $order ) ) {
				return 'settled';
			}

			$monetary_account_id = $this->get_order_monetary_account_id( $order );
			$payment_request     = $this->call_bunq(
				fn() => $this->api->get_payment_request( $payment_request_id, $monetary_account_id )
			);

			return $this->apply_payment_request( $order, $payment_request_id, $payment_request );
		} finally {
			OrderLock::release( $order->get_id() );
		}
	}

	/**
	 * Bring the order in line with its payment request as bunq reported it.
	 *
	 * @param WC_Order $order              The order.
	 * @param int      $payment_request_id The id of the payment request.
	 * @param array    $payment_request    The bunq.me payment request (ApiClient::get_payment_request()).
	 * @return string See check_payment_status().
	 */
	private function apply_payment_request( $order, $payment_request_id, array $payment_request ) {
		$unmatched_payment = null;

		foreach ( (array) ( $payment_request['result_inquiries'] ?? array() ) as $result_inquiry ) {
			$payment = $result_inquiry['payment'] ?? null;

			if ( ! isset( $payment['id'], $payment['amount']['value'], $payment['amount']['currency'] ) ) {
				continue;
			}

			// bunq returns the amount as a string; compare it as a decimal so the number of decimals in the shop
			// does not matter.
			$paid_amount = $payment['amount'];

			if ( $paid_amount['currency'] !== $order->get_currency() || ! Money::amounts_match( $paid_amount['value'], $order->get_total() ) ) {
				Logger::log( 'bunqme-tab ' . $payment_request_id . ': payment ' . $payment['id'] . ' of ' . $paid_amount['currency'] . ' ' . $paid_amount['value'] . ' does not match order #' . $order->get_order_number() . ' total ' . $order->get_currency() . ' ' . $order->get_total(), 'warning' );
				$unmatched_payment = $payment;
				continue;
			}

			Logger::log( 'bunqme-tab ' . $payment_request_id . ': payment ' . $payment['id'] . ' received for order #' . $order->get_order_number(), 'info' );

			$order->add_order_note(
				sprintf(
					/* translators: %s: bunq payment id */
					__( 'bunq payment %s received', 'bunq-for-woocommerce' ),
					$payment['id']
				)
			);
			$order->update_meta_data( self::META_PAYMENT_ID, $payment['id'] );
			// The account the money landed on, so a refund still works after the merchant changes the setting.
			if ( isset( $payment['monetary_account_id'] ) ) {
				$order->update_meta_data( self::META_MONETARY_ACCOUNT_ID, $payment['monetary_account_id'] );
			}
			self::record_payment_details( $order, $payment, $payment_request );
			$order->save();
			$order->payment_complete( (string) $payment['id'] );

			return 'paid';
		}

		// Money arrived but not the right amount: never cancel such an order, let the merchant decide.
		if ( $unmatched_payment ) {
			$unmatched_amount = $unmatched_payment['amount'];
			$order->update_meta_data( self::META_PAYMENT_ID, $unmatched_payment['id'] );
			if ( isset( $unmatched_payment['monetary_account_id'] ) ) {
				$order->update_meta_data( self::META_MONETARY_ACCOUNT_ID, $unmatched_payment['monetary_account_id'] );
			}
			self::record_payment_details( $order, $unmatched_payment, $payment_request );
			$order->save();
			$order->update_status(
				'on-hold',
				sprintf(
					/* translators: 1: bunq payment id, 2: amount received, 3: order total */
					__( 'bunq payment %1$s of %2$s received, but the order total is %3$s. Check the payment before processing the order.', 'bunq-for-woocommerce' ),
					$unmatched_payment['id'],
					$unmatched_amount['currency'] . ' ' . $unmatched_amount['value'],
					$order->get_currency() . ' ' . $order->get_total()
				)
			);

			return 'on-hold';
		}

		$status = (string) ( $payment_request['status'] ?? '' );

		if ( in_array( $status, array( 'CANCELLED', 'EXPIRED' ), true ) ) {
			Logger::log( 'bunqme-tab ' . $payment_request_id . ' is ' . $status . ', cancelling order #' . $order->get_order_number(), 'info' );

			// Remember bunq's final state so the order-cancelled hook does not try to cancel the request again.
			$order->update_meta_data( self::META_PAYMENT_REQUEST_STATUS, $status );
			$order->save();
			$order->update_status(
				'cancelled',
				sprintf(
					/* translators: 1: bunq payment request id, 2: status reported by bunq */
					__( 'bunq payment request %1$s is %2$s', 'bunq-for-woocommerce' ),
					$payment_request_id,
					strtolower( $status )
				)
			);

			return 'cancelled';
		}

		return 'pending';
	}

	/**
	 * Remember who paid the order and how, for the bunq box on the order screen (PaymentDetails). The caller saves
	 * the order.
	 *
	 * @param WC_Order $order           The order.
	 * @param array    $payment         The bunq payment (ApiClient::get_payment()).
	 * @param array    $payment_request The bunq.me payment request the payment was made through.
	 */
	public static function record_payment_details( $order, array $payment, array $payment_request = array() ) {
		$counterparty = isset( $payment['counterparty_alias'] ) && is_array( $payment['counterparty_alias'] ) ? $payment['counterparty_alias'] : array();

		$order->update_meta_data( self::META_PAYER_NAME, sanitize_text_field( (string) ( $counterparty['display_name'] ?? '' ) ) );
		$order->update_meta_data( self::META_PAYER_IBAN, sanitize_text_field( (string) ( $counterparty['iban'] ?? '' ) ) );
		// "UNKNOWN" rather than empty, so PaymentDetails does not ask bunq again for an order whose details are known.
		$order->update_meta_data( self::META_PAYMENT_TYPE, ! empty( $payment['type'] ) ? sanitize_text_field( (string) $payment['type'] ) : 'UNKNOWN' );

		if ( ! empty( $payment_request['bunqme_tab_share_url'] ) ) {
			$order->update_meta_data( self::META_PAYMENT_REQUEST_URL, esc_url_raw( (string) $payment_request['bunqme_tab_share_url'] ) );
		}
	}

	/**
	 * Fetch the payer and payment method of an order paid before the plugin stored them, and store them.
	 *
	 * @param WC_Order $order The order; it has a bunq payment.
	 * @return bool Whether bunq returned the payment.
	 * @throws Throwable When bunq could not be reached.
	 */
	public function fetch_payment_details( $order ) {
		$payment_request_id  = intval( $order->get_meta( self::META_PAYMENT_REQUEST_ID ) );
		$payment_id          = intval( $order->get_meta( self::META_PAYMENT_ID ) );
		$monetary_account_id = $this->get_order_monetary_account_id( $order );

		if ( ! $payment_request_id || ! $payment_id ) {
			return false;
		}

		// The payment request holds the payment as well as its share URL, so one call is enough.
		$payment_request = $this->call_bunq(
			fn() => $this->api->get_payment_request( $payment_request_id, $monetary_account_id )
		);

		foreach ( (array) ( $payment_request['result_inquiries'] ?? array() ) as $result_inquiry ) {
			if ( isset( $result_inquiry['payment']['id'] ) && intval( $result_inquiry['payment']['id'] ) === $payment_id ) {
				self::record_payment_details( $order, $result_inquiry['payment'], $payment_request );
				$order->save();

				return true;
			}
		}

		return false;
	}

	/**
	 * Cancel a bunq payment request at bunq so it can no longer be paid.
	 *
	 * @param int      $payment_request_id  The payment request.
	 * @param int|null $monetary_account_id Its account (get_order_monetary_account_id()); the selected one by default.
	 * @throws Throwable When bunq could not be reached.
	 */
	public function cancel_payment_request( $payment_request_id, $monetary_account_id = null ) {
		$monetary_account_id = $monetary_account_id ? $monetary_account_id : $this->get_monetary_account_bank_id();

		$this->call_bunq(
			function () use ( $payment_request_id, $monetary_account_id ) {
				$this->api->cancel_payment_request( $payment_request_id, $monetary_account_id );
			}
		);
	}

	/**
	 * Before the payment request of a cancelled order is cancelled at bunq: record a payment that came in anyway, for
	 * example just before WooCommerce cancelled the unpaid order, so the merchant sees it and can refund it.
	 *
	 * @param WC_Order $order The cancelled order.
	 * @return bool Whether a payment came in.
	 * @throws Throwable When bunq could not be reached.
	 */
	public function record_late_payment( $order ) {
		$payment_request_id  = intval( $order->get_meta( self::META_PAYMENT_REQUEST_ID ) );
		$monetary_account_id = $this->get_order_monetary_account_id( $order );

		$payment_request = $this->call_bunq(
			fn() => $this->api->get_payment_request( $payment_request_id, $monetary_account_id )
		);

		foreach ( (array) ( $payment_request['result_inquiries'] ?? array() ) as $result_inquiry ) {
			$payment = $result_inquiry['payment'] ?? null;

			if ( ! isset( $payment['id'], $payment['amount']['value'], $payment['amount']['currency'] ) ) {
				continue;
			}

			$order->update_meta_data( self::META_PAYMENT_ID, $payment['id'] );
			if ( isset( $payment['monetary_account_id'] ) ) {
				$order->update_meta_data( self::META_MONETARY_ACCOUNT_ID, $payment['monetary_account_id'] );
			}
			self::record_payment_details( $order, $payment, $payment_request );
			$order->save();
			$order->add_order_note(
				sprintf(
					/* translators: 1: bunq payment id, 2: amount received */
					__( 'bunq payment %1$s of %2$s came in, but the order was cancelled. Restore the order or refund the payment.', 'bunq-for-woocommerce' ),
					$payment['id'],
					$payment['amount']['currency'] . ' ' . $payment['amount']['value']
				)
			);
			Logger::log( 'bunqme-tab ' . $payment_request_id . ': payment ' . $payment['id'] . ' came in for cancelled order #' . $order->get_order_number(), 'warning' );

			return true;
		}

		return false;
	}

	/**
	 * Run bunq calls with the API context loaded. An ended session is renewed by the API client itself; when bunq
	 * refuses a new session as well (the shop now calls from another IP address), the context is recreated from the
	 * saved API key and the calls run once more.
	 *
	 * @param callable $calls The bunq calls.
	 * @return mixed What the calls return.
	 * @throws Throwable When no usable API context is available or bunq fails.
	 */
	public function call_bunq( callable $calls ) {
		$this->ensure_api_context_loaded();
		$call_count = $this->api->get_call_count();

		try {
			$result = $calls();
		} catch ( ApiException $exception ) {
			// Only when none of the calls went through: repeating one that did could, for example, create a second
			// payment request. And not while a recent recreation failed: that costs several bunq calls per request.
			if ( ! $exception->is_context_rejected() || $this->api->get_call_count() !== $call_count || get_transient( self::CONTEXT_FAILED_TRANSIENT ) ) {
				throw $exception;
			}

			Logger::log( 'bunq refused a new session (' . Logger::format_error( $exception ) . '), recreating the API context from the saved API key', 'warning' );
			$this->recreate_api_context();
			$result = $calls();
		}

		ConnectionHealth::record_success( self::mode( 'yes' === $this->get_setting( 'testmode' ) ) );

		return $result;
	}

	/**
	 * Check that the connection with bunq works: the API context of the active mode, the access it was granted and
	 * the selected bank account. Recreates the API context like the other calls when bunq refuses its session.
	 *
	 * @throws Throwable When it does not.
	 */
	public function check_connection() {
		$this->call_bunq(
			function () {
				$this->api->check_connection( $this->get_monetary_account_bank_id() );
			}
		);
	}

	/**
	 * Make sure the bunq API context is loaded for this request. When the stored context is missing or can no longer
	 * be used (expired or revoked session), rebuild it from the saved API key before giving up.
	 *
	 * @throws Throwable When no usable API context is available.
	 */
	public function ensure_api_context_loaded() {
		if ( $this->load_api_context() ) {
			return;
		}

		// A rebuild costs several bunq calls; do not repeat it for every request while the key stays unusable.
		if ( get_transient( self::CONTEXT_FAILED_TRANSIENT ) ) {
			throw new Exception( 'bunq API context is not available and a recent attempt to recreate it failed. Complete the OAuth authorization in WooCommerce > Settings > Payments > bunq.' );
		}

		Logger::log( 'bunq API context could not be loaded, recreating it from the saved API key', 'warning' );
		$this->recreate_api_context();
	}

	/**
	 * Recreate the API context from the saved API key and load it; remember a failure for a few minutes so the next
	 * requests do not try again.
	 *
	 * @throws Throwable When it could not be recreated.
	 */
	private function recreate_api_context() {
		$mode = self::mode( 'yes' === $this->get_setting( 'testmode' ) );

		try {
			$this->refresh_api_context( false );
		} catch ( Throwable $exception ) {
			set_transient( self::CONTEXT_FAILED_TRANSIENT, 1, 5 * MINUTE_IN_SECONDS );
			ConnectionHealth::record( $mode, $exception );
			throw $exception;
		}

		if ( ! $this->load_api_context() ) {
			$exception = new Exception( 'bunq API context is not available. Complete the OAuth authorization in WooCommerce > Settings > Payments > bunq.' );
			set_transient( self::CONTEXT_FAILED_TRANSIENT, 1, 5 * MINUTE_IN_SECONDS );
			ConnectionHealth::record( $mode, $exception );
			throw $exception;
		}
	}

	/**
	 * Load the stored API context of the active mode for the bunq calls in this request, and store it again when
	 * bunq renewed its session.
	 *
	 * @return bool Whether an API context is loaded.
	 */
	public function load_api_context() {
		$api_context_json = $this->get_setting( 'api_context' );

		if ( ! $api_context_json ) {
			return false;
		}

		$credential = 'yes' === $this->get_setting( 'testmode' ) ? 'test_api_context' : 'api_context';

		return $this->api->load_api_context_from_json(
			$api_context_json,
			function ( $renewed_api_context_json ) use ( $credential ) {
				$this->set_credential( $credential, $renewed_api_context_json );
			}
		);
	}
}
