<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Bunq_WooCommerce_Block_Checkout extends AbstractPaymentMethodType {

    private $gateway;

    protected $name = 'bunq';

    public function initialize() {
        $this->gateway = new WC_Bunq_Gateway();
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
        // In the block editor there is no cart; offer every enabled method there.
        $total = function_exists('WC') && WC()->cart ? (float) WC()->cart->total : null;

        return [
            'id' => $this->gateway->id,
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'payment_methods' => array_column($this->gateway->get_allowed_payment_methods($total), 'description', 'id'),
            'direct_gateway' => $this->gateway->direct_gateway,
            'supports' => array_filter($this->gateway->supports, [$this->gateway, 'supports']),
        ];
    }

}
