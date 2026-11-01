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
	 * The bunq API.
	 *
	 * @var ApiClient
	 */
	protected $api;

	/**
	 * The bunq OAuth authorization.
	 *
	 * @var OAuthClient
	 */
	protected $oauth;

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
		$this->icon               = '';
		$this->method_title       = 'bunq';
		$this->method_description = __( 'bunq payment gateway for WooCommerce', 'bunq-for-woocommerce' );
		$this->supports           = array(
			'products',
			'refunds',
		);

		// "Refresh bank accounts" button on the settings page: drop the cached list before the fields are built.
		if ( is_admin() && isset( $_GET[ self::REFRESH_ACCOUNTS_ACTION ] ) && current_user_can( 'manage_woocommerce' )
			&& wp_verify_nonce( sanitize_key( wp_unslash( isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '' ) ), self::REFRESH_ACCOUNTS_ACTION ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_key().
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
		$this->has_fields               = $this->direct_gateway;

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_' . CallbackHandler::API_REQUEST, array( $this, 'handle_callback' ) );

		foreach ( array( 'oauth_client_secret', 'test_oauth_client_secret' ) as $key ) {
			Logger::add_secret( $this->get_option( $key ), 'client secret' );
		}
		foreach ( array( 'api_key', 'test_api_key' ) as $key ) {
			Logger::add_secret( $this->get_setting( $key ), 'API key' );
		}
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
				isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '',
				isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : ''
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
		$view = isset( $data['view'] ) ? $data['view'] : '';

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
	 *     @type bool        $failed        Whether bunq recently rejected it.
	 *     @type bool        $client_saved  Whether the OAuth client id and secret are saved.
	 *     @type string|null $account_name  The bunq user that granted access.
	 *     @type string      $disconnect_url Where the "Disconnect" link goes.
	 * }
	 */
	public function get_connection( $mode ) {
		$prefix      = 'sandbox' === $mode ? 'test_' : '';
		$credentials = $this->get_credentials();
		$api_context = isset( $credentials[ $prefix . 'api_context' ] ) ? (string) $credentials[ $prefix . 'api_context' ] : '';
		$active      = self::mode( 'yes' === $this->get_option( 'testmode' ) ) === $mode;

		return array(
			'connected'      => '' !== $api_context,
			'failed'         => '' !== $api_context && $active && (bool) get_transient( self::CONTEXT_FAILED_TRANSIENT ),
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
		$mode = isset( $_GET[ self::DISCONNECT_ACTION ] ) ? sanitize_key( wp_unslash( $_GET[ self::DISCONNECT_ACTION ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified below.

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
		Logger::log( 'bunq API context created for ' . ( $testmode ? 'sandbox' : 'production' ), 'info' );

		if ( ! $register_callback ) {
			return;
		}

		// Register the callback URL at bunq so payments are confirmed automatically. A callback URL that only
		// resolves locally (development sites) cannot be reached by bunq, so skip it there.
		$callback_url = CallbackHandler::url();

		if ( Url::is_local( $callback_url ) ) {
			Logger::log( 'Skipped registering the bunq callback URL ' . $callback_url . ' because bunq cannot reach it.', 'info' );

			return;
		}

		try {
			$this->api->create_notification_filters( $this->get_monetary_account_bank_id() );
			Logger::log( 'bunq callback URL registered: ' . $callback_url, 'info' );
		} catch ( Throwable $exception ) {
			// Not fatal for the setup: payments still work, only the automatic payment confirmation is affected.
			Logger::log( $exception, 'warning' );
		}
	}

	/**
	 * Save the settings. Forgets the authorization of a mode whose OAuth client was removed, and goes on to bunq when
	 * the settings were saved with "Connect to bunq".
	 */
	public function process_admin_options() {
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
		}

		if ( empty( $this->settings['oauth_client_id'] ) || empty( $this->settings['oauth_client_secret'] ) ) {
			$this->set_credential( 'api_context', '' );
			$this->set_credential( 'api_key', '' );
			delete_transient( self::BANK_ACCOUNTS_TRANSIENT );
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

			return isset( $credentials[ $key ] ) ? $credentials[ $key ] : null;
		}

		return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : null;
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
		$saved = isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : '';

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
				function () {
					return $this->api->get_bank_accounts( true );
				}
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
		if ( ! parent::is_available() ) {
			return false;
		}

		if ( self::CURRENCY !== $this->get_checkout_currency() ) {
			return false;
		}

		return (bool) $this->get_setting( 'api_context' ) && ! get_transient( self::CONTEXT_FAILED_TRANSIENT );
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
			// The block checkout does not call validate_fields(), so check the posted method here as well.
			$bunqme_payment_method = null;
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verified the checkout nonce.
			if ( ! empty( $_POST[ self::PAYMENT_METHOD_FIELD ] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verified the checkout nonce.
				$bunqme_payment_method = PaymentMethods::bunqme_name( sanitize_text_field( wp_unslash( $_POST[ self::PAYMENT_METHOD_FIELD ] ) ) );
			}

			$payment_request = $this->call_bunq(
				function () use ( $order ) {
					return $this->api->create_payment_request(
						$order->get_total(),
						$order->get_currency(),
						'#' . $order->get_order_number(),
						$this->get_return_url( $order ),
						$this->get_monetary_account_bank_id()
					);
				}
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
			$order->save();

			// Fallback for a callback that never arrives: re-check the payment request in the background.
			PaymentMonitor::schedule_check( $order->get_id() );

			// Direct gateway: bunq.me opens the chosen method straight away when it is passed as ?paymentMethod= (it
			// used to be a path segment, which bunq.me now ignores).
			$redirect = $bunqme_payment_method
				? add_query_arg( 'paymentMethod', $bunqme_payment_method, $payment_request['url'] )
				: $payment_request['url'];

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
			$monetary_account_id = intval( $order->get_meta( self::META_MONETARY_ACCOUNT_ID ) );
			if ( ! $monetary_account_id ) {
				$monetary_account_id = $this->get_monetary_account_bank_id();
			}

			$payment = $this->call_bunq(
				function () use ( $payment_id, $monetary_account_id ) {
					return $this->api->get_payment( $payment_id, $monetary_account_id );
				}
			);
			$iban    = isset( $payment['counterparty_alias']['iban'] ) ? $payment['counterparty_alias']['iban'] : null;

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

			$account_holder = isset( $payment['counterparty_alias']['display_name'] ) ? $payment['counterparty_alias']['display_name'] : '';
			if ( ! $account_holder ) {
				$account_holder = $order->get_formatted_billing_full_name();
			}

			$refund_payment_id = $this->call_bunq(
				function () use ( $amount, $order, $iban, $account_holder, $description, $monetary_account_id ) {
					return $this->api->create_refund( (float) $amount, $order->get_currency(), $iban, $account_holder, $description, $monetary_account_id );
				}
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

			$payment_request = $this->call_bunq(
				function () use ( $payment_request_id ) {
					return $this->api->get_payment_request( $payment_request_id, $this->get_monetary_account_bank_id() );
				}
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

		foreach ( isset( $payment_request['result_inquiries'] ) ? (array) $payment_request['result_inquiries'] : array() as $result_inquiry ) {
			$payment = isset( $result_inquiry['payment'] ) ? $result_inquiry['payment'] : null;

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
			$order->update_meta_data( self::META_MONETARY_ACCOUNT_ID, isset( $payment['monetary_account_id'] ) ? $payment['monetary_account_id'] : null );
			$order->save();
			$order->payment_complete( (string) $payment['id'] );

			return 'paid';
		}

		// Money arrived but not the right amount: never cancel such an order, let the merchant decide.
		if ( $unmatched_payment ) {
			$unmatched_amount = $unmatched_payment['amount'];
			$order->update_meta_data( self::META_PAYMENT_ID, $unmatched_payment['id'] );
			$order->update_meta_data( self::META_MONETARY_ACCOUNT_ID, isset( $unmatched_payment['monetary_account_id'] ) ? $unmatched_payment['monetary_account_id'] : null );
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

		$status = isset( $payment_request['status'] ) ? (string) $payment_request['status'] : '';

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
	 * Cancel a bunq payment request at bunq so it can no longer be paid.
	 *
	 * @param int $payment_request_id The payment request.
	 * @throws Throwable When bunq could not be reached.
	 */
	public function cancel_payment_request( $payment_request_id ) {
		$this->call_bunq(
			function () use ( $payment_request_id ) {
				$this->api->cancel_payment_request( $payment_request_id, $this->get_monetary_account_bank_id() );
			}
		);
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
			return $calls();
		} catch ( ApiException $exception ) {
			// Only when none of the calls went through: repeating one that did could, for example, create a second
			// payment request. And not while a recent recreation failed: that costs several bunq calls per request.
			if ( ! $exception->is_context_rejected() || $this->api->get_call_count() !== $call_count || get_transient( self::CONTEXT_FAILED_TRANSIENT ) ) {
				throw $exception;
			}

			Logger::log( 'bunq refused a new session (' . Logger::format_error( $exception ) . '), recreating the API context from the saved API key', 'warning' );
			$this->recreate_api_context();
		}

		return $calls();
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
		try {
			$this->refresh_api_context( false );
		} catch ( Throwable $exception ) {
			set_transient( self::CONTEXT_FAILED_TRANSIENT, 1, 5 * MINUTE_IN_SECONDS );
			throw $exception;
		}

		if ( ! $this->load_api_context() ) {
			set_transient( self::CONTEXT_FAILED_TRANSIENT, 1, 5 * MINUTE_IN_SECONDS );
			throw new Exception( 'bunq API context is not available. Complete the OAuth authorization in WooCommerce > Settings > Payments > bunq.' );
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
