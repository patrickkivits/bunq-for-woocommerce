<?php
/**
 * The bunq payment details on the order screen.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Orders;

use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;
use PatrickKivits\BunqForWooCommerce\Gateway\PaymentMethods;
use PatrickKivits\BunqForWooCommerce\Logger;
use PatrickKivits\BunqForWooCommerce\Plugin;
use Throwable;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Shows who paid a bunq order and how in a box on the order screen (with and without High-Performance Order
 * Storage): the payer's name and IBAN, the payment method and a link to the bunq.me payment request. Orders paid
 * before the plugin stored these details get them from bunq once, when the order is opened.
 *
 * The payer's name and IBAN are personal data, so they are part of WooCommerce's personal data export and erasure.
 */
final class PaymentDetails {

	/**
	 * The id of the box.
	 */
	const META_BOX_ID = 'bunq-payment-details';

	/**
	 * Prefix of the transient that holds off asking bunq again for an order whose details could not be fetched.
	 */
	const FETCH_FAILED_TRANSIENT = 'bunq_for_woocommerce_details_failed_';

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Hands out the gateway WooCommerce registered.
	 */
	public function __construct( private readonly Plugin $plugin ) {}

	/**
	 * Hook into WordPress and WooCommerce.
	 */
	public function register_hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 2 );
		add_filter( 'woocommerce_privacy_export_order_personal_data_props', array( $this, 'add_export_props' ), 10, 2 );
		add_filter( 'woocommerce_privacy_export_order_personal_data_prop', array( $this, 'export_prop' ), 10, 3 );
		add_filter( 'woocommerce_privacy_remove_order_personal_data_meta', array( $this, 'add_erased_meta' ) );
	}

	/**
	 * The screen id of the order edit screen: that of High-Performance Order Storage when it is on.
	 *
	 * @return string
	 */
	public static function order_screen_id() {
		return function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
	}

	/**
	 * Add the box to the screen of an order paid with bunq (add_meta_boxes).
	 *
	 * @param string                 $screen_id     The screen (the post type without High-Performance Order Storage).
	 * @param \WP_Post|WC_Order|null $post_or_order The post of the order, or the order.
	 */
	public function add_meta_box( $screen_id, $post_or_order = null ) {
		if ( self::order_screen_id() !== $screen_id ) {
			return;
		}

		$order = self::order( $post_or_order );

		if ( ! $order || ! Gateway::is_bunq_gateway_id( $order->get_payment_method() ) ) {
			return;
		}

		add_meta_box( self::META_BOX_ID, __( 'bunq payment', 'bunq-for-woocommerce' ), array( $this, 'render' ), $screen_id, 'side', 'default' );
	}

	/**
	 * Draw the box.
	 *
	 * @param \WP_Post|WC_Order $post_or_order The post of the order, or the order.
	 */
	public function render( $post_or_order ) {
		$order = self::order( $post_or_order );

		if ( ! $order ) {
			return;
		}

		$this->fetch_missing_details( $order );

		echo self::html( $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped while drawn.
	}

	/**
	 * The content of the box.
	 *
	 * @param WC_Order $order The order.
	 * @return string
	 */
	public static function html( $order ) {
		$payment_id = (string) $order->get_meta( Gateway::META_PAYMENT_ID );
		$url        = (string) $order->get_meta( Gateway::META_PAYMENT_REQUEST_URL );
		$rows       = array();

		if ( '' !== $payment_id ) {
			$rows[] = array( __( 'Payer', 'bunq-for-woocommerce' ), (string) $order->get_meta( Gateway::META_PAYER_NAME ) );
			$rows[] = array( __( 'IBAN', 'bunq-for-woocommerce' ), self::format_iban( (string) $order->get_meta( Gateway::META_PAYER_IBAN ) ) );
			$rows[] = array( __( 'Method', 'bunq-for-woocommerce' ), PaymentMethods::payment_type_label( (string) $order->get_meta( Gateway::META_PAYMENT_TYPE ) ) );
			$rows[] = array( __( 'Payment ID', 'bunq-for-woocommerce' ), $payment_id );
		}

		$rows = array_filter(
			$rows,
			fn( $row ) => '' !== $row[1]
		);

		$html = '';

		if ( '' === $payment_id ) {
			$html .= '<p>' . esc_html__( 'No bunq payment has been received for this order yet.', 'bunq-for-woocommerce' ) . '</p>';
		} elseif ( $rows ) {
			$html .= '<table class="bunq-payment-details" style="width:100%;border-collapse:collapse"><tbody>';
			foreach ( $rows as $row ) {
				$html .= '<tr><th scope="row" style="text-align:left;padding:2px 8px 2px 0;vertical-align:top;white-space:nowrap">' . esc_html( $row[0] ) . '</th>'
					. '<td style="padding:2px 0;word-break:break-word">' . esc_html( $row[1] ) . '</td></tr>';
			}
			$html .= '</tbody></table>';
		}

		if ( '' !== $url ) {
			$html .= '<p><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">'
				. esc_html__( 'View the payment request on bunq.me', 'bunq-for-woocommerce' ) . '</a></p>';
		}

		return $html;
	}

	/**
	 * Ask bunq for the payer and payment method of an order paid before the plugin stored them. A failure is logged
	 * and not retried for a while, so the order screen does not wait for bunq every time it is opened.
	 *
	 * @param WC_Order $order The order.
	 */
	public function fetch_missing_details( $order ) {
		if ( ! intval( $order->get_meta( Gateway::META_PAYMENT_ID ) ) || '' !== (string) $order->get_meta( Gateway::META_PAYMENT_TYPE ) ) {
			return;
		}

		$transient = self::FETCH_FAILED_TRANSIENT . $order->get_id();

		if ( get_transient( $transient ) ) {
			return;
		}

		$gateway = $this->plugin->gateway();

		if ( ! $gateway || ! $gateway->get_setting( 'api_context' ) ) {
			return;
		}

		try {
			if ( $gateway->fetch_payment_details( $order ) ) {
				return;
			}
		} catch ( Throwable $exception ) {
			Logger::log( $exception );
		}

		set_transient( $transient, 1, 15 * MINUTE_IN_SECONDS );
	}

	/**
	 * An IBAN in groups of four characters, as it is usually written.
	 *
	 * @param string $iban The IBAN.
	 * @return string
	 */
	public static function format_iban( $iban ) {
		$iban = strtoupper( preg_replace( '/\s+/', '', (string) $iban ) );

		return trim( chunk_split( $iban, 4, ' ' ) );
	}

	/**
	 * Add the payer's name and IBAN to the personal data export of an order paid with bunq
	 * (woocommerce_privacy_export_order_personal_data_props).
	 *
	 * @param string[] $props Labels by property.
	 * @param WC_Order $order The order.
	 * @return string[]
	 */
	public function add_export_props( $props, $order ) {
		if ( $order instanceof WC_Order && Gateway::is_bunq_gateway_id( $order->get_payment_method() ) ) {
			$props[ Gateway::META_PAYER_NAME ] = __( 'bunq payer name', 'bunq-for-woocommerce' );
			$props[ Gateway::META_PAYER_IBAN ] = __( 'bunq payer IBAN', 'bunq-for-woocommerce' );
		}

		return $props;
	}

	/**
	 * The value of a property added by add_export_props() (woocommerce_privacy_export_order_personal_data_prop).
	 *
	 * @param mixed    $value The value WooCommerce found.
	 * @param string   $prop  The property.
	 * @param WC_Order $order The order.
	 * @return mixed
	 */
	public function export_prop( $value, $prop, $order ) {
		if ( in_array( $prop, array( Gateway::META_PAYER_NAME, Gateway::META_PAYER_IBAN ), true ) && $order instanceof WC_Order ) {
			return (string) $order->get_meta( $prop );
		}

		return $value;
	}

	/**
	 * Erase the payer's name and IBAN with the rest of the order's personal data
	 * (woocommerce_privacy_remove_order_personal_data_meta).
	 *
	 * @param string[] $meta Data types by meta key.
	 * @return string[]
	 */
	public function add_erased_meta( $meta ) {
		$meta[ Gateway::META_PAYER_NAME ] = 'text';
		$meta[ Gateway::META_PAYER_IBAN ] = 'text';

		return $meta;
	}

	/**
	 * The order of an order screen.
	 *
	 * @param mixed $post_or_order The post of the order, or the order.
	 * @return WC_Order|null
	 */
	private static function order( $post_or_order ) {
		if ( $post_or_order instanceof WC_Order ) {
			return $post_or_order;
		}

		if ( is_object( $post_or_order ) && isset( $post_or_order->ID ) ) {
			$order = wc_get_order( $post_or_order->ID );

			return $order instanceof WC_Order ? $order : null;
		}

		return null;
	}
}
