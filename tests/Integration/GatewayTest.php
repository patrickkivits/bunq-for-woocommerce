<?php

namespace BunqTest\Integration;

use WC_Bunq_Gateway;

/**
 * The payment gateway on top of the real WooCommerce settings API, cart, orders and notices, up to the point
 * where it would talk to bunq.
 */
final class GatewayTest extends TestCase
{
    public function testDefaultsWhenNothingHasBeenSaved()
    {
        $gateway = $this->createGateway();

        $this->assertSame('no', $gateway->enabled);
        $this->assertFalse($gateway->testmode);
        $this->assertFalse($gateway->direct_gateway);
        $this->assertSame('iDEAL, Credit Card or Bancontact', $gateway->title);
        $this->assertSame('Pay with iDEAL, Credit Card or Bancontact', $gateway->description);
        $this->assertNull($gateway->get_monetary_account_bank_id());
        $this->assertFalse($gateway->is_available());
    }

    public function testTheCredentialsFollowTheTestModeSetting()
    {
        $live = $this->createGateway($this->liveSettings(array('api_context' => 'live-context', 'test_api_context' => 'test-context')));
        $this->assertSame('live-api-key', $live->api_key);
        $this->assertSame('live-client-id', $live->oauth_client_id);
        $this->assertSame('live-context', $live->api_context);
        $this->assertSame('live-context', $live->get_setting('api_context'));
        $this->assertSame(123, $live->get_monetary_account_bank_id());

        $test = $this->createGateway($this->liveSettings(array('testmode' => 'yes', 'api_context' => 'live-context', 'test_api_context' => 'test-context')));
        $this->assertTrue($test->testmode);
        $this->assertSame('test-api-key', $test->api_key);
        $this->assertSame('test-client-id', $test->oauth_client_id);
        $this->assertSame('test-context', $test->api_context);
        $this->assertSame('test-context', $test->get_setting('api_context'));
        $this->assertSame('test-client-secret', $test->get_setting('oauth_client_secret'));
    }

    public function testTheGatewayIsOnlyAvailableOnceAuthorizedWithBunq()
    {
        $this->assertTrue($this->createGateway($this->liveSettings(array('api_context' => 'saved-api-context')))->is_available());
        $this->assertFalse($this->createGateway($this->liveSettings())->is_available(), 'No API context');
        $this->assertFalse($this->createGateway($this->liveSettings(array('enabled' => 'no', 'api_context' => 'saved-api-context')))->is_available(), 'Disabled');

        set_transient(WC_Bunq_Gateway::CONTEXT_FAILED_TRANSIENT, 1, 5 * MINUTE_IN_SECONDS);
        $this->assertFalse($this->createGateway($this->liveSettings(array('api_context' => 'saved-api-context')))->is_available(), 'Recreating the API context failed recently');
    }

    public function testTheSettingsFormShowsTheCredentialsOfTheActiveMode()
    {
        $live = array_keys($this->createGateway($this->liveSettings())->get_form_fields());
        $this->assertContains('oauth_client_id', $live);
        $this->assertContains('api_context', $live);
        $this->assertNotContains('test_oauth_client_id', $live);
        $this->assertNotContains('enabled_payment_methods', $live);

        $test = array_keys($this->createGateway($this->liveSettings(array('testmode' => 'yes', 'direct_gateway' => 'yes')))->get_form_fields());
        $this->assertContains('test_oauth_client_id', $test);
        $this->assertContains('test_api_context', $test);
        $this->assertNotContains('oauth_client_id', $test);
        $this->assertContains('enabled_payment_methods', $test);
    }

    public function testBankAccountsAreOnlyLookedUpOnTheAdminSide()
    {
        $fields = $this->createGateway($this->liveSettings())->get_form_fields();
        $this->assertSame(array(), $fields['monetary_account_bank_id']['options']);
        $this->assertFalse(get_transient(WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT));

        set_current_screen('woocommerce_page_wc-settings');
        $this->assertTrue(is_admin());

        $fields = $this->createGateway($this->liveSettings())->get_form_fields();
        $this->assertSame(array('' => 'API key not valid or not setup yet'), $fields['monetary_account_bank_id']['options']);
        $this->assertSame(array('' => 'API key not valid or not setup yet'), get_transient(WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT));
    }

    public function testSavingTheSettingsThroughWooCommerceKeepsTheAuthorizationWhileTheClientStays()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_context' => 'live-context', 'test_api_context' => 'test-context')));
        set_transient(WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT, array('123' => 'NL00'), HOUR_IN_SECONDS);
        $this->postSettings(array(
            'oauth_client_id' => 'live-client-id',
            'oauth_client_secret' => 'live-client-secret',
            'api_key' => 'live-api-key',
            'api_context' => 'live-context',
        ));

        $gateway->process_admin_options();

        $saved = get_option('woocommerce_bunq_settings');
        $this->assertSame('yes', $saved['enabled']);
        $this->assertSame('123', $saved['monetary_account_bank_id']);
        $this->assertSame('live-api-key', $saved['api_key']);
        $this->assertSame('live-context', $saved['api_context']);
        $this->assertSame('test-api-key', $saved['test_api_key']);
        $this->assertSame('test-context', $saved['test_api_context']);
        $this->assertSame(array('123' => 'NL00'), get_transient(WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT));
    }

    public function testRemovingTheLiveClientForgetsTheLiveAuthorization()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_context' => 'live-context', 'test_api_context' => 'test-context')));
        set_transient(WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT, array('123' => 'NL00'), HOUR_IN_SECONDS);
        $this->postSettings(array(
            'oauth_client_id' => '',
            'oauth_client_secret' => 'live-client-secret',
            'api_key' => 'live-api-key',
            'api_context' => 'live-context',
        ));

        $gateway->process_admin_options();

        $saved = get_option('woocommerce_bunq_settings');
        $this->assertSame('', $saved['api_key']);
        $this->assertSame('', $saved['api_context']);
        $this->assertSame('test-api-key', $saved['test_api_key'], 'The test authorization is unaffected');
        $this->assertSame('test-context', $saved['test_api_context']);
        $this->assertFalse(get_transient(WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT));
    }

    public function testTheFirstSaveOnAFreshInstallDoesNotWarnAboutTheOtherModesClient()
    {
        // Only the fields of the active mode are in the form, so WooCommerce never fills the other mode's
        // defaults; reading them directly raised "Undefined array key" on the first save.
        $gateway = $this->createGateway();
        $this->postSettings(array(
            'oauth_client_id' => 'live-client-id',
            'oauth_client_secret' => 'live-client-secret',
        ));

        $gateway->process_admin_options();

        $saved = get_option('woocommerce_bunq_settings');
        $this->assertSame('live-client-id', $saved['oauth_client_id']);
        $this->assertSame('', $saved['test_api_key']);
        $this->assertSame('', $saved['test_api_context']);

        $_POST = array();
        $gateway = $this->createGateway(array('testmode' => 'yes'));
        $this->postSettings(array(
            'testmode' => '1',
            'test_oauth_client_id' => 'test-client-id',
            'test_oauth_client_secret' => 'test-client-secret',
        ));

        $gateway->process_admin_options();

        $saved = get_option('woocommerce_bunq_settings');
        $this->assertSame('test-client-id', $saved['test_oauth_client_id']);
        $this->assertSame('', $saved['api_key']);
        $this->assertSame('', $saved['api_context']);
    }

    public function testTheCheckoutTotalIsTheCartTotal()
    {
        $gateway = $this->createGateway($this->liveSettings());

        $this->assertNull($gateway->get_checkout_total(), 'Empty cart');

        WC()->cart->add_to_cart($this->createProduct('12.50')->get_id());
        WC()->cart->calculate_totals();

        $this->assertSame(12.5, $gateway->get_checkout_total());
    }

    public function testTheCheckoutTotalIsTheOrderTotalOnThePayForOrderPage()
    {
        $gateway = $this->createGateway($this->liveSettings());
        $order = $this->createOrder(array('price' => '42'));
        WC()->cart->add_to_cart($this->createProduct('12.50')->get_id());
        WC()->cart->calculate_totals();

        $GLOBALS['wp']->query_vars['order-pay'] = $order->get_id();
        set_query_var('order-pay', $order->get_id());
        $this->assertTrue(is_wc_endpoint_url('order-pay'));

        $this->assertSame(42.0, $gateway->get_checkout_total());
    }

    public function testTheDirectGatewayOnlyOffersThePaymentMethodsThatFitTheCartTotal()
    {
        $gateway = $this->createGateway($this->liveSettings(array('direct_gateway' => 'yes', 'description' => 'Pay with bunq')));
        WC()->cart->add_to_cart($this->createProduct('0.50')->get_id());
        WC()->cart->calculate_totals();

        ob_start();
        $gateway->payment_fields();
        $html = ob_get_clean();

        $this->assertStringContainsString('<p>Pay with bunq</p>', $html);
        $this->assertStringContainsString('name="wc_bunq_gateway_payment_method"', $html);
        $this->assertStringContainsString('value="ideal"', $html);
        $this->assertStringContainsString('value="bunq-transfer"', $html);
        $this->assertStringNotContainsString('value="card"', $html, 'Cards need at least 1.00');
        $this->assertStringNotContainsString('value="bancontact"', $html, 'Bancontact needs at least 5.00');
    }

    public function testAnUnknownPaymentMethodIsRejectedWithACheckoutNotice()
    {
        $gateway = $this->createGateway($this->liveSettings(array('direct_gateway' => 'yes')));

        $_POST['wc_bunq_gateway_payment_method'] = 'paypal';
        $this->assertFalse($gateway->validate_fields());
        $this->assertSame(array('Payment method invalid'), wp_list_pluck(wc_get_notices('error'), 'notice'));

        wc_clear_notices();
        $_POST['wc_bunq_gateway_payment_method'] = 'ideal';
        $this->assertTrue($gateway->validate_fields());
        $this->assertSame(array(), wc_get_notices('error'));
    }

    public function testAPaymentFailsGracefullyWithoutAnApiContext()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_key' => '')));
        $order = $this->createOrder(array('meta' => array()));

        $result = $gateway->process_payment($order->get_id());

        $this->assertSame(array('result' => 'failure'), $result);
        $this->assertSame(
            array('The payment could not be started with bunq. Please try again or choose another payment method.'),
            wp_list_pluck(wc_get_notices('error'), 'notice')
        );
        $this->assertLogged('error', 'bunq API context is not available');

        $order = wc_get_order($order->get_id());
        $this->assertSame('', $order->get_meta('bunq_payment_request_id'));
        $this->assertSame('pending', $order->get_status());
        $this->assertSame(array(), $this->bunqOrderNotes($order));
        $this->assertFalse(as_has_scheduled_action('wc_bunq_check_payment', array($order->get_id(), 0), 'bunq'));
        $this->assertSame(1, get_transient(WC_Bunq_Gateway::CONTEXT_FAILED_TRANSIENT), 'The failed rebuild is remembered');
    }

    public function testARefundNeedsABunqPaymentAndAPositiveAmount()
    {
        $gateway = $this->createGateway($this->liveSettings());
        $order = $this->createOrder(array('status' => 'processing', 'meta' => array()));

        $error = $gateway->process_refund($order->get_id(), 5);
        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertSame('bunq_refund', $error->get_error_code());
        $this->assertStringContainsString('no bunq payment that can be refunded', $error->get_error_message());

        $order->update_meta_data('bunq_payment_id', 77);
        $order->save();

        $error = $gateway->process_refund($order->get_id(), 0);
        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertStringContainsString('greater than zero', $error->get_error_message());
    }

    public function testARefundReportsWhyBunqCouldNotBeReached()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_key' => '')));
        $order = $this->createOrder(array('status' => 'processing', 'meta' => array('bunq_payment_id' => 77)));

        $error = $gateway->process_refund($order->get_id(), '5.00', 'Damaged');

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertStringStartsWith('bunq refund failed: bunq API context is not available', $error->get_error_message());
        $this->assertSame(array(), $this->bunqOrderNotes(wc_get_order($order->get_id())));
    }

    public function testCheckingAnOrderThatNoLongerNeedsPaymentIsSettled()
    {
        $gateway = $this->createGateway($this->liveSettings());

        $this->assertSame('settled', $gateway->check_payment_status($this->createOrder(array('status' => 'processing'))));
        $this->assertSame('settled', $gateway->check_payment_status($this->createOrder(array('status' => 'completed'))));
        $this->assertSame(array(), $this->logs);
    }

    public function testCheckingAnOrderWithoutAPaymentRequestFails()
    {
        $gateway = $this->createGateway($this->liveSettings());
        $order = $this->createOrder(array('meta' => array()));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('has no bunq payment request');

        $gateway->check_payment_status($order);
    }

    public function testOnlyOneCheckOfAnOrderRunsAtATime()
    {
        $gateway = $this->createGateway($this->liveSettings());
        $order = $this->createOrder();
        set_transient('wc_bunq_gateway.lock.' . $order->get_id(), 1, 30);

        $this->assertSame('pending', $gateway->check_payment_status($order));
        $this->assertSame(array(), $this->logs, 'bunq was not contacted');
    }

    public function testTheCheckLockIsReleasedWhenBunqCannotBeReached()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_key' => '')));
        $order = $this->createOrder();

        try {
            $gateway->check_payment_status($order);
            $this->fail('No API context should have been available');
        } catch (\Exception $exception) {
            $this->assertStringContainsString('bunq API context is not available', $exception->getMessage());
        }

        $this->assertFalse(get_transient('wc_bunq_gateway.lock.' . $order->get_id()));
        $this->assertSame('pending', wc_get_order($order->get_id())->get_status());
    }

    public function testLoadingAnUnusableApiContextFailsAndKeepsTheSetting()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_context' => 'not json')));

        $this->assertFalse($gateway->load_api_context());
        $this->assertSame('not json', get_option('woocommerce_bunq_settings')['api_context']);
        $this->assertLogged('error', 'json_decode error');
    }

    /**
     * Fill $_POST the way the WooCommerce settings form posts the gateway's fields.
     */
    private function postSettings(array $fields)
    {
        $_POST = array(
            'woocommerce_bunq_enabled' => '1',
            'woocommerce_bunq_title' => 'iDEAL, Credit Card or Bancontact',
            'woocommerce_bunq_description' => 'Pay with iDEAL, Credit Card or Bancontact',
            'woocommerce_bunq_monetary_account_bank_id' => '123',
        );

        foreach ($fields as $key => $value) {
            $_POST['woocommerce_bunq_' . $key] = $value;
        }
    }
}
