<?php

namespace BunqTest\Integration;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use PatrickKivits\BunqForWooCommerce\Plugin;
use PatrickKivits\BunqForWooCommerce\Requirements;
use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;
use PatrickKivits\BunqForWooCommerce\Blocks\CheckoutIntegration;

/**
 * What the plugin registers with a real WordPress and WooCommerce once it is loaded.
 */
final class PluginTest extends TestCase
{
    public function testTheRequirementsAreMet()
    {
        $this->assertTrue((new Requirements(BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE))->check());
        $this->assertSame(
            md5(PHP_VERSION . '|' . OPENSSL_VERSION_TEXT),
            get_option(Requirements::OPTION),
            'The successful OpenSSL check is remembered for this PHP build'
        );
    }

    public function testTheGatewayIsRegisteredWithWooCommerce()
    {
        $gateways = WC()->payment_gateways()->payment_gateways();

        $this->assertArrayHasKey('bunq', $gateways);
        $this->assertInstanceOf(Gateway::class, $gateways['bunq']);
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
        $this->assertInstanceOf(CheckoutIntegration::class, $registry->get_registered('bunq'));
    }

    public function testThePaymentConfirmationHooksAreInPlace()
    {
        $monitor = Plugin::instance()->payment_monitor();

        $this->assertSame(5, has_action('template_redirect', array($monitor, 'check_on_return')), 'Before WooCommerce clears the cart at priority 20');
        $this->assertSame(10, has_action('wc_bunq_check_payment', array($monitor, 'run_scheduled_check')));
        $this->assertSame(10, has_action('woocommerce_order_status_cancelled', array($monitor, 'queue_cancellation')));
        $this->assertSame(10, has_action('wc_bunq_cancel_payment_request', array($monitor, 'cancel_payment_request')));
        $this->assertSame(10, has_action('woocommerce_api_wc_bunq_gateway', array(WC()->payment_gateways()->payment_gateways()['bunq'], 'handle_callback')));
    }

    public function testActionSchedulerIsAvailableForTheBackgroundChecks()
    {
        $this->assertTrue(function_exists('as_schedule_single_action'));
        $this->assertTrue(function_exists('as_enqueue_async_action'));
    }
}
