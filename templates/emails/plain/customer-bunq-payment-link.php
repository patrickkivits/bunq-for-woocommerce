<?php
/**
 * Email to the customer with the bunq payment link of an order (plain text).
 *
 * Override this template by copying it to yourtheme/woocommerce/emails/plain/customer-bunq-payment-link.php.
 *
 * @package PatrickKivits\BunqForWooCommerce
 *
 * @var WC_Order $order              The order.
 * @var string   $payment_url        The bunq.me link.
 * @var string   $email_heading      The heading.
 * @var string   $additional_content The text below the order details.
 * @var bool     $sent_to_admin      Always false.
 * @var bool     $plain_text         Always true.
 * @var WC_Email $email              The email.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's email hooks, as in its own templates.

echo "=================================================================\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=================================================================\n\n";

/* translators: %s: customer's first name */
echo sprintf( esc_html__( 'Hi %s,', 'pay-with-bunq-for-woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";

echo sprintf(
	/* translators: 1: order number, 2: order total */
	esc_html__( 'You can pay for order #%1$s of %2$s with the link below. bunq.me lets you choose how to pay, for example with iDEAL | Wero, a card or Bancontact.', 'pay-with-bunq-for-woocommerce' ),
	esc_html( $order->get_order_number() ),
	esc_html( html_entity_decode( wp_strip_all_tags( $order->get_formatted_order_total() ), ENT_QUOTES, get_bloginfo( 'charset' ) ) )
) . "\n\n";

echo esc_url_raw( $payment_url ) . "\n\n";

echo "\n----------------------------------------\n\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
