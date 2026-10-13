<?php
/**
 * The bunq API.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Bunq;

use bunq\Context\ApiContext;
use bunq\Context\BunqContext;
use bunq\Exception\TooManyRequestsException;
use bunq\Model\Core\DeviceServerInternal;
use bunq\Model\Core\NotificationFilterUrlMonetaryAccountInternal;
use bunq\Model\Core\SessionServer;
use bunq\Model\Generated\Endpoint\BunqMeTabApiObject;
use bunq\Model\Generated\Endpoint\BunqMeTabEntryApiObject;
use bunq\Model\Generated\Endpoint\DraftPaymentApiObject;
use bunq\Model\Generated\Endpoint\MonetaryAccountBankApiObject;
use bunq\Model\Generated\Endpoint\MonetaryAccountJointApiObject;
use bunq\Model\Generated\Endpoint\NotificationFilterUrlMonetaryAccountApiObject;
use bunq\Model\Generated\Endpoint\PaymentApiObject;
use bunq\Model\Generated\Object\AmountObject;
use bunq\Model\Generated\Object\DraftPaymentEntryObject;
use bunq\Model\Generated\Object\NotificationFilterUrlObject;
use bunq\Model\Generated\Object\PointerObject;
use bunq\Util\BunqEnumApiEnvironmentType;
use PatrickKivits\BunqForWooCommerce\Gateway\CallbackHandler;
use PatrickKivits\BunqForWooCommerce\Logger;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Every call the plugin makes to bunq, through the bunq SDK. The calls are paced by a RequestThrottler and retried
 * when bunq answers "too many requests".
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

	/**
	 * Paces the calls made within this request.
	 *
	 * @var RequestThrottler
	 */
	private $throttler;

	/**
	 * Constructor.
	 *
	 * @param RequestThrottler|null $throttler Paces the calls; one with bunq's limits by default.
	 */
	public function __construct( ?RequestThrottler $throttler = null ) {
		$this->throttler = $throttler ? $throttler : new RequestThrottler( new RequestThrottlerConfiguration() );
	}

	/**
	 * Run a bunq API call and retry it when bunq answers "too many requests". The throttler paces the calls made
	 * within one request so the limit is normally not hit; this covers the rest (calls from other PHP processes).
	 *
	 * @param callable $callback The API call.
	 * @param int      $attempts Attempts before giving up.
	 * @return mixed What the callback returns.
	 * @throws Throwable What the callback throws, a rate limit error after the last attempt.
	 */
	public function retry( callable $callback, $attempts = 3 ) {
		for ( $attempt = 1; ; $attempt++ ) {
			try {
				return $callback();
			} catch ( TooManyRequestsException $exception ) {
				if ( $attempt >= $attempts ) {
					throw $exception;
				}

				Logger::log( 'bunq rate limit hit, retrying in 3 seconds (attempt ' . $attempt . ' of ' . $attempts . ')', 'warning' );
				sleep( 3 );
			}
		}
	}

	/**
	 * The bunq environment.
	 *
	 * @param bool $testmode Whether to use the sandbox.
	 * @return BunqEnumApiEnvironmentType
	 */
	public static function environment( $testmode ) {
		return $testmode ? BunqEnumApiEnvironmentType::SANDBOX() : BunqEnumApiEnvironmentType::PRODUCTION();
	}

	/**
	 * Create a new API context (installation, device-server and session-server at bunq) and load it.
	 *
	 * @param string $api_key  The API key (the OAuth access token).
	 * @param bool   $testmode Whether to use the sandbox.
	 * @return ApiContext
	 * @throws Throwable When bunq rejects the key.
	 */
	public function create_api_context( $api_key, $testmode ) {
		$this->throttler->ensure_api_limits_are_respected( DeviceServerInternal::ENDPOINT_URL_CREATE, 'POST' );
		$this->throttler->ensure_api_limits_are_respected( SessionServer::ENDPOINT_URL_POST, 'POST' );

		$api_context = ApiContext::create( self::environment( $testmode ), $api_key, self::DEVICE_DESCRIPTION );

		$this->load_api_context_from_json( $api_context->toJson() );

		return $api_context;
	}

	/**
	 * Load a stored API context for the calls in this request, renewing its session when it expired.
	 *
	 * @param string $json The stored API context.
	 * @return string|false The API context to store (it changed when the session was renewed), false when it cannot
	 *                      be used.
	 */
	public function load_api_context_from_json( $json ) {
		if ( ! $json ) {
			return false;
		}

		try {
			$api_context = ApiContext::fromJson( $json );

			try {
				$current_context = BunqContext::getApiContext();
				if ( $current_context->getApiKey() === $api_context->getApiKey() && $current_context->isSessionActive() ) {
					// Already loaded in this request.
					return $json;
				}
			} catch ( Throwable $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// No context loaded yet in this request, this is expected.
			}

			// The loaded context expired: make sure the session is active and load the context with the new session.
			$api_context->ensureSessionActive();

			$this->throttler->ensure_api_limits_are_respected( MonetaryAccountBankApiObject::ENDPOINT_URL_LISTING, 'GET' );
			BunqContext::loadApiContext( $api_context );

			return $api_context->toJson();
		} catch ( Throwable $exception ) {
			Logger::log( $exception );
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
		try {
			$session = ApiContext::fromJson( $json )->getSessionContext();

			// Authorized through OAuth: the user that granted access; with a plain API key: the key's own user.
			if ( $session->getUserApiKeyOrNull() ) {
				$user = $session->getUserApiKeyOrNull()->getGrantedByUser()->getReferencedObject();
			} else {
				$user = $session->getUserCompanyOrNull() ? $session->getUserCompanyOrNull() : $session->getUserPersonOrNull();
			}

			$name = $user ? $user->getDisplayName() : null;

			return is_string( $name ) && '' !== $name ? $name : null;
		} catch ( Throwable $exception ) {
			return null;
		}
	}

	/**
	 * Create a bunq.me payment request.
	 *
	 * @param string   $amount                   Amount.
	 * @param string   $currency                 Currency.
	 * @param string   $description              Shown to the payer.
	 * @param string   $return_url               Where bunq.me sends the payer after paying.
	 * @param int|null $monetary_account_bank_id The receiving account, null for the default account.
	 * @return array The share URL ("url") and the id ("id") of the request.
	 */
	public function create_payment_request( $amount, $currency, $description, $return_url, $monetary_account_bank_id = null ) {
		$entry = new BunqMeTabEntryApiObject( new AmountObject( $amount, $currency ), $description, $return_url );

		$this->throttler->ensure_api_limits_are_respected( BunqMeTabApiObject::ENDPOINT_URL_CREATE, 'POST' );
		$payment_request_id = $this->retry(
			function () use ( $entry, $monetary_account_bank_id ) {
				return BunqMeTabApiObject::create( $entry, $monetary_account_bank_id )->getValue();
			}
		);

		$this->throttler->ensure_api_limits_are_respected( BunqMeTabApiObject::ENDPOINT_URL_READ, 'GET' );
		$payment_request = $this->retry(
			function () use ( $payment_request_id, $monetary_account_bank_id ) {
				return BunqMeTabApiObject::get( $payment_request_id, $monetary_account_bank_id )->getValue();
			}
		);

		return array(
			'url' => $payment_request->getBunqmeTabShareUrl(),
			'id'  => $payment_request->getId(),
		);
	}

	/**
	 * The bank accounts payments can be received on, as options for a select.
	 *
	 * @param mixed $api_context Whether an API context is loaded.
	 * @return array Account labels by monetary account id, with an empty first option that explains the state.
	 */
	public function get_bank_accounts( $api_context ) {
		if ( ! $api_context ) {
			return array( '' => __( 'Connect your bunq account first', 'bunq-for-woocommerce' ) );
		}

		try {
			$bank_accounts = array( '' => __( 'Select a bank account', 'bunq-for-woocommerce' ) );

			foreach ( $this->list_bank_monetary_accounts() as $account ) {
				foreach ( $account->getAlias() as $alias ) {
					if ( 'IBAN' === $alias->getType() ) {
						$bank_accounts[ $account->getId() ] = $alias->getValue() . ' - ' . $account->getDescription();
					}
				}
			}

			foreach ( $this->list_joint_monetary_accounts() as $account ) {
				foreach ( $account->getAlias() as $alias ) {
					if ( 'IBAN' === $alias->getType() ) {
						$bank_accounts[ $account->getId() ] = $alias->getValue() . ' - ' . $account->getDescription() . ' ' . __( '(Joint)', 'bunq-for-woocommerce' );
					}
				}
			}

			return $bank_accounts;
		} catch ( Throwable $exception ) {
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
	 * Fetch a bunq.me payment request.
	 *
	 * @param int      $payment_request_id       The payment request.
	 * @param int|null $monetary_account_bank_id Its account, null for the default account.
	 * @return BunqMeTabApiObject
	 */
	public function get_payment_request( $payment_request_id, $monetary_account_bank_id = null ) {
		$this->throttler->ensure_api_limits_are_respected( BunqMeTabApiObject::ENDPOINT_URL_READ, 'GET' );

		return $this->retry(
			function () use ( $payment_request_id, $monetary_account_bank_id ) {
				return BunqMeTabApiObject::get( $payment_request_id, $monetary_account_bank_id )->getValue();
			}
		);
	}

	/**
	 * Cancel a bunq.me payment request so it can no longer be paid.
	 *
	 * @param int      $payment_request_id       The payment request.
	 * @param int|null $monetary_account_bank_id Its account, null for the default account.
	 */
	public function cancel_payment_request( $payment_request_id, $monetary_account_bank_id = null ) {
		$this->throttler->ensure_api_limits_are_respected( BunqMeTabApiObject::ENDPOINT_URL_UPDATE, 'PUT' );

		$this->retry(
			function () use ( $payment_request_id, $monetary_account_bank_id ) {
				return BunqMeTabApiObject::update( $payment_request_id, $monetary_account_bank_id, 'CANCELLED' );
			}
		);
	}

	/**
	 * Fetch a payment received on a bunq account.
	 *
	 * @param int      $payment_id               The payment.
	 * @param int|null $monetary_account_bank_id Its account, null for the default account.
	 * @return PaymentApiObject
	 */
	public function get_payment( $payment_id, $monetary_account_bank_id = null ) {
		$this->throttler->ensure_api_limits_are_respected( PaymentApiObject::ENDPOINT_URL_READ, 'GET' );

		return $this->retry(
			function () use ( $payment_id, $monetary_account_bank_id ) {
				return PaymentApiObject::get( $payment_id, $monetary_account_bank_id )->getValue();
			}
		);
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
	 * @param float    $amount                   Amount.
	 * @param string   $currency                 Currency.
	 * @param string   $iban                     IBAN to pay to.
	 * @param string   $name                     Account holder name, required by bunq for IBAN pointers.
	 * @param string   $description              Shown to the recipient; sanitized with sanitize_payment_description()
	 *                                           and cut to the 140 characters bunq allows.
	 * @param int|null $monetary_account_bank_id The paying account, null for the default account.
	 * @return int The id of the draft payment.
	 */
	public function create_refund( $amount, $currency, $iban, $name, $description, $monetary_account_bank_id = null ) {
		$entry = new DraftPaymentEntryObject(
			new AmountObject( number_format( (float) $amount, 2, '.', '' ), $currency ),
			new PointerObject( 'IBAN', $iban, $name ),
			mb_substr( self::sanitize_payment_description( $description ), 0, 140 )
		);

		$this->throttler->ensure_api_limits_are_respected( DraftPaymentApiObject::ENDPOINT_URL_CREATE, 'POST' );

		return $this->retry(
			function () use ( $entry, $monetary_account_bank_id ) {
				return $this->create_draft_payment( $entry, $monetary_account_bank_id );
			}
		);
	}

	/**
	 * Register the WooCommerce callback URL for bunq.me payment requests on the monetary account.
	 *
	 * @param int|null $monetary_account_bank_id The account, null for the default account.
	 */
	public function create_notification_filters( $monetary_account_bank_id = null ) {
		$callback_url = CallbackHandler::url();

		// bunq replaces the complete list of URL notification filters of the monetary account with the posted list,
		// so keep the filters that are already there (other integrations) and add ours to them.
		$notification_filters = array();

		try {
			foreach ( $this->get_notification_filters( $monetary_account_bank_id ) as $existing_filter_list ) {
				foreach ( (array) $existing_filter_list->getNotificationFilters() as $existing_filter ) {
					if ( self::NOTIFICATION_CATEGORY === $existing_filter->getCategory() && $existing_filter->getNotificationTarget() === $callback_url ) {
						continue; // Ours, added below.
					}

					// Listed objects also carry response fields (id, created, ...) that must not be posted back.
					$notification_filters[] = new NotificationFilterUrlObject(
						$existing_filter->getCategory(),
						$existing_filter->getNotificationTarget(),
						$existing_filter->getAllUserId(),
						$existing_filter->getAllMonetaryAccountId(),
						$existing_filter->getAllVerificationType()
					);
				}
			}
		} catch ( Throwable $exception ) {
			// Registering our callback matters more than preserving the others; log what happened.
			Logger::log( 'Could not read the existing bunq notification filters, registering only the WooCommerce callback: ' . Logger::format_error( $exception ), 'warning' );
			$notification_filters = array();
		}

		$notification_filters[] = new NotificationFilterUrlObject( self::NOTIFICATION_CATEGORY, $callback_url );

		$this->throttler->ensure_api_limits_are_respected( NotificationFilterUrlMonetaryAccountInternal::ENDPOINT_URL_CREATE, 'POST' );
		$this->save_notification_filters( $monetary_account_bank_id, $notification_filters );
	}

	/**
	 * The URL notification filters of the monetary account.
	 *
	 * @param int|null $monetary_account_bank_id The account, null for the default account.
	 * @return NotificationFilterUrlMonetaryAccountInternal[]
	 */
	public function get_notification_filters( $monetary_account_bank_id = null ) {
		$this->throttler->ensure_api_limits_are_respected( NotificationFilterUrlMonetaryAccountApiObject::ENDPOINT_URL_LISTING, 'GET' );

		return NotificationFilterUrlMonetaryAccountInternal::listing( $monetary_account_bank_id )->getValue();
	}

	/**
	 * The user's bank monetary accounts.
	 *
	 * @return MonetaryAccountBankApiObject[]
	 */
	protected function list_bank_monetary_accounts() {
		$this->throttler->ensure_api_limits_are_respected( MonetaryAccountBankApiObject::ENDPOINT_URL_LISTING, 'GET' );

		return $this->retry(
			function () {
				return MonetaryAccountBankApiObject::listing()->getValue();
			}
		);
	}

	/**
	 * The user's joint monetary accounts.
	 *
	 * @return MonetaryAccountJointApiObject[]
	 */
	protected function list_joint_monetary_accounts() {
		$this->throttler->ensure_api_limits_are_respected( MonetaryAccountJointApiObject::ENDPOINT_URL_LISTING, 'GET' );

		return $this->retry(
			function () {
				return MonetaryAccountJointApiObject::listing()->getValue();
			}
		);
	}

	/**
	 * Post a draft payment that the merchant approves in the bunq app (one accept).
	 *
	 * @param DraftPaymentEntryObject $entry                    The payment.
	 * @param int|null                $monetary_account_bank_id The paying account, null for the default account.
	 * @return int The id of the draft payment.
	 */
	protected function create_draft_payment( DraftPaymentEntryObject $entry, $monetary_account_bank_id ) {
		return DraftPaymentApiObject::create( array( $entry ), 1, $monetary_account_bank_id )->getValue();
	}

	/**
	 * Replace the URL notification filters of the monetary account.
	 *
	 * @param int|null                      $monetary_account_bank_id The account, null for the default account.
	 * @param NotificationFilterUrlObject[] $notification_filters     The complete list of filters.
	 */
	protected function save_notification_filters( $monetary_account_bank_id, array $notification_filters ) {
		NotificationFilterUrlMonetaryAccountInternal::createWithListResponse( $monetary_account_bank_id, $notification_filters );
	}
}
