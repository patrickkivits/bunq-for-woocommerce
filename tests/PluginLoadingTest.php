<?php

namespace BunqTest;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * What the plugin registers with WordPress and WooCommerce when it is loaded.
 */
final class PluginLoadingTest extends TestCase
{
    public function testTheGatewayIsAddedToTheWooCommercePaymentGateways()
    {
        $this->assertSame(array('WC_Bunq_Gateway'), apply_filters('woocommerce_payment_gateways', array()));
        $this->assertSame(array('WC_Gateway_Paypal', 'WC_Bunq_Gateway'), apply_filters('woocommerce_payment_gateways', array('WC_Gateway_Paypal')));
    }

    public function testCompatibilityWithOrderTablesAndTheBlockCheckoutIsDeclared()
    {
        do_action('before_woocommerce_init');

        $this->assertSame(array(
            array('custom_order_tables', BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE, true),
            array('cart_checkout_blocks', BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE, true),
        ), FeaturesUtil::$declared);
    }

    public function testTranslationsAreLoadedFromTheLanguagesFolder()
    {
        do_action('init');

        $this->assertSame(array('bunq-for-woocommerce' => 'bunq-for-woocommerce/languages'), Environment::$loadedTextdomains);
    }

    public function testThePluginFileIsKnownToTheIncludes()
    {
        $this->assertSame(realpath(dirname(__DIR__) . '/bunq-for-woocommerce.php'), realpath(BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE));
    }

    public function testTheVersionInThePluginHeaderIsARelease()
    {
        $header = file_get_contents(BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE, false, null, 0, 2048);

        $this->assertSame(1, preg_match('/^ \* Version: (\S+)$/m', $header, $match));
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $match[1]);
    }

    public function testTheShippedTranslationsAreCompiled()
    {
        foreach (glob(dirname(__DIR__) . '/languages/*.po') as $po) {
            $this->assertFileExists(substr($po, 0, -3) . '.mo', basename($po) . ' has no compiled .mo file');
        }
    }
}
