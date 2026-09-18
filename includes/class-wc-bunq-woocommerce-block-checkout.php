<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Bunq_WooCommerce_Block_Checkout extends AbstractPaymentMethodType {

    private $gateway;

    protected $name = 'bunq';

    public function initialize() {
        // Reuse the instance WooCommerce registered; a second one would register the gateway's hooks twice.
        $gateway = function_exists('bunq_get_gateway') ? bunq_get_gateway() : null;
        $this->gateway = $gateway ?: new WC_Bunq_Gateway();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {
        $script_path = plugin_dir_path(__FILE__) . 'block/checkout.js';

        wp_register_script(
            'wc-bunq-blocks-integration',
            plugin_dir_url(__FILE__) . 'block/checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
            ],
            file_exists($script_path) ? (string) filemtime($script_path) : null,
            true
        );

        return [ 'wc-bunq-blocks-integration' ];
    }

    public function get_payment_method_data() {
        // Null (no cart in the block editor, empty cart) means every enabled method is offered.
        $total = $this->gateway->get_checkout_total();

        return [
            'id' => $this->gateway->id,
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'payment_methods' => array_column($this->gateway->get_allowed_payment_methods($total), 'description', 'id'),
            'direct_gateway' => $this->gateway->direct_gateway,
            'supports' => array_values(array_filter($this->gateway->supports, [$this->gateway, 'supports'])),
        ];
    }

}
