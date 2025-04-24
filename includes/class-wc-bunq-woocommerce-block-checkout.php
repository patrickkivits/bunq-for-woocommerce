<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Bunq_WooCommerce_Block_Checkout extends AbstractPaymentMethodType {

	private $gateway;

	protected $name = 'bunq';

	public function initialize() {
		$this->gateway = new WC_Bunq_Gateway();
	}

	public function is_active() {
		return isset( $this->gateway->enabled ) && $this->gateway->enabled === 'yes';
	}

	public function get_payment_method_script_handles() {

		wp_register_script(
			'wc-bunq-blocks-integration',
			plugin_dir_url( __FILE__ ) . 'block/checkout.js',
			array(
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
			),
			null,
			true
		);

		return array( 'wc-bunq-blocks-integration' );
	}

	public function get_payment_method_data() {
		return array(
			'id'          => $this->gateway->id,
			'title'       => $this->gateway->title,
			'description' => $this->gateway->description,
		);
	}
}
