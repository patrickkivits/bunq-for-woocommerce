<?php
/**
 * Email to the customer with the bunq payment link of an order (HTML).
 *
 * Override this template by copying it to yourtheme/woocommerce/emails/customer-bunq-payment-link.php.
 *
 * @package PatrickKivits\BunqForWooCommerce
 *
 * @var WC_Order $order              The order.
 * @var string   $payment_url        The bunq.me link.
 * @var string   $email_heading      The heading.
 * @var string   $additional_content The text below the order details.
 * @var bool     $sent_to_admin      Always false.
 * @var bool     $plain_text         Always false.
 * @var WC_Email $email              The email.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's email hooks, as in its own templates.

$bunq_for_woocommerce_button_color = get_option( 'woocommerce_email_base_color', '#720eec' );
$bunq_for_woocommerce_button_text  = function_exists( 'wc_light_or_dark' ) ? wc_light_or_dark( $bunq_for_woocommerce_button_color, '#202020', '#ffffff' ) : '#ffffff';

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
	<?php
	/* translators: %s: customer's first name */
	printf( esc_html__( 'Hi %s,', 'bunq-for-woocommerce' ), esc_html( $order->get_billing_first_name() ) );
	?>
</p>
<p>
	<?php
	printf(
		/* translators: 1: order number, 2: order total */
		esc_html__( 'You can pay for order #%1$s of %2$s with the link below. bunq.me lets you choose how to pay, for example with iDEAL | Wero, a card or Bancontact.', 'bunq-for-woocommerce' ),
		esc_html( $order->get_order_number() ),
		wp_kses_post( $order->get_formatted_order_total() )
	);
	?>
</p>
<p style="margin: 24px 0;">
	<a href="<?php echo esc_url( $payment_url ); ?>" style="display: inline-block; padding: 12px 24px; border-radius: 4px; background-color: <?php echo esc_attr( $bunq_for_woocommerce_button_color ); ?>; color: <?php echo esc_attr( $bunq_for_woocommerce_button_text ); ?>; font-weight: bold; text-decoration: none;"><?php esc_html_e( 'Pay now', 'bunq-for-woocommerce' ); ?></a>
</p>
<p>
	<?php esc_html_e( 'Does the button not work? Copy this link into your browser:', 'bunq-for-woocommerce' ); ?>
	<br>
	<a href="<?php echo esc_url( $payment_url ); ?>"><?php echo esc_html( $payment_url ); ?></a>
</p>

<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
