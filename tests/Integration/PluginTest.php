<?php

namespace BunqTest\Integration;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WC_Bunq_Gateway;
use WC_Bunq_WooCommerce_Block_Checkout;

/**
 * What the plugin registers with a real WordPress and WooCommerce once it is loaded.
 */
final class PluginTest extends TestCase
{
    public function testTheRequirementsAreMet()
    {
        $this->assertTrue(bunq_requirements_check());
        $this->assertSame(
            md5(PHP_VERSION . '|' . OPENSSL_VERSION_TEXT),
            get_option(BUNQ_REQUIREMENTS_OPTION),
            'The successful OpenSSL check is remembered for this PHP build'
        );
    }

    public function testTheGatewayIsRegisteredWithWooCommerce()
    {
        $gateways = WC()->payment_gateways()->payment_gateways();

        $this->assertArrayHasKey('bunq', $gateways);
        $this->assertInstanceOf(WC_Bunq_Gateway::class, $gateways['bunq']);
        $this->assertSame('bunq', $gateways['bunq']->get_method_title());
        $this->assertTrue($gateways['bunq']->supports('products'));
        $this->assertTrue($gateways['bunq']->supports('refunds'));
    }

    public function testTheGatewayIsNotOfferedAtCheckoutBeforeTheBunqAuthorization()
    {
        $this->assertArrayNotHasKey('bunq', WC()->payment_gateways()->get_available_payment_gateways());
    }

    public function testTheGatewayIsOfferedAtCheckoutOnceAuthorizedAndEnabled()
    {
        update_option('woocommerce_bunq_settings', $this->liveSettings(array('api_context' => 'saved-api-context')));
        WC()->payment_gateways()->init();

        $this->assertArrayHasKey('bunq', WC()->payment_gateways()->get_available_payment_gateways());
    }

    public function testCompatibilityWithOrderTablesAndTheBlockCheckoutIsDeclared()
    {
        $plugin = plugin_basename(BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE);

        foreach (array('custom_order_tables', 'cart_checkout_blocks') as $feature) {
            $compatible = FeaturesUtil::get_compatible_plugins_for_feature($feature);

            $this->assertContains($plugin, $compatible['compatible'], $feature);
            $this->assertNotContains($plugin, $compatible['incompatible'], $feature);
        }
    }

    public function testTheBlockCheckoutPaymentMethodIsRegistered()
    {
        if (!did_action('woocommerce_blocks_payment_method_type_registration')) {
            $this->markTestSkipped('WooCommerce Blocks did not register payment methods in this context');
        }

        $registry = Package::container()->get(PaymentMethodRegistry::class);

        $this->assertTrue($registry->is_registered('bunq'));
        $this->assertInstanceOf(WC_Bunq_WooCommerce_Block_Checkout::class, $registry->get_registered('bunq'));
    }

    public function testThePaymentConfirmationHooksAreInPlace()
    {
        $this->assertSame(5, has_action('template_redirect', 'bunq_check_payment_on_return'), 'Before WooCommerce clears the cart at priority 20');
        $this->assertSame(10, has_action('wc_bunq_check_payment', 'bunq_scheduled_payment_check'));
        $this->assertSame(10, has_action('woocommerce_order_status_cancelled', 'bunq_cancel_payment_request_for_order'));
        $this->assertSame(10, has_action('wc_bunq_cancel_payment_request', 'bunq_cancel_payment_request_now'));
        $this->assertSame(10, has_action('woocommerce_api_wc_bunq_gateway', array(WC()->payment_gateways()->payment_gateways()['bunq'], 'bunq_callback')));
    }

    public function testActionSchedulerIsAvailableForTheBackgroundChecks()
    {
        $this->assertTrue(function_exists('as_schedule_single_action'));
        $this->assertTrue(function_exists('as_enqueue_async_action'));
    }
}
