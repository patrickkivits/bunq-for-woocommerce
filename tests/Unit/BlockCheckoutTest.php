<?php

namespace BunqTest;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;
use PatrickKivits\BunqForWooCommerce\Blocks\CheckoutIntegration;

/**
 * The payment method integration for the WooCommerce block checkout.
 */
final class BlockCheckoutTest extends TestCase
{
    public function testThePaymentMethodIsRegisteredWithTheBlockRegistry()
    {
        $registry = new PaymentMethodRegistry();

        do_action('woocommerce_blocks_payment_method_type_registration', $registry);

        $this->assertArrayHasKey('bunq', $registry->registered);
        $this->assertInstanceOf(CheckoutIntegration::class, $registry->registered['bunq']);
    }

    public function testTheRegisteredGatewayInstanceIsReused()
    {
        $gateway = $this->createGateway($this->liveSettings());
        $this->registerGateway($gateway);

        $method = new CheckoutIntegration();
        $method->initialize();

        $this->assertSame($gateway, $this->gatewayOf($method));
    }

    public function testAGatewayIsCreatedWhenWooCommerceHasNoneRegistered()
    {
        $method = new CheckoutIntegration();
        $method->initialize();

        $this->assertInstanceOf(Gateway::class, $this->gatewayOf($method));
    }

    public function testTheMethodIsActiveWhenTheGatewayIsAvailable()
    {
        $this->registerGateway($this->createGateway($this->liveSettings(array('api_context' => '{"live":1}'))));
        $method = new CheckoutIntegration();
        $method->initialize();
        $this->assertTrue($method->is_active());

        $this->registerGateway($this->createGateway($this->liveSettings()));
        $method = new CheckoutIntegration();
        $method->initialize();
        $this->assertFalse($method->is_active());
    }

    public function testTheCheckoutScriptIsRegisteredWithItsDependencies()
    {
        $method = new CheckoutIntegration();
        $method->initialize();

        $this->assertSame(array('wc-bunq-blocks-integration'), $method->get_payment_method_script_handles());

        $script = Environment::$registeredScripts['wc-bunq-blocks-integration'];
        $this->assertSame('https://shop.example/wp-content/plugins/bunq-for-woocommerce/assets/js/block-checkout.js', $script['src']);
        $this->assertSame(array('wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities'), $script['deps']);
        $this->assertSame((string) filemtime(dirname(__DIR__, 2) . '/assets/js/block-checkout.js'), $script['ver'], 'Cache busting by modification time');
        $this->assertTrue($script['in_footer']);
    }

    public function testThePaymentMethodDataDescribesTheGatewayToTheCheckoutBlock()
    {
        $this->registerGateway($this->createGateway($this->liveSettings(array(
            'title' => 'bunq',
            'description' => 'Pay with bunq',
            'direct_gateway' => 'yes',
            'enabled_payment_methods' => array('ideal', 'card'),
        ))));
        WC()->cart = (object) array('total' => '0.50');
        $method = new CheckoutIntegration();
        $method->initialize();

        $this->assertSame(array(
            'id' => 'bunq',
            'title' => 'bunq',
            'description' => 'Pay with bunq',
            'payment_methods' => array('ideal' => 'iDEAL'),
            'direct_gateway' => true,
            'select_label' => 'Payment method',
            'supports' => array('products', 'refunds'),
        ), $method->get_payment_method_data());
    }

    public function testEveryEnabledPaymentMethodIsOfferedWithoutACart()
    {
        $this->registerGateway($this->createGateway($this->liveSettings(array('direct_gateway' => 'yes'))));
        $method = new CheckoutIntegration();
        $method->initialize();

        $data = $method->get_payment_method_data();

        $this->assertSame(array('card', 'ideal', 'bancontact', 'bunq-transfer'), array_keys($data['payment_methods']));
    }

    private function gatewayOf(CheckoutIntegration $method)
    {
        $property = new \ReflectionProperty(CheckoutIntegration::class, 'gateway');
        $property->setAccessible(true);

        return $property->getValue($method);
    }
}
