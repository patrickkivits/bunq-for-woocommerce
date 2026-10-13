<?php
/**
 * The block checkout integration.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;
use PatrickKivits\BunqForWooCommerce\Gateway\PaymentMethods;
use PatrickKivits\BunqForWooCommerce\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Offers the gateway in the WooCommerce block checkout.
 */
final class CheckoutIntegration extends AbstractPaymentMethodType {

	/**
	 * The handle of the checkout script.
	 */
	const SCRIPT_HANDLE = 'wc-bunq-blocks-integration';

	/**
	 * The checkout script, relative to the plugin directory.
	 */
	const SCRIPT_PATH = 'assets/js/block-checkout.js';

	/**
	 * The payment method name; matches the gateway id.
	 *
	 * @var string
	 */
	protected $name = Gateway::ID;

	/**
	 * The gateway.
	 *
	 * @var Gateway
	 */
	private $gateway;

	/**
	 * Use the gateway instance WooCommerce registered; a second one would register the gateway's hooks twice.
	 */
	public function initialize() {
		$gateway       = Plugin::instance()->gateway();
		$this->gateway = $gateway ? $gateway : new Gateway();
	}

	/**
	 * Whether the payment method is offered.
	 *
	 * @return bool
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Register the checkout script.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {
		$plugin_file = Plugin::instance()->file();
		$script_path = plugin_dir_path( $plugin_file ) . self::SCRIPT_PATH;

		wp_register_script(
			self::SCRIPT_HANDLE,
			plugin_dir_url( $plugin_file ) . self::SCRIPT_PATH,
			array(
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
			),
			file_exists( $script_path ) ? (string) filemtime( $script_path ) : null,
			true
		);

		return array( self::SCRIPT_HANDLE );
	}

	/**
	 * The settings the checkout script reads (getSetting( 'bunq_data' )).
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		// Null (no cart in the block editor, empty cart) means every enabled method is offered.
		$total = $this->gateway->get_checkout_total();

		return array(
			'id'              => $this->gateway->id,
			'title'           => $this->gateway->title,
			'description'     => $this->gateway->description,
			'payment_methods' => PaymentMethods::options( $this->gateway->get_allowed_payment_methods( $total ) ),
			'direct_gateway'  => $this->gateway->direct_gateway,
			'supports'        => array_values( array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) ) ),
		);
	}
}
