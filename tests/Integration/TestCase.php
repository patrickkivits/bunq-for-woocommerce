<?php

namespace BunqTest\Integration;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Utilities\OrderUtil;
use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;

abstract class TestCase extends \WP_UnitTestCase
{
    /** @var array[] Everything the plugin logged through wc_get_logger() (source "bunq") during the test. */
    protected $logs = array();

    public function set_up()
    {
        parent::set_up();

        $this->logs = array();
        add_filter('woocommerce_logger_log_message', array($this, 'recordLog'), 10, 3);

        if (!WC()->cart) {
            wc_load_cart();
        }
        WC()->cart->empty_cart();
        wc_clear_notices();

        // Fresh gateway instances built from this test's (rolled back, so empty) settings.
        WC()->payment_gateways()->init();
    }

    /**
     * Store orders in WooCommerce's own order tables (HPOS, created in bootstrap.php) for the rest of the test, the
     * default of new shops. The test suite installs WooCommerce with the posts table.
     */
    protected function useHighPerformanceOrderStorage()
    {
        update_option(CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes');
        $this->assertTrue(OrderUtil::custom_orders_table_usage_is_enabled());
    }

    public function recordLog($message, $level, $context)
    {
        // WooCommerce logs under its own sources (for example when a gateway is enabled); only the plugin's matter.
        if (isset($context['source']) && $context['source'] === 'bunq') {
            $this->logs[] = array('level' => $level, 'message' => $message);
        }

        return $message;
    }

    /**
     * @param string|null $level Only messages logged at this level, or all of them.
     * @return string[]
     */
    protected function logMessages($level = null)
    {
        $messages = array();

        foreach ($this->logs as $entry) {
            if ($level === null || $entry['level'] === $level) {
                $messages[] = $entry['message'];
            }
        }

        return $messages;
    }

    protected function assertLogged($level, $needle)
    {
        foreach ($this->logMessages($level) as $message) {
            if (strpos($message, $needle) !== false) {
                $this->addToAssertionCount(1);

                return;
            }
        }

        $this->fail(sprintf('No "%s" log message contains "%s". Logged: %s', $level, $needle, json_encode($this->logs)));
    }

    /**
     * Save gateway settings and instantiate the gateway the way WooCommerce does.
     *
     * @param array|null $settings The woocommerce_bunq_settings option; null for a shop that never saved them.
     * @return Gateway
     */
    protected function createGateway(?array $settings = null)
    {
        if ($settings !== null) {
            update_option('woocommerce_bunq_settings', $settings);
        }

        return new Gateway();
    }

    /**
     * The stored API keys and API contexts (kept apart from the settings since 1.7.0).
     *
     * @return array
     */
    protected function credentials()
    {
        return get_option(\PatrickKivits\BunqForWooCommerce\Gateway\Gateway::CREDENTIALS_OPTION, array());
    }

    /**
     * A complete settings array as saved by a shop that finished the setup in live mode. The API keys and contexts
     * are among them the way versions before 1.7.0 stored them; the gateway moves them to their own option.
     */
    protected function liveSettings(array $overrides = array())
    {
        return array_merge(array(
            'enabled' => 'yes',
            'testmode' => 'no',
            'title' => 'iDEAL, Credit Card or Bancontact',
            'description' => 'Pay with iDEAL, Credit Card or Bancontact',
            'monetary_account_bank_id' => '123',
            'direct_gateway' => 'no',
            'oauth_client_id' => 'live-client-id',
            'oauth_client_secret' => 'live-client-secret',
            'api_key' => 'live-api-key',
            'api_context' => '',
            'test_oauth_client_id' => 'test-client-id',
            'test_oauth_client_secret' => 'test-client-secret',
            'test_api_key' => 'test-api-key',
            'test_api_context' => '',
        ), $overrides);
    }

    /**
     * Make this instance the one WooCommerce hands out for the bunq gateway (used by Plugin::gateway()).
     */
    protected function useGateway(Gateway $gateway)
    {
        $gateways = WC()->payment_gateways();

        foreach ($gateways->payment_gateways as $index => $registered) {
            if ($registered->id === 'bunq') {
                $gateways->payment_gateways[$index] = $gateway;

                return;
            }
        }

        $gateways->payment_gateways[] = $gateway;
    }

    /**
     * A real product in the catalogue.
     *
     * @return \WC_Product_Simple
     */
    protected function createProduct($price = '10')
    {
        $product = new \WC_Product_Simple();
        $product->set_name('Test product');
        $product->set_regular_price($price);
        $product->save();

        return $product;
    }

    /**
     * A real, unpaid order for the bunq gateway with a bunq payment request attached.
     *
     * @param array $args Keys: status, payment_method, price, meta.
     * @return \WC_Order
     */
    protected function createOrder(array $args = array())
    {
        $args = array_merge(array(
            'status' => 'pending',
            'payment_method' => 'bunq',
            'price' => '10',
            'meta' => array('bunq_payment_request_id' => 55),
        ), $args);

        $order = wc_create_order();
        $order->add_product($this->createProduct($args['price']), 1);
        $order->set_payment_method($args['payment_method']);
        $order->set_billing_first_name('Jane');
        $order->set_billing_last_name('Doe');
        $order->calculate_totals();
        foreach ($args['meta'] as $key => $value) {
            $order->update_meta_data($key, $value);
        }
        $order->set_status($args['status']);
        $order->save();

        return wc_get_order($order->get_id());
    }

    /**
     * @return string[] The notes the plugin added to the order, oldest first. WooCommerce's own notes (status
     *                  changes, stock) are left out.
     */
    protected function bunqOrderNotes(\WC_Order $order)
    {
        $notes = wc_get_order_notes(array('order_id' => $order->get_id(), 'order' => 'ASC'));
        $contents = array();

        foreach ($notes as $note) {
            if (strpos($note->content, 'bunq') !== false) {
                $contents[] = $note->content;
            }
        }

        return $contents;
    }
}
