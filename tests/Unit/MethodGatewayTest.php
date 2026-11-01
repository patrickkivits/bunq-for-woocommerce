<?php

namespace BunqTest;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use PatrickKivits\BunqForWooCommerce\Blocks\CheckoutIntegration;
use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;
use PatrickKivits\BunqForWooCommerce\Gateway\MethodGateway;
use PatrickKivits\BunqForWooCommerce\Orders\PaymentMonitor;
use PatrickKivits\BunqForWooCommerce\Plugin;

/**
 * The payment method logos, and iDEAL | Wero, card and Bancontact as payment options of their own ("Separate
 * payment methods").
 */
final class MethodGatewayTest extends TestCase
{
    const IMAGES = 'https://shop.example/wp-content/plugins/bunq-for-woocommerce/assets/images/';

    // --- Logos --------------------------------------------------------------------------------------------------

    public function testTheBunqGatewayShowsTheLogosOfThePaymentMethodsBunqMeOffers()
    {
        $gateway = $this->createGateway($this->liveSettings());

        $this->assertSame(self::IMAGES . 'ideal-wero.svg', $gateway->icon);
        $this->assertSame(
            '<img src="' . self::IMAGES . 'ideal-wero.svg" alt="iDEAL | Wero" class="bunq-payment-method-icon" />'
            . '<img src="' . self::IMAGES . 'card.svg" alt="Credit or Debit Card" class="bunq-payment-method-icon" />'
            . '<img src="' . self::IMAGES . 'bancontact.svg" alt="Bancontact" class="bunq-payment-method-icon" />',
            $gateway->get_icon()
        );
    }

    public function testWithTheDirectGatewayOnlyTheLogosOfTheMethodsOnOfferAreShown()
    {
        $gateway = $this->createGateway($this->liveSettings(array(
            'direct_gateway' => 'yes',
            'enabled_payment_methods' => array('card', 'bancontact', 'bunq-transfer'),
        )));
        WC()->cart = (object) array('total' => '600.00');

        $this->assertSame(array('bancontact'), array_column($gateway->get_icons($gateway->get_checkout_total()), 'id'), 'Card is over its limit');
        $this->assertSame(self::IMAGES . 'card.svg', $gateway->icon, 'The icon property ignores the cart');
    }

    public function testEveryLogoIsShipped()
    {
        foreach (array('ideal-wero.svg', 'card.svg', 'bancontact.svg') as $file) {
            $this->assertFileExists(dirname(__DIR__, 2) . '/assets/images/' . $file);
        }
    }

    public function testTheLogosCanBeFiltered()
    {
        add_filter('woocommerce_gateway_icon', function ($html, $id) {
            return $id . ':' . substr_count($html, '<img');
        }, 10, 2);

        $this->assertSame('bunq:3', $this->createGateway($this->liveSettings())->get_icon());
        $this->assertSame('bunq_card:1', (new MethodGateway('card'))->get_icon());
    }

    // --- Separate payment methods -------------------------------------------------------------------------------

    public function testSeparatePaymentMethodsAreOffByDefault()
    {
        $gateway = $this->createGateway();

        $this->assertFalse($gateway->separate_gateways);
        $this->assertArrayHasKey('separate_gateways', $gateway->get_form_fields());
        $this->assertSame('no', $gateway->get_form_fields()['separate_gateways']['default']);

        foreach (array('ideal', 'card', 'bancontact') as $method) {
            $this->assertSame('no', (new MethodGateway($method))->enabled);
        }
    }

    public function testEachMethodGatewayDescribesItsPaymentMethod()
    {
        update_option('woocommerce_bunq_settings', $this->liveSettings(array('separate_gateways' => 'yes')));
        $gateway = new MethodGateway('ideal');

        $this->assertSame('bunq_ideal', $gateway->id);
        $this->assertSame('ideal', $gateway->get_payment_method_id());
        $this->assertSame('iDEAL | Wero', $gateway->title);
        $this->assertSame('', $gateway->description);
        $this->assertSame('iDEAL | Wero (bunq)', $gateway->get_method_title());
        $this->assertSame(self::IMAGES . 'ideal-wero.svg', $gateway->icon);
        $this->assertSame('<img src="' . self::IMAGES . 'ideal-wero.svg" alt="iDEAL | Wero" class="bunq-payment-method-icon" />', $gateway->get_icon());
        $this->assertFalse($gateway->has_fields);
        $this->assertSame('yes', $gateway->enabled);
        $this->assertTrue($gateway->supports('refunds'));
        $this->assertSame(array(), $gateway->get_form_fields(), 'Set up on the bunq settings page');
    }

    public function testOnlyIdealCardAndBancontactCanBeSeparate()
    {
        $this->expectException(\InvalidArgumentException::class);

        new MethodGateway('bunq-transfer');
    }

    public function testMethodGatewaysShareTheBunqSettingsAndConnection()
    {
        update_option('woocommerce_bunq_settings', $this->liveSettings(array('separate_gateways' => 'yes', 'api_context' => '{"live":1}')));
        $gateway = new MethodGateway('card');

        $this->assertSame('woocommerce_bunq_settings', $gateway->get_option_key());
        $this->assertSame('{"live":1}', $gateway->api_context);
        $this->assertSame(123, $gateway->get_monetary_account_bank_id());
    }

    public function testMethodGatewaysDoNotHookTheSettingsFormOrTheCallback()
    {
        $gateway = new MethodGateway('card');

        $this->assertFalse(has_action('woocommerce_update_options_payment_gateways_bunq_card', array($gateway, 'process_admin_options')));
        $this->assertFalse(has_action('woocommerce_api_wc_bunq_gateway', array($gateway, 'handle_callback')));
    }

    public function testWithSeparatePaymentMethodsOnlyTheMethodGatewaysAreOffered()
    {
        update_option('woocommerce_bunq_settings', $this->liveSettings(array('api_context' => '{"live":1}')));
        $this->assertTrue((new Gateway())->is_available());
        $this->assertFalse((new MethodGateway('ideal'))->is_available(), 'Off by default');

        update_option('woocommerce_bunq_settings', $this->liveSettings(array('api_context' => '{"live":1}', 'separate_gateways' => 'yes')));
        $this->assertFalse((new Gateway())->is_available());
        foreach (array('ideal', 'card', 'bancontact') as $method) {
            $this->assertTrue((new MethodGateway($method))->is_available(), $method);
        }
    }

    public function testMethodGatewaysFollowTheBunqGatewaysConditions()
    {
        $settings = $this->liveSettings(array('api_context' => '{"live":1}', 'separate_gateways' => 'yes'));

        update_option('woocommerce_bunq_settings', array_merge($settings, array('enabled' => 'no')));
        $this->assertFalse((new MethodGateway('ideal'))->is_available(), 'bunq disabled');

        update_option('woocommerce_bunq_settings', array_merge($settings, array('api_context' => '')));
        $this->assertFalse((new MethodGateway('ideal'))->is_available(), 'Not connected');

        update_option('woocommerce_bunq_settings', $settings);
        update_option('woocommerce_currency', 'USD');
        $this->assertFalse((new MethodGateway('ideal'))->is_available(), 'Not in euros');
    }

    public function testOnlyTheEnabledPaymentMethodsAreOfferedSeparately()
    {
        update_option('woocommerce_bunq_settings', $this->liveSettings(array(
            'api_context' => '{"live":1}',
            'separate_gateways' => 'yes',
            'enabled_payment_methods' => array('ideal', 'bunq-transfer'),
        )));

        $this->assertTrue((new MethodGateway('ideal'))->is_available());
        $this->assertSame('no', (new MethodGateway('card'))->enabled);
        $this->assertFalse((new MethodGateway('card'))->is_available());
        $this->assertFalse((new MethodGateway('bancontact'))->is_available());
    }

    public function testAMethodGatewayIsHiddenWhenBunqDoesNotAcceptTheAmountForIt()
    {
        update_option('woocommerce_bunq_settings', $this->liveSettings(array('api_context' => '{"live":1}', 'separate_gateways' => 'yes')));
        WC()->cart = (object) array('total' => '600.00');

        $this->assertTrue((new MethodGateway('ideal'))->is_available());
        $this->assertTrue((new MethodGateway('bancontact'))->is_available());
        $this->assertFalse((new MethodGateway('card'))->is_available(), 'Card payments go up to 500 euros');
    }

    public function testAMethodGatewayOpensItsPaymentMethodOnBunqMe()
    {
        $bunq = new FakeBunqApi();
        update_option('woocommerce_bunq_settings', $this->liveSettings(array('api_context' => '{"live":1}', 'separate_gateways' => 'yes')));
        $gateway = new MethodGateway('bancontact', $bunq);
        $_POST['wc_bunq_gateway_payment_method'] = 'card';

        $result = $gateway->process_payment(Environment::addOrder(array('total' => '24.95', 'payment_method' => 'bunq_bancontact'))->get_id());

        $this->assertSame(array('result' => 'success', 'redirect' => 'https://bunq.me/shop/24.95/1?paymentMethod=BANCONTACT'), $result);
    }

    public function testOrdersOfMethodGatewaysAreCheckedLikeThoseOfTheBunqGateway()
    {
        foreach (array('bunq', 'bunq_ideal', 'bunq_card', 'bunq_bancontact') as $id) {
            $this->assertTrue(Gateway::is_bunq_gateway_id($id), $id);
            $order = Environment::addOrder(array('payment_method' => $id, 'meta' => array('bunq_payment_request_id' => 55)));
            $this->assertTrue(PaymentMonitor::order_awaits_payment($order), $id);
        }

        $this->assertFalse(Gateway::is_bunq_gateway_id('bunq_bunq-transfer'));
        $this->assertFalse(PaymentMonitor::order_awaits_payment(Environment::addOrder(array('payment_method' => 'paypal', 'meta' => array('bunq_payment_request_id' => 55)))));
    }

    public function testTheMethodGatewaysAreAlwaysRegisteredSoTheirOrdersCanBeRefunded()
    {
        $gateways = apply_filters('woocommerce_payment_gateways', array());

        $this->assertSame(Gateway::class, $gateways[0]);
        $this->assertCount(4, $gateways);
        $this->assertSame(array('bunq_ideal', 'bunq_card', 'bunq_bancontact'), array_map(function ($gateway) {
            return $gateway->id;
        }, array_slice($gateways, 1)));
    }

    public function testPluginFindsARegisteredMethodGatewayById()
    {
        $gateway = new MethodGateway('card');
        Environment::$gateways['bunq_card'] = $gateway;

        $this->assertSame($gateway, Plugin::instance()->gateway('bunq_card'));
        $this->assertNull(Plugin::instance()->gateway());
    }

    // --- Turning a method gateway on or off in WooCommerce's list of payment methods ----------------------------

    public function testTurningOnAMethodGatewayTurnsOnSeparatePaymentMethods()
    {
        update_option('woocommerce_bunq_settings', $this->liveSettings(array('enabled' => 'no')));
        $gateway = new MethodGateway('ideal');

        $gateway->update_option('enabled', 'yes');

        $settings = get_option('woocommerce_bunq_settings');
        $this->assertSame('yes', $settings['enabled']);
        $this->assertSame('yes', $settings['separate_gateways']);
        $this->assertSame(array(), $settings['enabled_payment_methods'], 'Every method stays enabled');
        $this->assertSame('yes', $gateway->enabled);
    }

    public function testTurningOnAMethodGatewayAddsItToTheEnabledPaymentMethods()
    {
        update_option('woocommerce_bunq_settings', $this->liveSettings(array('separate_gateways' => 'yes', 'enabled_payment_methods' => array('ideal'))));

        (new MethodGateway('card'))->update_option('enabled', 'yes');

        $this->assertSame(array('ideal', 'card'), get_option('woocommerce_bunq_settings')['enabled_payment_methods']);
    }

    public function testTurningOffAMethodGatewayLeavesItOutOfTheEnabledPaymentMethods()
    {
        update_option('woocommerce_bunq_settings', $this->liveSettings(array('separate_gateways' => 'yes')));
        $gateway = new MethodGateway('card');

        $gateway->update_option('enabled', 'no');

        $settings = get_option('woocommerce_bunq_settings');
        $this->assertSame(array('ideal', 'bancontact', 'bunq-transfer'), array_values($settings['enabled_payment_methods']));
        $this->assertSame('yes', $settings['enabled'], 'bunq stays enabled');
        $this->assertSame('yes', $settings['separate_gateways']);
        $this->assertSame('no', $gateway->enabled);
    }

    public function testTurningOffTheLastMethodGatewayGoesBackToTheSingleBunqOption()
    {
        update_option('woocommerce_bunq_settings', $this->liveSettings(array('separate_gateways' => 'yes', 'enabled_payment_methods' => array('ideal', 'bunq-transfer'))));

        (new MethodGateway('ideal'))->update_option('enabled', 'no');

        $settings = get_option('woocommerce_bunq_settings');
        $this->assertSame('no', $settings['separate_gateways']);
        $this->assertSame(array('ideal', 'bunq-transfer'), $settings['enabled_payment_methods']);
        $this->assertSame('yes', $settings['enabled']);
    }

    public function testOtherSettingsOfAMethodGatewayAreTheBunqGateways()
    {
        update_option('woocommerce_bunq_settings', $this->liveSettings());

        (new MethodGateway('ideal'))->update_option('title', 'Pay');

        $this->assertSame('Pay', get_option('woocommerce_bunq_settings')['title']);
    }

    public function testTheSettingsPageOfAMethodGatewayLinksToTheBunqSettings()
    {
        ob_start();
        (new MethodGateway('bancontact'))->admin_options();
        $html = ob_get_clean();

        $this->assertStringContainsString('<h2>Bancontact (bunq)</h2>', $html);
        $this->assertStringContainsString('href="' . Gateway::settings_url() . '"', $html);
    }

    // --- Block checkout -----------------------------------------------------------------------------------------

    public function testEachMethodGatewayIsRegisteredWithTheBlockRegistry()
    {
        $registry = new PaymentMethodRegistry();

        do_action('woocommerce_blocks_payment_method_type_registration', $registry);

        $this->assertSame(array('bunq', 'bunq_ideal', 'bunq_card', 'bunq_bancontact'), array_keys($registry->registered));
        $this->assertSame('bunq_card', $registry->registered['bunq_card']->get_name());
    }

    public function testTheBlockCheckoutDataOfAMethodGateway()
    {
        update_option('woocommerce_bunq_settings', $this->liveSettings(array('api_context' => '{"live":1}', 'separate_gateways' => 'yes')));
        Environment::$gateways['bunq_ideal'] = new MethodGateway('ideal');
        $method = new CheckoutIntegration('bunq_ideal');
        $method->initialize();

        $this->assertTrue($method->is_active());
        $data = $method->get_payment_method_data();
        $this->assertSame('bunq_ideal', $data['id']);
        $this->assertSame('iDEAL | Wero', $data['title']);
        $this->assertSame('', $data['description']);
        $this->assertFalse($data['direct_gateway']);
        $this->assertSame(array(array('id' => 'ideal', 'src' => self::IMAGES . 'ideal-wero.svg', 'alt' => 'iDEAL | Wero')), $data['icons']);
    }

    public function testABlockCheckoutMethodCreatesItsGatewayWhenWooCommerceHasNoneRegistered()
    {
        $method = new CheckoutIntegration('bunq_bancontact');
        $method->initialize();

        $property = new \ReflectionProperty(CheckoutIntegration::class, 'gateway');
        $property->setAccessible(true);
        $gateway = $property->getValue($method);

        $this->assertInstanceOf(MethodGateway::class, $gateway);
        $this->assertSame('bunq_bancontact', $gateway->id);
    }
}
