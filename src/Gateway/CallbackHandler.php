<?php
/**
 * The bunq callback.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Gateway;

use PatrickKivits\BunqForWooCommerce\Bunq\ApiClient;
use PatrickKivits\BunqForWooCommerce\Logger;
use PatrickKivits\BunqForWooCommerce\Orders\PaymentMonitor;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the callback bunq sends for a bunq.me payment request (notification filter BUNQME_TAB). The order is
 * brought in line with the payment request as fetched from bunq; the notification body itself is not trusted.
 */
final class CallbackHandler {

	/**
	 * The WooCommerce API request the callback URL points at (/wc-api/wc_bunq_gateway/). It is registered at bunq,
	 * so it must not change.
	 */
	const API_REQUEST = 'wc_bunq_gateway';

	/**
	 * Anyone can call the callback URL, and every call makes the shop ask bunq. At most one lookup per payment
	 * request runs within this many seconds; a callback within that window queues one follow-up check instead, so a
	 * genuine notification is never lost.
	 */
	const THROTTLE_SECONDS = 10;

	/**
	 * Prefix of the transient that marks a recent lookup, per payment request.
	 */
	const THROTTLE_TRANSIENT_PREFIX = 'bunq_for_woocommerce_callback_';

	/**
	 * The gateway that checks the payment status.
	 *
	 * @var Gateway
	 */
	private $gateway;

	/**
	 * Constructor.
	 *
	 * @param Gateway $gateway The gateway that checks the payment status.
	 */
	public function __construct( Gateway $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * The callback URL bunq calls.
	 *
	 * @return string
	 */
	public static function url() {
		return WC()->api_request_url( self::API_REQUEST );
	}

	/**
	 * Handle a notification.
	 *
	 * @param string $body The raw request body.
	 * @return string What happened: "ignored" (not a bunq.me notification), "unknown" (not exactly one order
	 *                belongs to the payment request), "settled" (the order did not need payment), "deferred" (bunq was
	 *                asked moments ago, a follow-up check is queued), "error" (bunq could not be reached) or the outcome
	 *                of Gateway::check_payment_status().
	 */
	public function handle( $body ) {
		$notification = $body ? json_decode( $body ) : null;

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- bunq's JSON.
		$category           = isset( $notification->NotificationUrl->category ) ? $notification->NotificationUrl->category : null;
		$payment_request_id = isset( $notification->NotificationUrl->object->BunqMeTab->id ) ? $notification->NotificationUrl->object->BunqMeTab->id : null;
		// phpcs:enable

		if ( ApiClient::NOTIFICATION_CATEGORY !== $category || ! is_numeric( $payment_request_id ) ) {
			Logger::log( 'bunq callback ignored: category ' . var_export( $category, true ) . ', bunqme-tab id ' . var_export( $payment_request_id, true ), 'debug' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export

			return 'ignored';
		}

		$payment_request_id = (int) $payment_request_id;

		// Two results means the meta data is ambiguous, so stop rather than guess.
		$orders = wc_get_orders(
			array(
				'limit'        => 2,
				'meta_key'     => Gateway::META_PAYMENT_REQUEST_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'   => $payment_request_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_compare' => '=',
			)
		);

		if ( count( $orders ) !== 1 ) {
			Logger::log( 'bunq callback for bunqme-tab ' . $payment_request_id . ': found ' . count( $orders ) . ' orders, expected exactly one', 'warning' );

			return 'unknown';
		}

		$order = $orders[0];

		if ( ! $order->needs_payment() ) {
			Logger::log( 'bunq callback for bunqme-tab ' . $payment_request_id . ': order #' . $order->get_order_number() . ' no longer needs payment (status ' . $order->get_status() . ')', 'debug' );

			return 'settled';
		}

		$throttle = self::THROTTLE_TRANSIENT_PREFIX . $payment_request_id;

		if ( get_transient( $throttle ) ) {
			PaymentMonitor::schedule_follow_up_check( $order->get_id(), self::THROTTLE_SECONDS );
			Logger::log( 'bunq callback for bunqme-tab ' . $payment_request_id . ': bunq was asked less than ' . self::THROTTLE_SECONDS . ' seconds ago, follow-up check queued', 'debug' );

			return 'deferred';
		}

		set_transient( $throttle, 1, self::THROTTLE_SECONDS );

		try {
			$outcome = $this->gateway->check_payment_status( $order );
			Logger::log( 'bunq callback for bunqme-tab ' . $payment_request_id . ': order #' . $order->get_order_number() . ' is ' . $outcome, 'debug' );

			return $outcome;
		} catch ( Throwable $exception ) {
			Logger::log( $exception );

			return 'error';
		}
	}
}
