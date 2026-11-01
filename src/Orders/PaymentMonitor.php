<?php
/**
 * Payment confirmation outside the bunq callback.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Orders;

use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;
use PatrickKivits\BunqForWooCommerce\Logger;
use PatrickKivits\BunqForWooCommerce\Plugin;
use Throwable;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Confirms payments when the customer returns to the shop and, as a fallback for a callback that never arrives,
 * from Action Scheduler. Also cancels the bunq payment request when the order is cancelled in WooCommerce.
 */
final class PaymentMonitor {

	/**
	 * Action Scheduler hook of the background payment check. Queued actions refer to it, so it must not change.
	 */
	const CHECK_ACTION = 'wc_bunq_check_payment';

	/**
	 * Action Scheduler hook of the cancellation at bunq. Queued actions refer to it, so it must not change.
	 */
	const CANCEL_ACTION = 'wc_bunq_cancel_payment_request';

	/**
	 * Action Scheduler hook of the single check that replaces callbacks arriving in quick succession.
	 */
	const FOLLOW_UP_ACTION = 'bunq_for_woocommerce_follow_up_check';

	/**
	 * Action Scheduler group of the plugin's actions.
	 */
	const ACTION_GROUP = 'bunq';

	/**
	 * Hands out the gateway WooCommerce registered.
	 *
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Hands out the gateway WooCommerce registered.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Hook into WordPress, WooCommerce and Action Scheduler.
	 */
	public function register_hooks() {
		add_action( self::CHECK_ACTION, array( $this, 'run_scheduled_check' ), 10, 2 );
		// Before WooCommerce clears the cart on the same hook (wc_clear_cart_after_payment, priority 20).
		add_action( 'template_redirect', array( $this, 'check_on_return' ), 5 );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'queue_cancellation' ), 10, 2 );
		add_action( self::CANCEL_ACTION, array( $this, 'cancel_payment_request' ) );
		add_action( self::FOLLOW_UP_ACTION, array( $this, 'run_follow_up_check' ) );
	}

	/**
	 * Whether this is an unpaid bunq order with a payment request to check.
	 *
	 * @param mixed $order The order.
	 * @return bool
	 */
	public static function order_awaits_payment( $order ) {
		return $order instanceof WC_Order
			&& Gateway::is_bunq_gateway_id( $order->get_payment_method() )
			&& $order->needs_payment()
			&& intval( $order->get_meta( Gateway::META_PAYMENT_REQUEST_ID ) ) > 0;
	}

	/**
	 * Delays between the scheduled checks of one order. After the last one the order is left to WooCommerce's own
	 * unpaid-order handling.
	 *
	 * @return int[]
	 */
	public static function check_delays() {
		return array(
			10 * MINUTE_IN_SECONDS,
			30 * MINUTE_IN_SECONDS,
			2 * HOUR_IN_SECONDS,
			6 * HOUR_IN_SECONDS,
			DAY_IN_SECONDS,
			2 * DAY_IN_SECONDS,
		);
	}

	/**
	 * Queue a background check of the order's payment request (Action Scheduler ships with WooCommerce).
	 *
	 * @param int $order_id Order id.
	 * @param int $attempt  Number of checks that ran before.
	 */
	public static function schedule_check( $order_id, $attempt = 0 ) {
		if ( ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}

		$delays = self::check_delays();

		if ( ! isset( $delays[ $attempt ] ) ) {
			return;
		}

		as_schedule_single_action( time() + $delays[ $attempt ], self::CHECK_ACTION, array( intval( $order_id ), intval( $attempt ) ), self::ACTION_GROUP );
	}

	/**
	 * Queue one check of the order's payment request, unless one is queued already.
	 *
	 * @param int $order_id Order id.
	 * @param int $delay    Seconds from now.
	 */
	public static function schedule_follow_up_check( $order_id, $delay ) {
		if ( ! function_exists( 'as_schedule_single_action' ) || ! function_exists( 'as_next_scheduled_action' ) ) {
			return;
		}

		$args = array( intval( $order_id ) );

		if ( false !== as_next_scheduled_action( self::FOLLOW_UP_ACTION, $args, self::ACTION_GROUP ) ) {
			return;
		}

		as_schedule_single_action( time() + $delay, self::FOLLOW_UP_ACTION, $args, self::ACTION_GROUP );
	}

	/**
	 * The follow-up check of a callback that arrived while bunq had just been asked.
	 *
	 * @param int $order_id Order id.
	 */
	public function run_follow_up_check( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! self::order_awaits_payment( $order ) ) {
			return;
		}

		$gateway = $this->plugin->gateway();

		if ( ! $gateway ) {
			return;
		}

		try {
			$outcome = $gateway->check_payment_status( $order );
			Logger::log( 'Follow-up check for order #' . $order->get_order_number() . ': ' . $outcome, 'debug' );
		} catch ( Throwable $exception ) {
			Logger::log( $exception );
		}
	}

	/**
	 * The scheduled check: ask bunq for the payment and queue the next check while it is still pending.
	 *
	 * @param int $order_id Order id.
	 * @param int $attempt  Number of checks that ran before.
	 */
	public function run_scheduled_check( $order_id, $attempt = 0 ) {
		$order = wc_get_order( $order_id );

		if ( ! self::order_awaits_payment( $order ) ) {
			return;
		}

		$gateway = $this->plugin->gateway();

		if ( ! $gateway ) {
			return;
		}

		$outcome = 'pending';

		try {
			$outcome = $gateway->check_payment_status( $order );
		} catch ( Throwable $exception ) {
			Logger::log( $exception );
		}

		Logger::log( 'Scheduled check ' . ( $attempt + 1 ) . ' for order #' . $order->get_order_number() . ': ' . $outcome, 'debug' );

		if ( 'pending' === $outcome ) {
			self::schedule_check( $order_id, $attempt + 1 );
		}
	}

	/**
	 * When the customer lands on the order-received page, ask bunq for the payment right away instead of waiting for
	 * the callback.
	 */
	public function check_on_return() {
		if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}

		global $wp;
		$order_id = absint( isset( $wp->query_vars['order-received'] ) ? $wp->query_vars['order-received'] : 0 );
		$order    = $order_id ? wc_get_order( $order_id ) : null;

		if ( ! self::order_awaits_payment( $order ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The order key authorizes the check.
		$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

		if ( ! is_string( $order_key ) || '' === $order_key || ! hash_equals( (string) $order->get_order_key(), $order_key ) ) {
			return;
		}

		$gateway = $this->plugin->gateway();

		if ( ! $gateway ) {
			return;
		}

		try {
			$outcome = $gateway->check_payment_status( $order );
			Logger::log( 'Check on return for order #' . $order->get_order_number() . ': ' . $outcome, 'debug' );
		} catch ( Throwable $exception ) {
			Logger::log( $exception );
		}
	}

	/**
	 * A cancelled order must not be payable any more, so cancel its payment request at bunq as well. The bunq call is
	 * queued so that bulk cancellations (unpaid-order cron, bulk actions) do not make one rate-limited call per order
	 * inside the request that cancels them.
	 *
	 * @param int           $order_id Order id.
	 * @param WC_Order|null $order    The order, when WooCommerce passes it.
	 */
	public function queue_cancellation( $order_id, $order = null ) {
		$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );

		if ( ! $order || ! Gateway::is_bunq_gateway_id( $order->get_payment_method() ) ) {
			return;
		}

		// Nothing to cancel, or bunq already reported the request as cancelled or expired.
		if ( ! intval( $order->get_meta( Gateway::META_PAYMENT_REQUEST_ID ) ) || $order->get_meta( Gateway::META_PAYMENT_REQUEST_STATUS ) ) {
			return;
		}

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::CANCEL_ACTION, array( intval( $order_id ) ), self::ACTION_GROUP );
		} else {
			$this->cancel_payment_request( $order_id );
		}
	}

	/**
	 * Cancel the payment request of a cancelled order at bunq.
	 *
	 * @param int $order_id Order id.
	 */
	public function cancel_payment_request( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order || ! Gateway::is_bunq_gateway_id( $order->get_payment_method() ) ) {
			return;
		}

		$payment_request_id = intval( $order->get_meta( Gateway::META_PAYMENT_REQUEST_ID ) );

		if ( ! $payment_request_id || $order->get_meta( Gateway::META_PAYMENT_REQUEST_STATUS ) ) {
			return;
		}

		$gateway = $this->plugin->gateway();

		if ( ! $gateway ) {
			return;
		}

		try {
			$gateway->cancel_payment_request( $payment_request_id );
			$order->update_meta_data( Gateway::META_PAYMENT_REQUEST_STATUS, 'CANCELLED' );
			$order->save();
			$order->add_order_note(
				sprintf(
					/* translators: %s: bunq payment request id */
					__( 'bunq payment request %s cancelled', 'bunq-for-woocommerce' ),
					$payment_request_id
				)
			);
			Logger::log( 'bunqme-tab ' . $payment_request_id . ' cancelled for order #' . $order->get_order_number(), 'info' );
		} catch ( Throwable $exception ) {
			Logger::log( $exception, 'warning' );
		}
	}
}
