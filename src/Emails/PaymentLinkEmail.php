<?php
/**
 * The email with the bunq payment link.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Emails;

use PatrickKivits\BunqForWooCommerce\Plugin;
use WC_Email;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Sends the customer the bunq.me link to pay an order with (see Orders\PaymentLink). Merchants can change the subject,
 * heading and text in WooCommerce > Settings > Emails, and the templates in their theme like WooCommerce's own:
 * woocommerce/emails/customer-bunq-payment-link.php and woocommerce/emails/plain/customer-bunq-payment-link.php.
 * Only loaded through woocommerce_email_classes, when WC_Email is available.
 */
class PaymentLinkEmail extends WC_Email {

	/**
	 * The email id; WooCommerce stores the settings under it (woocommerce_customer_bunq_payment_link_settings).
	 */
	const ID = 'customer_bunq_payment_link';

	/**
	 * The bunq.me link of the email being sent.
	 *
	 * @var string
	 */
	public $payment_url = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = self::ID;
		$this->customer_email = true;
		$this->title          = __( 'bunq payment link', 'bunq-for-woocommerce' );
		$this->description    = __( 'Sent to the customer when you choose "Send bunq payment link to customer" on an unpaid order. Holds the bunq.me link to pay the order with.', 'bunq-for-woocommerce' );
		$this->template_html  = 'emails/customer-bunq-payment-link.php';
		$this->template_plain = 'emails/plain/customer-bunq-payment-link.php';
		$this->template_base  = plugin_dir_path( Plugin::instance()->file() ) . 'templates/';
		$this->placeholders   = array(
			'{order_date}'   => '',
			'{order_number}' => '',
		);

		parent::__construct();
	}

	/**
	 * The default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'Pay for your {site_title} order #{order_number}', 'bunq-for-woocommerce' );
	}

	/**
	 * The default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Your payment link for order #{order_number}', 'bunq-for-woocommerce' );
	}

	/**
	 * The default text below the order details.
	 *
	 * @return string
	 */
	public function get_default_additional_content() {
		return __( 'Questions about your order? Just reply to this email.', 'bunq-for-woocommerce' );
	}

	/**
	 * Send the payment link to the customer.
	 *
	 * @param int           $order_id    Order id.
	 * @param WC_Order|null $order       The order.
	 * @param string        $payment_url The bunq.me link.
	 * @return bool Whether the email was sent.
	 */
	public function trigger( $order_id, $order = null, $payment_url = '' ) {
		$this->setup_locale();

		if ( $order_id && ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}

		$sent = false;

		if ( $order instanceof WC_Order ) {
			$this->object                         = $order;
			$this->recipient                      = $order->get_billing_email();
			$this->payment_url                    = (string) $payment_url;
			$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
			$this->placeholders['{order_number}'] = $order->get_order_number();

			if ( $this->is_enabled() && $this->get_recipient() && '' !== $this->payment_url ) {
				$sent = (bool) $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}
		}

		$this->restore_locale();

		return $sent;
	}

	/**
	 * The HTML content.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html( $this->template_html, $this->template_args( false ), '', $this->template_base );
	}

	/**
	 * The plain text content.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, $this->template_args( true ), '', $this->template_base );
	}

	/**
	 * The variables of the templates.
	 *
	 * @param bool $plain_text Whether this is the plain text email.
	 * @return array
	 */
	private function template_args( $plain_text ) {
		return array(
			'order'              => $this->object,
			'payment_url'        => $this->payment_url,
			'email_heading'      => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin'      => false,
			'plain_text'         => $plain_text,
			'email'              => $this,
		);
	}
}
