<?php

namespace BunqTest;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use PatrickKivits\BunqForWooCommerce\Blocks\CheckoutIntegration;
use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;
use PatrickKivits\BunqForWooCommerce\Gateway\MethodGateway;
use PatrickKivits\BunqForWooCommerce\Plugin;
use PatrickKivits\BunqForWooCommerce\Requirements;

/**
 * What the plugin registers with WordPress and WooCommerce when it is loaded.
 */
final class PluginLoadingTest extends TestCase
{
    public function testTheGatewayIsAddedToTheWooCommercePaymentGateways()
    {
        $gateways = apply_filters('woocommerce_payment_gateways', array('WC_Gateway_Paypal'));

        $this->assertSame(array('WC_Gateway_Paypal', Gateway::class), array_slice($gateways, 0, 2));
        $this->assertContainsOnlyInstancesOf(MethodGateway::class, array_slice($gateways, 2), 'The separate payment methods follow');
    }

    public function testTheClassNamesOfEarlierVersionsStillWork()
    {
        $this->assertTrue(class_exists('WC_Bunq_Gateway'));
        $this->assertTrue(is_a('WC_Bunq_Gateway', Gateway::class, true));
        $this->assertTrue(class_exists('wc_bunq_woocommerce_block_checkout'), 'Class names are case-insensitive');
        $this->assertTrue(is_a('WC_Bunq_WooCommerce_Block_Checkout', CheckoutIntegration::class, true));
    }

    /**
     * Earlier versions declared WC_Bunq_Gateway on plugins_loaded; instanceof and type hints do not autoload.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testTheOldGatewayClassNameExistsOncePluginsAreLoaded()
    {
        $this->assertFalse(class_exists('WC_Bunq_Gateway', false));
        $this->assertTrue(class_exists('WC_Bunq_WooCommerce_Block_Checkout', false), 'Defined when the block checkout loaded');

        do_action('plugins_loaded');

        $this->assertTrue(class_exists('WC_Bunq_Gateway', false));
        $this->assertInstanceOf('WC_Bunq_Gateway', new Gateway());
    }

    public function testASecondCopyOfThePluginOnlyAddsANotice()
    {
        Environment::$hooks = array();

        require dirname(__DIR__, 2) . '/pay-with-bunq-for-woocommerce.php';

        $this->assertSame(array('admin_notices'), $this->sortedHookNames());
        ob_start();
        do_action('admin_notices');
        $this->assertStringContainsString('installed twice', ob_get_clean());
    }

    public function testAnIncompatibleEnvironmentOnlyGetsTheDeactivationAndItsNotice()
    {
        Environment::$hooks = array();
        Environment::$options['active_plugins'] = array();

        $this->freshPlugin()->boot();

        $this->assertSame(array('admin_init', 'admin_notices', 'before_woocommerce_init', 'init'), $this->sortedHookNames());

        do_action('admin_init');
        $this->assertSame(array('pay-with-bunq-for-woocommerce/pay-with-bunq-for-woocommerce.php'), Environment::$deactivatedPlugins);
    }

    public function testACompatibleEnvironmentGetsTheGatewayAndItsHooks()
    {
        Environment::$hooks = array();

        $this->freshPlugin()->boot();

        $this->assertSame(array(
            'add_meta_boxes',
            'admin_init',
            'admin_notices',
            'admin_post_bunq_check_connection',
            'before_woocommerce_init',
            'bunq_for_woocommerce_connection_check',
            'bunq_for_woocommerce_follow_up_check',
            'deactivate_pay-with-bunq-for-woocommerce/pay-with-bunq-for-woocommerce.php',
            'init',
            'plugins_loaded',
            'template_redirect',
            'wc_bunq_cancel_payment_request',
            'wc_bunq_check_payment',
            'woocommerce_blocks_loaded',
            'woocommerce_email_classes',
            'woocommerce_gateway_bunq_bancontact_settings_values',
            'woocommerce_gateway_bunq_card_settings_values',
            'woocommerce_gateway_bunq_ideal_settings_values',
            'woocommerce_gateway_bunq_settings_values',
            'woocommerce_order_action_bunq_send_payment_link',
            'woocommerce_order_actions',
            'woocommerce_order_status_cancelled',
            'woocommerce_payment_gateways',
            'woocommerce_privacy_export_order_personal_data_prop',
            'woocommerce_privacy_export_order_personal_data_props',
            'woocommerce_privacy_remove_order_personal_data_meta',
            'woocommerce_rest_prepare_payment_gateway',
            'woocommerce_system_status_report',
        ), $this->sortedHookNames());
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

        $this->assertSame(array('pay-with-bunq-for-woocommerce' => 'pay-with-bunq-for-woocommerce/languages'), Environment::$loadedTextdomains);
    }

    public function testThePluginFileIsKnownToTheIncludes()
    {
        $this->assertSame(realpath(dirname(__DIR__, 2) . '/pay-with-bunq-for-woocommerce.php'), realpath(BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE));
    }

    public function testTheVersionInThePluginHeaderIsARelease()
    {
        $header = file_get_contents(BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE, false, null, 0, 2048);

        $this->assertSame(1, preg_match('/^ \* Version: (\S+)$/m', $header, $match));
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $match[1]);
        $this->assertSame($match[1], Plugin::VERSION, 'Plugin::VERSION follows the header');
    }

    public function testTheWordPressOrgReadmeMatchesThePluginHeader()
    {
        $header = file_get_contents(BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE, false, null, 0, 2048);
        $readme = file_get_contents(dirname(__DIR__, 2) . '/readme.txt');

        $this->assertSame($this->field($header, ' * Version'), $this->field($readme, 'Stable tag'));
        $this->assertStringContainsString('= ' . Plugin::VERSION . " =\n", $readme, 'The changelog covers this version');
        foreach (array('Requires at least', 'Requires PHP') as $field) {
            $this->assertSame($this->field($header, ' * ' . $field), $this->field($readme, $field), $field);
        }
        // Plugin Check refuses "Tested up to" in the plugin header: it belongs in readme.txt only.
        $this->assertStringNotContainsString(' * Tested up to:', $header);
        $this->field($readme, 'Tested up to');
        $this->assertSame(Requirements::MIN_WP_VERSION, $this->field($header, ' * Requires at least'), 'The requirements check enforces the declared minimum');
        $this->assertSame('=== ' . $this->field($header, ' * Plugin Name') . ' ===', strtok($readme, "\n"));
    }

    public function testTheShippedTranslationsAreCompiled()
    {
        foreach (glob(dirname(__DIR__, 2) . '/languages/*.po') as $po) {
            $this->assertFileExists(substr($po, 0, -3) . '.mo', basename($po) . ' has no compiled .mo file');
        }
    }

    /**
     * WordPress before 6.5 takes the end of the translations table from the hash table offset, also when there is no
     * hash table; a file with offset 0 there does not load at all.
     */
    public function testTheShippedTranslationsLoadInOlderWordPressVersions()
    {
        foreach (glob(dirname(__DIR__, 2) . '/languages/*.mo') as $mo) {
            $header = unpack('Vmagic/Vrevision/Vtotal/Voriginals/Vtranslations/Vhash_length/Vhash_addr', file_get_contents($mo, false, null, 0, 28));

            $this->assertSame(0x950412de, $header['magic'], basename($mo));
            $this->assertSame($header['translations'] + 8 * $header['total'], $header['hash_addr'], basename($mo) . ': the hash table offset must end the translations table');
        }
    }

    /**
     * A plugin instance that has not registered anything yet.
     */
    private function freshPlugin()
    {
        $class = new \ReflectionClass(Plugin::class);
        $plugin = $class->newInstanceWithoutConstructor();
        $file = $class->getProperty('file');
        $file->setValue($plugin, BUNQ_FOR_WOOCOMMERCE_PLUGIN_FILE);

        return $plugin;
    }

    private function field($text, $name)
    {
        $this->assertSame(1, preg_match('/^' . preg_quote($name, '/') . ': *(.+?)\s*$/m', $text, $match), $name . ' is missing');

        return $match[1];
    }

    private function sortedHookNames()
    {
        $hooks = array_keys(Environment::$hooks);
        sort($hooks);

        return $hooks;
    }
}
