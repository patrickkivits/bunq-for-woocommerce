<?php
/**
 * The WooCommerce payment gateway.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Gateway;

use Exception;
use PatrickKivits\BunqForWooCommerce\Bunq\ApiClient;
use PatrickKivits\BunqForWooCommerce\Bunq\OAuthClient;
use PatrickKivits\BunqForWooCommerce\Logger;
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
	const LOCK_TRANSIENT_PREFIX    = 'wc_bunq_gateway.lock.';
	const REFRESH_ACCOUNTS_ACTION  = 'bunq_refresh_accounts';

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
	 * Exchange the code bunq returned for an access token and create the bunq API context with it. The outcome is
	 * shown on the settings page (admin_options()).
	 *
	 * @return string The settings page URL to return to.
	 */
	public function complete_oauth_authorization() {
		// Strip the OAuth parameters so the redirect URI matches the one registered at bunq.
		$oauth_redirect_uri = remove_query_arg( array( 'code', 'state' ), Url::current() );

		// Buffer any output (e.g. PHP deprecation notices from the SDK) so the redirect header can still be sent.
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
	 * The settings form, with the outcome of the last authorization and the authorization and refresh buttons.
	 */
	public function admin_options() {
		$last_error = get_transient( self::LAST_ERROR_TRANSIENT );
		if ( $last_error ) {
			delete_transient( self::LAST_ERROR_TRANSIENT );
			echo '<div class="notice notice-error"><p><strong>bunq for WooCommerce:</strong> ' . esc_html( $last_error ) . '</p>'
				. '<p>' . sprintf(
					/* translators: %s: link to the WooCommerce log page */
					esc_html__( 'Details are logged in %s (source: bunq).', 'bunq-for-woocommerce' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs' ) ) . '">' . esc_html__( 'WooCommerce > Status > Logs', 'bunq-for-woocommerce' ) . '</a>'
				) . '</p></div>';
		}

		$last_success = get_transient( self::LAST_SUCCESS_TRANSIENT );
		if ( $last_success ) {
			delete_transient( self::LAST_SUCCESS_TRANSIENT );
			echo '<div class="notice notice-success"><p><strong>bunq for WooCommerce:</strong> ' . esc_html( $last_success ) . '</p></div>';
		}

		parent::admin_options();
		$this->init_settings();

		$testmode            = 'yes' === $this->get_option( 'testmode' );
		$oauth_client_id     = $testmode ? $this->get_option( 'test_oauth_client_id' ) : $this->get_option( 'oauth_client_id' );
		$oauth_client_secret = $testmode ? $this->get_option( 'test_oauth_client_secret' ) : $this->get_option( 'oauth_client_secret' );

		$settings_url = remove_query_arg( array( 'code', 'state', self::REFRESH_ACCOUNTS_ACTION, '_wpnonce' ), Url::current() );

		if ( $oauth_client_id && $oauth_client_secret ) {
			$url = $this->oauth->get_authorization_url( $oauth_client_id, $oauth_client_secret, $settings_url, $testmode );
			echo '<a href="' . esc_url( $url ) . '" class="button-secondary">' . esc_html__( 'OAuth Authorization Request', 'bunq-for-woocommerce' ) . '</a> ';
		}

		if ( $this->get_setting( 'api_context' ) ) {
			$refresh_url = wp_nonce_url( add_query_arg( self::REFRESH_ACCOUNTS_ACTION, '1', $settings_url ), self::REFRESH_ACCOUNTS_ACTION );
			echo '<a href="' . esc_url( $refresh_url ) . '" class="button-secondary">' . esc_html__( 'Refresh bank accounts', 'bunq-for-woocommerce' ) . '</a>';
		}
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
		$api_context = $this->api->create_api_context( $api_key, $testmode );
		$this->set_credential( ( $testmode ? 'test_api_context' : 'api_context' ), $api_context->toJson() );
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
	 * Save the settings. Forgets the authorization of a mode whose OAuth client was removed.
	 */
	public function process_admin_options() {
		parent::process_admin_options();

		// Only the fields of the active mode are in the form, so the other mode's keys can be absent until they have
		// been saved once: a missing key counts as "not set".
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
			$data['placeholder'] = __( 'Saved. Enter a new value to replace it.', 'bunq-for-woocommerce' );
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
				$bank_accounts = $this->api->get_bank_accounts( $this->load_api_context() );

				// A real account list is kept for an hour; an error or "not set up yet" result only briefly, so it does
				// not stick around after the merchant fixed the cause.
				set_transient( self::BANK_ACCOUNTS_TRANSIENT, $bank_accounts, count( $bank_accounts ) > 1 ? HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS );
			}
		}

		// The settings that decide which other fields there are fall back to the defaults of these.
		$this->form_fields = SettingsFields::base( $bank_accounts );
		$direct_gateway    = 'yes' === $this->get_option( 'direct_gateway' );
		$testmode          = 'yes' === $this->get_option( 'testmode' );
		$connected         = (bool) $this->get_setting( 'api_context' );

		$this->form_fields = SettingsFields::get( $bank_accounts, $direct_gateway, $testmode, $connected );
	}

	/**
	 * Only offer the gateway at checkout when the bunq authorization has been completed; without an API context no
	 * payment request can be created.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( ! parent::is_available() ) {
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
			$this->ensure_api_context_loaded();

			// The block checkout does not call validate_fields(), so check the posted method here as well.
			$bunqme_payment_method = null;
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verified the checkout nonce.
			if ( ! empty( $_POST[ self::PAYMENT_METHOD_FIELD ] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verified the checkout nonce.
				$bunqme_payment_method = PaymentMethods::bunqme_name( sanitize_text_field( wp_unslash( $_POST[ self::PAYMENT_METHOD_FIELD ] ) ) );
			}

			$payment_request = $this->api->create_payment_request(
				$order->get_total(),
				$order->get_currency(),
				'#' . $order->get_order_number(),
				$this->get_return_url( $order ),
				$this->get_monetary_account_bank_id()
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
			$this->ensure_api_context_loaded();

			$monetary_account_id = intval( $order->get_meta( self::META_MONETARY_ACCOUNT_ID ) );
			if ( ! $monetary_account_id ) {
				$monetary_account_id = $this->get_monetary_account_bank_id();
			}

			$payment      = $this->api->get_payment( $payment_id, $monetary_account_id );
			$counterparty = $payment->getCounterpartyAlias();
			$iban         = $counterparty ? $counterparty->getIban() : null;

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

			$account_holder = $counterparty->getDisplayName();
			if ( ! $account_holder ) {
				$account_holder = $order->get_formatted_billing_full_name();
			}

			$refund_payment_id = $this->api->create_refund(
				(float) $amount,
				$order->get_currency(),
				$iban,
				$account_holder,
				$description,
				$monetary_account_id
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
		$lock = self::LOCK_TRANSIENT_PREFIX . $order->get_id();
		if ( get_transient( $lock ) ) {
			return 'pending';
		}
		set_transient( $lock, 1, 30 );

		try {
			$this->ensure_api_context_loaded();

			$payment_request = $this->api->get_payment_request( $payment_request_id, $this->get_monetary_account_bank_id() );

			return $this->apply_payment_request( $order, $payment_request_id, $payment_request );
		} finally {
			delete_transient( $lock );
		}
	}

	/**
	 * Bring the order in line with its payment request as bunq reported it.
	 *
	 * @param WC_Order $order              The order.
	 * @param int      $payment_request_id The id of the payment request.
	 * @param object   $payment_request    The bunq.me payment request (BunqMeTabApiObject).
	 * @return string See check_payment_status().
	 */
	private function apply_payment_request( $order, $payment_request_id, $payment_request ) {
		$unmatched_payment = null;

		foreach ( (array) $payment_request->getResultInquiries() as $result_inquiry ) {
			$payment = $result_inquiry->getPayment();

			if ( ! $payment ) {
				continue;
			}

			// bunq returns the amount as a string; compare it as a decimal so the number of decimals in the shop
			// does not matter.
			$paid_amount = $payment->getAmount();

			if ( $paid_amount->getCurrency() !== $order->get_currency() || ! Money::amounts_match( $paid_amount->getValue(), $order->get_total() ) ) {
				Logger::log( 'bunqme-tab ' . $payment_request_id . ': payment ' . $payment->getId() . ' of ' . $paid_amount->getCurrency() . ' ' . $paid_amount->getValue() . ' does not match order #' . $order->get_order_number() . ' total ' . $order->get_currency() . ' ' . $order->get_total(), 'warning' );
				$unmatched_payment = $payment;
				continue;
			}

			Logger::log( 'bunqme-tab ' . $payment_request_id . ': payment ' . $payment->getId() . ' received for order #' . $order->get_order_number(), 'info' );

			$order->add_order_note(
				sprintf(
					/* translators: %s: bunq payment id */
					__( 'bunq payment %s received', 'bunq-for-woocommerce' ),
					$payment->getId()
				)
			);
			$order->update_meta_data( self::META_PAYMENT_ID, $payment->getId() );
			// The account the money landed on, so a refund still works after the merchant changes the setting.
			$order->update_meta_data( self::META_MONETARY_ACCOUNT_ID, $payment->getMonetaryAccountId() );
			$order->save();
			$order->payment_complete( (string) $payment->getId() );

			return 'paid';
		}

		// Money arrived but not the right amount: never cancel such an order, let the merchant decide.
		if ( $unmatched_payment ) {
			$unmatched_amount = $unmatched_payment->getAmount();
			$order->update_meta_data( self::META_PAYMENT_ID, $unmatched_payment->getId() );
			$order->update_meta_data( self::META_MONETARY_ACCOUNT_ID, $unmatched_payment->getMonetaryAccountId() );
			$order->save();
			$order->update_status(
				'on-hold',
				sprintf(
					/* translators: 1: bunq payment id, 2: amount received, 3: order total */
					__( 'bunq payment %1$s of %2$s received, but the order total is %3$s. Check the payment before processing the order.', 'bunq-for-woocommerce' ),
					$unmatched_payment->getId(),
					$unmatched_amount->getCurrency() . ' ' . $unmatched_amount->getValue(),
					$order->get_currency() . ' ' . $order->get_total()
				)
			);

			return 'on-hold';
		}

		$status = (string) $payment_request->getStatus();

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
		$this->ensure_api_context_loaded();
		$this->api->cancel_payment_request( $payment_request_id, $this->get_monetary_account_bank_id() );
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

		$testmode             = 'yes' === $this->get_setting( 'testmode' );
		$new_api_context_json = $this->api->load_api_context_from_json( $api_context_json );

		if ( $new_api_context_json && $new_api_context_json !== $api_context_json ) {
			$this->set_credential( ( $testmode ? 'test_api_context' : 'api_context' ), $new_api_context_json );
		}

		return (bool) $new_api_context_json;
	}
}
