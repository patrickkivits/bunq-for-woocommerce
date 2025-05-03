<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Bunq_WooCommerce_Block_Checkout extends AbstractPaymentMethodType {

    private $gateway;

    protected $name = 'bunq';

    public function initialize() {
        $this->gateway = new WC_Bunq_Gateway();
    }

    public function is_active() {
        return isset($this->gateway->enabled) && $this->gateway->enabled === 'yes';
    }

    public function get_payment_method_script_handles() {

        wp_register_script(
            'wc-bunq-blocks-integration',
            plugin_dir_url(__FILE__) . 'block/checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
            ],
            null,
            true
        );

        return [ 'wc-bunq-blocks-integration' ];
    }

    public function get_payment_method_data() {
        global $woocommerce;

        $enabled_payment_methods_setting = $this->gateway->settings['enabled_payment_methods'] ?? null;
        if(is_array($enabled_payment_methods_setting) && !empty($enabled_payment_methods_setting)) {
            $enabled_payment_methods = array_filter($this->gateway->payment_methods, function($payment_method) use ($enabled_payment_methods_setting) {
                return in_array($payment_method['id'], $enabled_payment_methods_setting);
            });
        } else {
            $enabled_payment_methods = $this->gateway->payment_methods;
        }

        $total = $woocommerce->cart->total;
        $allowed_payment_methods = array_filter($enabled_payment_methods, function($payment_method) use ($total) {
            return $payment_method['min'] <= $total && ($payment_method['max'] === null || $payment_method['max'] >= $total);
        });

        return [
            'id' => $this->gateway->id,
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'payment_methods' => array_column($allowed_payment_methods, 'description', 'id'),
            'direct_gateway' => $this->gateway->direct_gateway,
        ];
    }

}