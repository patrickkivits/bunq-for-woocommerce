<?php
/**
 * The "Send bunq payment link" order action.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Orders;

use PatrickKivits\BunqForWooCommerce\Emails\PaymentLinkEmail;
use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;
use PatrickKivits\BunqForWooCommerce\Logger;
use PatrickKivits\BunqForWooCommerce\Plugin;
use Throwable;
use WC_Admin_Meta_Boxes;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Lets the merchant email the customer a bunq.me link for an unpaid order, for example one taken by phone or sent as
 * an invoice. The order then follows the payment like a checkout payment: through the bunq callback, the customer's
 * return to the shop and the background checks.
 */
final class PaymentLink {

	/**
	 * The order action. WooCommerce runs woocommerce_order_action_{ACTION} when the merchant picks it.
	 */
	const ACTION = 'bunq_send_payment_link';

	/**
	 * The key of the email among WooCommerce's emails (WC()->mailer()->get_emails()).
	 */
	const EMAIL_KEY = 'WC_Email_Bunq_Payment_Link';

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Hands out the gateway WooCommerce registered.
	 */
	public function __construct( private readonly Plugin $plugin ) {}

	/**
	 * Hook into WooCommerce.
	 */
	public function register_hooks() {
		add_filter( 'woocommerce_order_actions', array( $this, 'add_order_action' ), 10, 2 );
		add_action( 'woocommerce_order_action_' . self::ACTION, array( $this, 'send' ) );
		add_filter( 'woocommerce_email_classes', array( $this, 'add_email' ) );
	}

	/**
	 * Whether the order can be paid with a bunq payment link: it needs payment, in euros as bunq.me only takes those.
	 *
	 * @param mixed $order The order.
	 * @return bool
	 */
	public static function can_send( $order ) {
		return $order instanceof WC_Order && $order->needs_payment() && Gateway::CURRENCY === $order->get_currency();
	}

	/**
	 * Offer the action on the edit order screen while bunq is connected and the order can be paid with a link
	 * (woocommerce_order_actions).
	 *
	 * @param array         $actions Order actions by key.
	 * @param WC_Order|null $order   The order; WooCommerce versions before 6.6 leave it out.
	 * @return array
	 */
	public function add_order_action( $actions, $order = null ) {
		if ( ! $order instanceof WC_Order && isset( $GLOBALS['theorder'] ) ) {
			$order = $GLOBALS['theorder'];
		}

		if ( self::can_send( $order ) && $this->connected_gateway() ) {
			$actions[ self::ACTION ] = __( 'Send bunq payment link to customer', 'bunq-for-woocommerce' );
		}

		return $actions;
	}

	/**
	 * Add the payment link email to WooCommerce's emails (woocommerce_email_classes).
	 *
	 * @param array $emails WooCommerce's emails by key.
	 * @return array
	 */
	public function add_email( $emails ) {
		$emails[ self::EMAIL_KEY ] = new PaymentLinkEmail();

		return $emails;
	}

	/**
	 * Get a bunq.me link for the order and email it to the customer (woocommerce_order_action_bunq_send_payment_link).
	 * Problems are shown to the merchant on the edit order screen.
	 *
	 * @param WC_Order $order The order.
	 */
	public function send( $order ) {
		$gateway = $this->connected_gateway();

		if ( ! $gateway ) {
			$this->report_error( __( 'The bunq payment link was not sent: connect your bunq account in the bunq payment settings first.', 'bunq-for-woocommerce' ) );

			return;
		}

		if ( ! self::can_send( $order ) ) {
			$this->report_error( __( 'The bunq payment link was not sent: only unpaid orders in euros can be paid with a bunq payment link.', 'bunq-for-woocommerce' ) );

			return;
		}

		if ( ! $order->get_billing_email() ) {
			$this->report_error( __( 'The bunq payment link was not sent: the order has no billing email address.', 'bunq-for-woocommerce' ) );

			return;
		}

		$email = $this->email();

		if ( ! $email || ! $email->is_enabled() ) {
			$this->report_error( __( 'The bunq payment link was not sent: the "bunq payment link" email is disabled in WooCommerce > Settings > Emails.', 'bunq-for-woocommerce' ) );

			return;
		}

		try {
			$link = $gateway->get_payment_link( $order );
		} catch ( Throwable $exception ) {
			Logger::log( $exception );
			$this->report_error( __( 'The bunq payment link could not be created. The WooCommerce logs (source: bunq) have the details.', 'bunq-for-woocommerce' ) );

			return;
		}

		if ( null === $link['url'] ) {
			$this->report_error( __( 'The bunq payment link was not sent: a payment for this order already came in through bunq. Check the order notes.', 'bunq-for-woocommerce' ) );

			return;
		}

		// The callback and the payment checks only follow orders paid with bunq.
		if ( ! Gateway::is_bunq_gateway_id( $order->get_payment_method() ) ) {
			$order->set_payment_method( $gateway );
			$order->save();
		}

		if ( ! $email->trigger( $order->get_id(), $order, $link['url'] ) ) {
			$this->report_error( __( 'The bunq payment link could not be emailed to the customer. Check that your site can send email.', 'bunq-for-woocommerce' ) );

			return;
		}

		$order->add_order_note(
			sprintf(
				/* translators: %s: customer's email address */
				__( 'bunq payment link sent to %s', 'bunq-for-woocommerce' ),
				$order->get_billing_email()
			)
		);
	}

	/**
	 * The bunq gateway, when it has been connected to a bunq account.
	 *
	 * @return Gateway|null
	 */
	private function connected_gateway() {
		$gateway = $this->plugin->gateway();

		return $gateway && $gateway->get_setting( 'api_context' ) ? $gateway : null;
	}

	/**
	 * The payment link email WooCommerce loaded.
	 *
	 * @return PaymentLinkEmail|null
	 */
	private function email() {
		if ( ! function_exists( 'WC' ) || ! WC()->mailer() ) {
			return null;
		}

		$emails = WC()->mailer()->get_emails();

		return isset( $emails[ self::EMAIL_KEY ] ) && $emails[ self::EMAIL_KEY ] instanceof PaymentLinkEmail ? $emails[ self::EMAIL_KEY ] : null;
	}

	/**
	 * Show a problem to the merchant on the edit order screen after the redirect, and log it.
	 *
	 * @param string $message The problem.
	 */
	private function report_error( $message ) {
		Logger::log( $message, 'warning' );

		if ( class_exists( WC_Admin_Meta_Boxes::class ) ) {
			WC_Admin_Meta_Boxes::add_error( $message );
		}
	}
}
