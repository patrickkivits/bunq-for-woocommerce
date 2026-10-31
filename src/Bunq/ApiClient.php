<?php
/**
 * The bunq API.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Bunq;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use PatrickKivits\BunqForWooCommerce\Gateway\CallbackHandler;
use PatrickKivits\BunqForWooCommerce\Logger;
use PatrickKivits\BunqForWooCommerce\Plugin;
use RuntimeException;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Every call the plugin makes to bunq (https://doc.bunq.com/). The calls are signed with the key pair of the API
 * context, paced by a RequestThrottler and retried when bunq answers "too many requests".
 *
 * The API context is what bunq needs to accept calls: an installation (a key pair registered at bunq), a device
 * server (the API key bound to this server's IP address) and a session. It is stored as JSON:
 * {"api_key", "environment_type", "installation_context": {"token", "private_key_client", "public_key_server"},
 * "session_context": {"token", "expiry_time", "user_id", "display_name"}}, which reads the contexts the bunq SDK
 * stored up to version 1.8 as well.
 */
class ApiClient {

	/**
	 * The device description bunq shows for the API context.
	 */
	const DEVICE_DESCRIPTION = 'bunq for WooCommerce';

	/**
	 * The notification category of bunq.me payment requests.
	 */
	const NOTIFICATION_CATEGORY = 'BUNQME_TAB';

	const BASE_URL_PRODUCTION = 'https://api.bunq.com/v1/';
	const BASE_URL_SANDBOX    = 'https://public-api.sandbox.bunq.com/v1/';

	/**
	 * The public key of api.bunq.com, pinned for production calls.
	 */
	const PINNED_PUBLIC_KEY_FILE = __DIR__ . '/api.bunq.com.pubkey.pem';

	/**
	 * A session is renewed this many seconds before bunq expires it.
	 */
	const SESSION_RENEWAL_MARGIN = 30;

	/**
	 * Expiry time format of the stored session, in UTC.
	 */
	const EXPIRY_TIME_FORMAT = 'Y-m-d H:i:s.u';

	/**
	 * Attempts per call while bunq answers "too many requests", and the seconds between them.
	 */
	const ATTEMPTS    = 3;
	const RETRY_DELAY = 3;

	/**
	 * Paces the calls made within this request.
	 *
	 * @var RequestThrottler
	 */
	private $throttler;

	/**
	 * Sends the HTTP requests.
	 *
	 * @var ClientInterface
	 */
	private $http;

	/**
	 * The loaded API context, null until one is created or loaded.
	 *
	 * @var array|null
	 */
	private $context = null;

	/**
	 * The default monetary account of the loaded context, looked up when a call needs it.
	 *
	 * @var int|null
	 */
	private $primary_monetary_account_id = null;

	/**
	 * Stores the loaded API context when its session changes, null when the caller does not store it.
	 *
	 * @var callable|null
	 */
	private $save = null;

	/**
	 * The calls bunq answered successfully so far.
	 *
	 * @var int
	 */
	private $call_count = 0;

	/**
	 * Constructor.
	 *
	 * @param RequestThrottler|null $throttler Paces the calls; one with bunq's limits by default.
	 * @param ClientInterface|null  $http      Sends the HTTP requests; a Guzzle client by default.
	 */
	public function __construct( ?RequestThrottler $throttler = null, ?ClientInterface $http = null ) {
		$this->throttler = $throttler ? $throttler : new RequestThrottler( new RequestThrottlerConfiguration() );
		$this->http      = $http ? $http : new Client();
	}

	/**
	 * Create a new API context (installation, device server and session at bunq) and load it.
	 *
	 * @param string $api_key  The API key (the OAuth access token).
	 * @param bool   $testmode Whether to use the sandbox.
	 * @return string The API context to store.
	 * @throws Throwable When bunq rejects the key.
	 */
	public function create_api_context( $api_key, $testmode ) {
		$key_pair = self::generate_key_pair();
		$context  = array(
			'api_key'          => (string) $api_key,
			'environment_type' => $testmode ? 'SANDBOX' : 'PRODUCTION',
		);

		$installation = $this->send( $context, 'POST', 'installation', array( 'client_public_key' => $key_pair['public'] ) );
		$token        = self::item( $installation, 'Token' );
		$server_key   = self::item( $installation, 'ServerPublicKey' );

		$context['installation_context'] = array(
			'token'              => $token['token'],
			'private_key_client' => $key_pair['private'],
			'public_key_server'  => $server_key['server_public_key'],
		);

		// An empty list of IP addresses binds the API key to the address this call comes from.
		$this->send(
			$context,
			'POST',
			'device-server',
			array(
				'description'   => self::DEVICE_DESCRIPTION,
				'secret'        => $context['api_key'],
				'permitted_ips' => self::permitted_ips(),
			)
		);

		$this->load( $this->start_session( $context ), null );

		return self::to_json( $this->context );
	}

	/**
	 * The IP addresses bunq accepts the API key from, registered with the device server when connecting.
	 *
	 * By default the list is empty, so bunq binds the key to the address the shop connects from. A shop whose
	 * outgoing requests come from several addresses (a load balanced or serverless host) can list all of them with
	 * the bunq_for_woocommerce_permitted_ips filter; bunq then accepts the key from each. The list only applies to
	 * new connections: connect again after changing it.
	 *
	 * @return string[] Valid IPv4 and IPv6 addresses, without duplicates.
	 */
	public static function permitted_ips() {
		/**
		 * Filters the IP addresses bunq accepts the shop's API key from.
		 *
		 * @param string[] $permitted_ips IP addresses; empty to use the address the shop connects from.
		 */
		$ips = apply_filters( 'bunq_for_woocommerce_permitted_ips', array() );

		$permitted = array();
		foreach ( (array) $ips as $ip ) {
			$ip = trim( (string) $ip );
			if ( false !== filter_var( $ip, FILTER_VALIDATE_IP ) && ! in_array( $ip, $permitted, true ) ) {
				$permitted[] = $ip;
			}
		}

		return $permitted;
	}

	/**
	 * Load a stored API context for the calls in this request, renewing its session when it (nearly) expired. The
	 * session is renewed again whenever a later call finds it expired or ended.
	 *
	 * @param string        $json The stored API context.
	 * @param callable|null $save Stores the API context when its session was renewed; receives its JSON.
	 * @return bool Whether the API context can be used.
	 */
	public function load_api_context_from_json( $json, ?callable $save = null ) {
		if ( ! $json ) {
			return false;
		}

		try {
			$this->load( self::parse_context( $json ), $save );

			if ( ! self::is_session_active( $this->context ) ) {
				$this->renew_session();
			}

			return true;
		} catch ( Throwable $exception ) {
			Logger::log( $exception );
			$this->context = null;
		}

		return false;
	}

	/**
	 * The name of the bunq user that granted the shop access, read from a stored API context without calling bunq.
	 *
	 * @param string $json The stored API context.
	 * @return string|null Null when the context does not tell.
	 */
	public function get_account_name( $json ) {
		$context = json_decode( (string) $json, true );
		$session = isset( $context['session_context'] ) && is_array( $context['session_context'] ) ? $context['session_context'] : array();

		// Contexts stored by the bunq SDK keep the users of the session instead of the name.
		$name = array_key_exists( 'display_name', $session ) ? $session['display_name'] : self::display_name( $session );

		return is_string( $name ) && '' !== $name ? $name : null;
	}

	/**
	 * Create a bunq.me payment request.
	 *
	 * @param string   $amount              Amount.
	 * @param string   $currency            Currency.
	 * @param string   $description         Shown to the payer.
	 * @param string   $return_url          Where bunq.me sends the payer after paying.
	 * @param int|null $monetary_account_id The receiving account, null for the default account.
	 * @return array The share URL ("url") and the id ("id") of the request.
	 */
	public function create_payment_request( $amount, $currency, $description, $return_url, $monetary_account_id = null ) {
		$response = $this->request(
			'POST',
			$this->account_path( $monetary_account_id, 'bunqme-tab' ),
			array(
				'bunqme_tab_entry' => array(
					'amount_inquired' => array(
						'value'    => (string) $amount,
						'currency' => $currency,
					),
					'description'     => $description,
					'redirect_url'    => $return_url,
				),
			)
		);

		// Only the id comes back; the share URL is in the payment request itself.
		$payment_request = $this->get_payment_request( self::id( $response ), $monetary_account_id );

		return array(
			'url' => $payment_request['bunqme_tab_share_url'],
			'id'  => $payment_request['id'],
		);
	}

	/**
	 * Fetch a bunq.me payment request.
	 *
	 * @param int      $payment_request_id  The payment request.
	 * @param int|null $monetary_account_id Its account, null for the default account.
	 * @return array The BunqMeTab as bunq returns it ("id", "status", "bunqme_tab_share_url", "result_inquiries", ...),
	 *               with the payment of each result inquiry unwrapped to a Payment (see get_payment()).
	 */
	public function get_payment_request( $payment_request_id, $monetary_account_id = null ) {
		$payment_request = self::item( $this->request( 'GET', $this->account_path( $monetary_account_id, 'bunqme-tab/' . (int) $payment_request_id ) ), 'BunqMeTab' );

		if ( isset( $payment_request['result_inquiries'] ) && is_array( $payment_request['result_inquiries'] ) ) {
			foreach ( $payment_request['result_inquiries'] as $index => $inquiry ) {
				// bunq wraps the payment in its type: {"payment": {"Payment": {...}}}.
				if ( isset( $inquiry['payment']['Payment'] ) ) {
					$payment_request['result_inquiries'][ $index ]['payment'] = $inquiry['payment']['Payment'];
				}
			}
		}

		return $payment_request;
	}

	/**
	 * Cancel a bunq.me payment request so it can no longer be paid.
	 *
	 * @param int      $payment_request_id  The payment request.
	 * @param int|null $monetary_account_id Its account, null for the default account.
	 */
	public function cancel_payment_request( $payment_request_id, $monetary_account_id = null ) {
		$this->request( 'PUT', $this->account_path( $monetary_account_id, 'bunqme-tab/' . (int) $payment_request_id ), array( 'status' => 'CANCELLED' ) );
	}

	/**
	 * Fetch a payment received on a bunq account.
	 *
	 * @param int      $payment_id          The payment.
	 * @param int|null $monetary_account_id Its account, null for the default account.
	 * @return array The Payment as bunq returns it: "id", "monetary_account_id", "amount" ("value", "currency"),
	 *               "counterparty_alias" ("iban", "display_name"), ...
	 */
	public function get_payment( $payment_id, $monetary_account_id = null ) {
		return self::item( $this->request( 'GET', $this->account_path( $monetary_account_id, 'payment/' . (int) $payment_id ) ), 'Payment' );
	}

	/**
	 * Strip characters bunq rejects in the description of a payment to a non-bunq account.
	 *
	 * Such payments leave bunq as SEPA transfers, whose remittance text allows letters, digits, spaces and the
	 * punctuation / - ? : ( ) . , ' +. Anything else (for example # or emojis) makes bunq return HTTP 478 "The
	 * description text of a payment to a non-bunqer cannot contain special characters or emojis".
	 *
	 * @param string $description Description.
	 * @return string
	 */
	public static function sanitize_payment_description( $description ) {
		$description = preg_replace( '/[^\p{L}\p{N} \/\-?:().,\'+]/u', ' ', (string) $description );
		$description = preg_replace( '/ {2,}/', ' ', $description );

		return trim( $description );
	}

	/**
	 * Create a draft payment back to an IBAN (a refund of a received payment).
	 *
	 * The plugin authenticates with an OAuth access token, and bunq only lets OAuth apps move money between the
	 * user's own accounts or create draft payments. A direct payment to the customer's IBAN is rejected with "Not
	 * enough permissions to create payment", so the refund is created as a draft that the merchant approves in the
	 * bunq app.
	 *
	 * @param float    $amount              Amount.
	 * @param string   $currency            Currency.
	 * @param string   $iban                IBAN to pay to.
	 * @param string   $name                Account holder name, required by bunq for IBAN pointers.
	 * @param string   $description         Shown to the recipient; sanitized with sanitize_payment_description() and
	 *                                      cut to the 140 characters bunq allows.
	 * @param int|null $monetary_account_id The paying account, null for the default account.
	 * @return int The id of the draft payment.
	 */
	public function create_refund( $amount, $currency, $iban, $name, $description, $monetary_account_id = null ) {
		$entry = array(
			'amount'             => array(
				'value'    => number_format( (float) $amount, 2, '.', '' ),
				'currency' => $currency,
			),
			'counterparty_alias' => array(
				'type'  => 'IBAN',
				'value' => $iban,
				'name'  => $name,
			),
			'description'        => mb_substr( self::sanitize_payment_description( $description ), 0, 140 ),
		);

		return self::id(
			$this->request(
				'POST',
				$this->account_path( $monetary_account_id, 'draft-payment' ),
				array(
					'entries'                    => array( $entry ),
					'number_of_required_accepts' => 1,
				)
			)
		);
	}

	/**
	 * The bank accounts payments can be received on, as options for a select.
	 *
	 * @param mixed $api_context Whether an API context is loaded.
	 * @return array Account labels by monetary account id, with an empty first option that explains the state.
	 * @throws ApiException When bunq refuses a new session for the API context (is_context_rejected()).
	 */
	public function get_bank_accounts( $api_context ) {
		if ( ! $api_context ) {
			return array( '' => __( 'Connect your bunq account first', 'bunq-for-woocommerce' ) );
		}

		try {
			$bank_accounts = array( '' => __( 'Select a bank account', 'bunq-for-woocommerce' ) );
			$account_types = array(
				'monetary-account-bank'  => '',
				'monetary-account-joint' => ' ' . __( '(Joint)', 'bunq-for-woocommerce' ),
			);

			foreach ( $account_types as $endpoint => $suffix ) {
				foreach ( $this->list_monetary_accounts( $endpoint ) as $account ) {
					foreach ( isset( $account['alias'] ) ? (array) $account['alias'] : array() as $alias ) {
						if ( isset( $alias['type'], $alias['value'] ) && 'IBAN' === $alias['type'] ) {
							$bank_accounts[ $account['id'] ] = $alias['value'] . ' - ' . $account['description'] . $suffix;
						}
					}
				}
			}

			return $bank_accounts;
		} catch ( Throwable $exception ) {
			// The caller can recreate the API context for this one.
			if ( $exception instanceof ApiException && $exception->is_context_rejected() ) {
				throw $exception;
			}

			Logger::log( $exception );

			return array(
				'' => sprintf(
					/* translators: %s: error message from bunq */
					__( 'Error: %s', 'bunq-for-woocommerce' ),
					Logger::format_error( $exception )
				),
			);
		}
	}

	/**
	 * Register the WooCommerce callback URL for bunq.me payment requests on the monetary account.
	 *
	 * @param int|null $monetary_account_id The account, null for the default account.
	 */
	public function create_notification_filters( $monetary_account_id = null ) {
		$callback_url = CallbackHandler::url();
		$path         = $this->account_path( $monetary_account_id, 'notification-filter-url' );

		// bunq replaces the complete list of URL notification filters of the monetary account with the posted list,
		// so keep the filters that are already there (other integrations) and add ours to them.
		$notification_filters = array();

		try {
			// One NotificationFilterUrl per filter. 200 is the largest page bunq returns; the POST replaces the whole list,
			// so a filter left out would be removed.
			foreach ( $this->request( 'GET', $path . '?count=200' ) as $item ) {
				$filter = isset( $item['NotificationFilterUrl'] ) ? $item['NotificationFilterUrl'] : array();

				if ( ! isset( $filter['category'], $filter['notification_target'] ) ) {
					continue;
				}

				if ( self::NOTIFICATION_CATEGORY === $filter['category'] && $filter['notification_target'] === $callback_url ) {
					continue; // Ours, added below.
				}

				// Listed filters also carry response fields (id, created, ...) that must not be posted back.
				$notification_filters[] = array_filter(
					array_intersect_key( $filter, array_flip( array( 'category', 'notification_target', 'all_user_id', 'all_monetary_account_id', 'all_verification_type' ) ) )
				);
			}
		} catch ( Throwable $exception ) {
			// Registering our callback matters more than preserving the others; log what happened.
			Logger::log( 'Could not read the existing bunq notification filters, registering only the WooCommerce callback: ' . Logger::format_error( $exception ), 'warning' );
			$notification_filters = array();
		}

		$notification_filters[] = array(
			'category'            => self::NOTIFICATION_CATEGORY,
			'notification_target' => $callback_url,
		);

		$this->request( 'POST', $path, array( 'notification_filters' => $notification_filters ) );
	}

	/**
	 * The bunq user id of the loaded API context.
	 *
	 * @return int
	 * @throws RuntimeException When no API context is loaded.
	 */
	public function user_id() {
		if ( ! $this->context ) {
			throw new RuntimeException( 'No bunq API context is loaded.' );
		}

		return (int) $this->context['session_context']['user_id'];
	}

	/**
	 * Call bunq with the loaded API context.
	 *
	 * @param string     $method HTTP method.
	 * @param string     $path   Path below /v1/, for example "user/1/monetary-account-bank".
	 * @param array|null $body   Request body for POST and PUT.
	 * @return array The "Response" list of bunq's answer.
	 * @throws ApiException When bunq answers with an error.
	 * @throws Throwable When bunq cannot be reached or its answer cannot be trusted.
	 */
	public function request( $method, $path, $body = null ) {
		if ( ! $this->context ) {
			throw new RuntimeException( 'No bunq API context is loaded.' );
		}

		// A long request (a batch of scheduled checks) can outlive the session.
		if ( ! self::is_session_active( $this->context ) ) {
			$this->renew_session();
		}

		try {
			$response = $this->send( $this->context, $method, $path, $body );
		} catch ( ApiException $exception ) {
			if ( ! $exception->is_authentication_error() ) {
				throw $exception;
			}

			// bunq ended the session before its expiry time. bunq rejected the call, so repeating it with a new session
			// cannot do it twice.
			Logger::log( 'bunq rejected the session, opening a new one: ' . Logger::format_error( $exception ), 'warning' );
			$this->renew_session();
			$response = $this->send( $this->context, $method, $path, $body );
		}

		++$this->call_count;

		return $response;
	}

	/**
	 * The number of calls bunq answered successfully so far, to tell whether a series of calls had any effect yet.
	 *
	 * @return int
	 */
	public function get_call_count() {
		return $this->call_count;
	}

	/**
	 * Use an API context for the calls in this request.
	 *
	 * @param array         $context The API context.
	 * @param callable|null $save    Stores the API context when its session changes.
	 */
	private function load( array $context, $save ) {
		if ( ! $this->context || $this->context['api_key'] !== $context['api_key'] ) {
			$this->primary_monetary_account_id = null;
		}

		$this->context = $context;
		$this->save    = $save;
	}

	/**
	 * Open a new session for the loaded API context and store the context.
	 *
	 * @throws ApiException When bunq refuses (is_context_rejected() for a revoked key or another IP address).
	 */
	private function renew_session() {
		try {
			$this->context = $this->start_session( $this->context );
		} catch ( ApiException $exception ) {
			// bunq answers 400 "Incorrect API key or IP address" or 401/403 when the key cannot open a session here.
			if ( in_array( $exception->getCode(), array( 400, 401, 403 ), true ) ) {
				throw new ApiException( $exception->getMessage(), $exception->getCode(), $exception->get_response_id(), true ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Escaped where it is shown.
			}

			throw $exception;
		}

		if ( $this->save ) {
			call_user_func( $this->save, self::to_json( $this->context ) );
		}
	}

	/**
	 * Open a session for the API context.
	 *
	 * @param array $context The API context, with or without a session.
	 * @return array The API context with the new session.
	 */
	private function start_session( array $context ) {
		unset( $context['session_context'] );

		$response = $this->send( $context, 'POST', 'session-server', array( 'secret' => $context['api_key'] ) );
		$token    = self::item( $response, 'Token' );

		// The session's user: UserPerson, UserCompany, UserApiKey (OAuth) or UserPaymentServiceProvider.
		$users = array();
		foreach ( $response as $item ) {
			$type = key( $item );
			if ( 0 === strpos( (string) $type, 'User' ) && is_array( $item[ $type ] ) ) {
				$users[ $type ] = $item[ $type ];
			}
		}

		$user = reset( $users );
		if ( ! $user || empty( $user['id'] ) ) {
			throw new RuntimeException( 'bunq did not return the user of the session.' );
		}

		// An OAuth key's session lasts as long as the app that requested it (requested_by_user) allows.
		$timeout_user = isset( $users['UserApiKey'] ) ? self::anchored_user( $users['UserApiKey'], 'requested_by_user' ) : $user;
		$timeout      = isset( $timeout_user['session_timeout'] ) ? (int) $timeout_user['session_timeout'] : HOUR_IN_SECONDS;

		$context['session_context'] = array(
			'token'        => $token['token'],
			'expiry_time'  => gmdate( self::EXPIRY_TIME_FORMAT, time() + $timeout ),
			'user_id'      => (int) $user['id'],
			'display_name' => self::display_name( $users ),
		);

		return $context;
	}

	/**
	 * Send one call to bunq: authenticated with the context's token, signed with its key, the answer verified with
	 * bunq's key. Paced by the throttler, and retried while bunq answers "too many requests".
	 *
	 * @param array      $context The API context; without installation for the installation call itself.
	 * @param string     $method  HTTP method.
	 * @param string     $path    Path below /v1/.
	 * @param array|null $body    Request body for POST and PUT.
	 * @return array The "Response" list of bunq's answer.
	 * @throws ApiException When bunq answers with an error.
	 * @throws Throwable When bunq cannot be reached or its answer cannot be trusted.
	 */
	private function send( array $context, $method, $path, $body = null ) {
		$production   = 'PRODUCTION' === $context['environment_type'];
		$installation = isset( $context['installation_context'] ) ? $context['installation_context'] : null;
		$body_string  = in_array( $method, array( 'POST', 'PUT' ), true ) ? wp_json_encode( $body ? $body : new \stdClass() ) : '';

		$headers = array(
			'Cache-Control'      => 'no-cache',
			'User-Agent'         => 'bunq-for-woocommerce/' . Plugin::VERSION,
			'X-Bunq-Language'    => 'en_US',
			'X-Bunq-Region'      => 'nl_NL',
			'X-Bunq-Geolocation' => '0 0 0 0 000',
		);

		if ( isset( $context['session_context']['token'] ) ) {
			$headers['X-Bunq-Client-Authentication'] = $context['session_context']['token'];
		} elseif ( $installation ) {
			$headers['X-Bunq-Client-Authentication'] = $installation['token'];
		}

		if ( $installation ) {
			if ( ! openssl_sign( $body_string, $signature, $installation['private_key_client'], OPENSSL_ALGO_SHA256 ) ) {
				throw new RuntimeException( 'Could not sign the bunq request with the key of the API context.' );
			}

			$headers['X-Bunq-Client-Signature'] = base64_encode( $signature ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- bunq's signature format.
		}

		$options = array(
			'headers'         => $headers,
			'http_errors'     => false,
			'allow_redirects' => false,
			'timeout'         => 30,
		);

		if ( '' !== $body_string ) {
			$options['headers']['Content-Type'] = 'application/json';
			$options['body']                    = $body_string;
		}

		if ( $production ) {
			$options['curl'] = array( ( defined( 'CURLOPT_PINNEDPUBLICKEY' ) ? CURLOPT_PINNEDPUBLICKEY : 10230 ) => self::PINNED_PUBLIC_KEY_FILE );
		}

		$url = ( $production ? self::BASE_URL_PRODUCTION : self::BASE_URL_SANDBOX ) . $path;

		for ( $attempt = 1; ; $attempt++ ) {
			$this->throttler->ensure_api_limits_are_respected( $path, $method );

			// bunq rejects a request id it saw before, so a retry gets a new one.
			$options['headers']['X-Bunq-Client-Request-Id'] = bin2hex( random_bytes( 16 ) );

			$response      = $this->http->request( $method, $url, $options );
			$status        = $response->getStatusCode();
			$response_body = (string) $response->getBody();

			if ( 429 === $status && $attempt < self::ATTEMPTS ) {
				Logger::log( 'bunq rate limit hit, retrying in ' . self::RETRY_DELAY . ' seconds (attempt ' . $attempt . ' of ' . self::ATTEMPTS . ')', 'warning' );
				sleep( self::RETRY_DELAY );
				continue;
			}

			if ( 200 !== $status ) {
				throw ApiException::from_response( $status, $response_body, $response->getHeaderLine( 'X-Bunq-Client-Response-Id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Escaped where it is shown.
			}

			break;
		}

		// bunq signs the body of every answer once the installation exchanged keys.
		if ( $installation ) {
			$signature = base64_decode( $response->getHeaderLine( 'X-Bunq-Server-Signature' ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- bunq's signature format.

			if ( 1 !== openssl_verify( $response_body, (string) $signature, $installation['public_key_server'], OPENSSL_ALGO_SHA256 ) ) {
				throw new RuntimeException( 'The signature of bunq\'s answer to ' . $method . ' ' . $path . ' is invalid.' ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Escaped where it is shown.
			}
		}

		$decoded = json_decode( $response_body, true );

		if ( ! isset( $decoded['Response'] ) || ! is_array( $decoded['Response'] ) ) {
			throw new RuntimeException( 'bunq\'s answer to ' . $method . ' ' . $path . ' is not a valid response.' ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Escaped where it is shown.
		}

		return $decoded['Response'];
	}

	/**
	 * The path below a monetary account.
	 *
	 * @param int|null $monetary_account_id The account, null for the default account.
	 * @param string   $path                Path below the account.
	 * @return string
	 */
	private function account_path( $monetary_account_id, $path ) {
		if ( ! $monetary_account_id ) {
			$monetary_account_id = $this->primary_monetary_account_id();
		}

		return 'user/' . $this->user_id() . '/monetary-account/' . (int) $monetary_account_id . '/' . $path;
	}

	/**
	 * The default account: the first active bank account of the user.
	 *
	 * @return int
	 * @throws RuntimeException When the user has no active bank account.
	 */
	private function primary_monetary_account_id() {
		if ( ! $this->primary_monetary_account_id ) {
			foreach ( $this->list_monetary_accounts( 'monetary-account-bank' ) as $account ) {
				if ( isset( $account['status'] ) && 'ACTIVE' === $account['status'] ) {
					$this->primary_monetary_account_id = (int) $account['id'];
					break;
				}
			}
		}

		if ( ! $this->primary_monetary_account_id ) {
			throw new RuntimeException( 'The bunq account has no active bank account.' );
		}

		return $this->primary_monetary_account_id;
	}

	/**
	 * The user's monetary accounts of a type.
	 *
	 * @param string $endpoint "monetary-account-bank" or "monetary-account-joint".
	 * @return array[]
	 */
	private function list_monetary_accounts( $endpoint ) {
		$type     = 'monetary-account-bank' === $endpoint ? 'MonetaryAccountBank' : 'MonetaryAccountJoint';
		$accounts = array();

		// 200 is the largest page bunq returns.
		foreach ( $this->request( 'GET', 'user/' . $this->user_id() . '/' . $endpoint . '?count=200' ) as $item ) {
			if ( isset( $item[ $type ]['id'] ) ) {
				$accounts[] = $item[ $type ];
			}
		}

		return $accounts;
	}

	/**
	 * A new RSA key pair for an installation.
	 *
	 * @return array The PEM encoded "private" and "public" key.
	 * @throws RuntimeException When OpenSSL cannot generate one.
	 */
	public static function generate_key_pair() {
		$key = openssl_pkey_new(
			array(
				'private_key_bits' => 2048,
				'private_key_type' => OPENSSL_KEYTYPE_RSA,
			)
		);

		$details = $key ? openssl_pkey_get_details( $key ) : false;

		if ( ! $details || ! openssl_pkey_export( $key, $private_key ) ) {
			throw new RuntimeException( 'OpenSSL could not generate a key pair: ' . openssl_error_string() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Escaped where it is shown.
		}

		return array(
			'private' => $private_key,
			'public'  => $details['key'],
		);
	}

	/**
	 * Read a stored API context.
	 *
	 * @param string $json The stored API context.
	 * @return array
	 * @throws Exception When it is not a complete API context.
	 */
	private static function parse_context( $json ) {
		$context = json_decode( $json, true );

		if ( ! is_array( $context ) ) {
			throw new Exception( 'The stored bunq API context is not valid JSON: ' . json_last_error_msg() ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Only logged.
		}

		foreach ( array( 'api_key', 'environment_type', 'installation_context.token', 'installation_context.private_key_client', 'installation_context.public_key_server' ) as $field ) {
			$value = $context;
			foreach ( explode( '.', $field ) as $key ) {
				$value = is_array( $value ) && isset( $value[ $key ] ) ? $value[ $key ] : null;
			}

			if ( null === $value ) {
				throw new Exception( 'The stored bunq API context has no ' . $field . '.' ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Only logged.
			}
		}

		$session = isset( $context['session_context'] ) ? $context['session_context'] : null;

		$context['installation_context'] = array_intersect_key( $context['installation_context'], array_flip( array( 'token', 'private_key_client', 'public_key_server' ) ) );
		unset( $context['session_context'], $context['proxy_url'] );

		if ( isset( $session['token'], $session['expiry_time'], $session['user_id'] ) ) {
			$context['session_context'] = array(
				'token'        => $session['token'],
				'expiry_time'  => $session['expiry_time'],
				'user_id'      => (int) $session['user_id'],
				'display_name' => array_key_exists( 'display_name', $session ) ? $session['display_name'] : self::display_name( $session ),
			);
		}

		return $context;
	}

	/**
	 * Serialize an API context for storage.
	 *
	 * @param array $context The API context.
	 * @return string
	 */
	private static function to_json( array $context ) {
		return wp_json_encode( $context );
	}

	/**
	 * Whether the session of the API context can still be used for a while.
	 *
	 * @param array $context The API context.
	 * @return bool
	 */
	private static function is_session_active( array $context ) {
		if ( ! isset( $context['session_context']['expiry_time'] ) ) {
			return false;
		}

		$expiry = \DateTime::createFromFormat( self::EXPIRY_TIME_FORMAT, $context['session_context']['expiry_time'], new \DateTimeZone( 'UTC' ) );

		return $expiry && $expiry->getTimestamp() - time() > self::SESSION_RENEWAL_MARGIN;
	}

	/**
	 * The display name of a session's user. For an OAuth key (UserApiKey) that is the user that granted access, not
	 * the app that asked for it.
	 *
	 * @param array $users The session's users by type: "UserCompany" etc. as bunq returns them, or "userCompany" etc.
	 *                     as the bunq SDK stored them.
	 * @return string|null
	 */
	private static function display_name( array $users ) {
		$users = array_change_key_case( array_filter( $users, 'is_array' ) );

		if ( isset( $users['userapikey'] ) ) {
			$user = self::anchored_user( $users['userapikey'], 'granted_by_user' );
		} elseif ( isset( $users['usercompany'] ) ) {
			$user = $users['usercompany'];
		} else {
			$user = isset( $users['userperson'] ) ? $users['userperson'] : null;
		}

		return isset( $user['display_name'] ) && is_string( $user['display_name'] ) ? $user['display_name'] : null;
	}

	/**
	 * A user an API key refers to, which bunq wraps in its type: {"UserCompany": {...}}.
	 *
	 * @param array  $user_api_key The UserApiKey.
	 * @param string $field        "granted_by_user" or "requested_by_user".
	 * @return array|null
	 */
	private static function anchored_user( array $user_api_key, $field ) {
		foreach ( isset( $user_api_key[ $field ] ) ? (array) $user_api_key[ $field ] : array() as $user ) {
			if ( is_array( $user ) ) {
				return $user;
			}
		}

		return null;
	}

	/**
	 * The first object of a type in bunq's response list, e.g. {"Response": [{"Id": {...}}, {"Token": {...}}]}.
	 *
	 * @param array  $response The response list.
	 * @param string $type     The type, e.g. "Token".
	 * @return array
	 * @throws RuntimeException When the response has no such object.
	 */
	public static function item( array $response, $type ) {
		foreach ( $response as $item ) {
			if ( isset( $item[ $type ] ) && is_array( $item[ $type ] ) ) {
				return $item[ $type ];
			}
		}

		throw new RuntimeException( 'bunq\'s answer has no ' . $type . '.' ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Escaped where it is shown.
	}

	/**
	 * The id of the object bunq created.
	 *
	 * @param array $response The response list, [{"Id": {"id": 1}}].
	 * @return int
	 */
	public static function id( array $response ) {
		$id = self::item( $response, 'Id' );

		return (int) $id['id'];
	}
}
