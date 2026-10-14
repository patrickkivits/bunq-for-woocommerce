<?php
/**
 * One payment check per order at a time.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Orders;

use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * The bunq callback, the customer's return and the scheduled checks can ask bunq about the same order at the same
 * moment. The lock is a row in the options table that only one request can insert; it is never read through
 * get_option(), so no cache can hand out a stale copy of it.
 */
final class OrderLock {

	/**
	 * Option name prefix; the order id follows.
	 */
	const OPTION_PREFIX = 'wc_bunq_gateway.lock.';

	/**
	 * The value of each lock this request holds, by order id: its expiry time and a token of this request.
	 *
	 * @var string[]
	 */
	private static $held = array();

	/**
	 * Take the lock of an order.
	 *
	 * @param int $order_id Order id.
	 * @param int $seconds  After this long the lock counts as left behind by a request that died.
	 * @return bool Whether this request holds the lock now.
	 */
	public static function acquire( $order_id, $seconds ) {
		global $wpdb;

		$order_id = (int) $order_id;
		$name     = self::OPTION_PREFIX . $order_id;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery -- The insert must be atomic, which the options API is not.
		$current = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name ) );

		// Remove an expired lock, but only that one: another request may be replacing it at this very moment.
		if ( null !== $current && (int) $current < time() ) {
			$wpdb->delete(
				$wpdb->options,
				array(
					'option_name'  => $name,
					'option_value' => $current,
				)
			);
		}

		$value    = ( time() + $seconds ) . ' ' . wp_generate_uuid4();
		$inserted = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", $name, $value ) );
		// phpcs:enable

		if ( 1 !== (int) $inserted ) {
			return false;
		}

		self::$held[ $order_id ] = $value;

		return true;
	}

	/**
	 * Release the lock of an order, unless it expired and another request took it over.
	 *
	 * @param int $order_id Order id.
	 */
	public static function release( $order_id ) {
		global $wpdb;

		$order_id = (int) $order_id;

		if ( ! isset( self::$held[ $order_id ] ) ) {
			return;
		}

		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->options,
			array(
				'option_name'  => self::OPTION_PREFIX . $order_id,
				'option_value' => self::$held[ $order_id ],
			)
		);
		unset( self::$held[ $order_id ] );
	}

	/**
	 * Whether the order still waits for its payment as it is stored now. Another request may have completed or
	 * cancelled it after this one loaded it, and the caches of this request still hold the order as it was then.
	 *
	 * @param WC_Order $order The order as this request loaded it.
	 * @return bool
	 */
	public static function stored_order_needs_payment( WC_Order $order ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Past every cache on purpose; the table name comes from WooCommerce.
		if ( class_exists( OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$table  = OrderUtil::get_table_for_orders();
			$status = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$table} WHERE id = %d", $order->get_id() ) );
		} else {
			$status = $wpdb->get_var( $wpdb->prepare( "SELECT post_status FROM {$wpdb->posts} WHERE ID = %d", $order->get_id() ) );
		}
		// phpcs:enable

		/** This filter is documented in woocommerce/includes/class-wc-order.php (WC_Order::needs_payment()). */
		$payable = apply_filters( 'woocommerce_valid_order_statuses_for_payment', array( 'pending', 'failed' ), $order ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter, applied as WC_Order::needs_payment() does.

		return in_array( preg_replace( '/^wc-/', '', (string) $status ), (array) $payable, true );
	}
}
