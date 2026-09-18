<?php

namespace BunqTest;

use WC_Bunq_Gateway;

/**
 * The WooCommerce payment gateway, up to the point where it would talk to bunq.
 */
final class GatewayTest extends TestCase
{
    public function testDefaultsWhenNothingHasBeenSaved()
    {
        $gateway = $this->createGateway();

        $this->assertSame('bunq', $gateway->id);
        $this->assertSame('bunq', $gateway->get_method_title());
        $this->assertSame('no', $gateway->enabled);
        $this->assertFalse($gateway->testmode);
        $this->assertFalse($gateway->direct_gateway);
        $this->assertFalse($gateway->has_fields);
        $this->assertSame('iDEAL, Credit Card or Bancontact', $gateway->title);
        $this->assertSame('Pay with iDEAL, Credit Card or Bancontact', $gateway->description);
        $this->assertTrue($gateway->supports('products'));
        $this->assertTrue($gateway->supports('refunds'));
        $this->assertNull($gateway->get_monetary_account_bank_id());
    }

    public function testTheGatewayHooksItsSettingsAndCallbackHandlers()
    {
        $gateway = $this->createGateway();

        $this->assertSame(10, has_action('woocommerce_update_options_payment_gateways_bunq', array($gateway, 'process_admin_options')));
        $this->assertSame(10, has_action('woocommerce_api_wc_bunq_gateway', array($gateway, 'bunq_callback')));
    }

    public function testLiveCredentialsAreUsedOutsideTestMode()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_context' => '{"live":1}', 'test_api_context' => '{"test":1}')));

        $this->assertFalse($gateway->testmode);
        $this->assertSame('live-api-key', $gateway->api_key);
        $this->assertSame('live-client-id', $gateway->oauth_client_id);
        $this->assertSame('live-client-secret', $gateway->oauth_client_secret);
        $this->assertSame('{"live":1}', $gateway->api_context);
    }

    public function testTestCredentialsAreUsedInTestMode()
    {
        $gateway = $this->createGateway($this->liveSettings(array('testmode' => 'yes', 'api_context' => '{"live":1}', 'test_api_context' => '{"test":1}')));

        $this->assertTrue($gateway->testmode);
        $this->assertSame('test-api-key', $gateway->api_key);
        $this->assertSame('test-client-id', $gateway->oauth_client_id);
        $this->assertSame('test-client-secret', $gateway->oauth_client_secret);
        $this->assertSame('{"test":1}', $gateway->api_context);
    }

    public function testGetSettingMapsTheGenericKeysToTheActiveMode()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_context' => '{"live":1}', 'test_api_context' => '{"test":1}')));

        $this->assertSame('{"live":1}', $gateway->get_setting('api_context'));
        $this->assertSame('live-client-id', $gateway->get_setting('oauth_client_id'));
        $this->assertSame('live-client-secret', $gateway->get_setting('oauth_client_secret'));
        $this->assertSame('live-api-key', $gateway->get_setting('api_key'));
        $this->assertSame('test-api-key', $gateway->get_setting('test_api_key'));
        $this->assertNull($gateway->get_setting('unknown'));

        update_option('woocommerce_bunq_settings', $this->liveSettings(array('testmode' => 'yes', 'api_context' => '{"live":1}', 'test_api_context' => '{"test":1}')));

        $this->assertSame('{"test":1}', $gateway->get_setting('api_context'), 'Reads the saved settings, not the ones loaded at construction');
        $this->assertSame('test-client-id', $gateway->get_setting('oauth_client_id'));
        $this->assertSame('test-client-secret', $gateway->get_setting('oauth_client_secret'));
        $this->assertSame('live-api-key', $gateway->get_setting('api_key'), 'Only the three generic keys are mapped');
    }

    public function testGetSettingIsUnavailableWithoutTheTestModeSetting()
    {
        $gateway = $this->createGateway(array('title' => 'Pay'));

        $this->assertNull($gateway->get_setting('title'));
    }

    /**
     * @dataProvider monetaryAccountSettings
     */
    public function testTheSelectedBankAccountIsAnIdOrNull($setting, $expected)
    {
        $gateway = $this->createGateway($this->liveSettings(array('monetary_account_bank_id' => $setting)));

        $this->assertSame($expected, $gateway->get_monetary_account_bank_id());
    }

    public function monetaryAccountSettings()
    {
        return array(
            'not selected' => array('', null),
            'zero' => array('0', null),
            'garbage' => array('abc', null),
            'id' => array('123', 123),
            'negative' => array('-5', null),
        );
    }

    public function testAllPaymentMethodsAreAllowedByDefault()
    {
        $gateway = $this->createGateway($this->liveSettings());

        $this->assertSame(array('card', 'ideal', 'bancontact', 'bunq-transfer'), $this->ids($gateway->get_allowed_payment_methods()));
        $this->assertSame(array('card', 'ideal', 'bancontact', 'bunq-transfer'), $this->ids($gateway->get_allowed_payment_methods(50)));
    }

    public function testOnlyTheEnabledPaymentMethodsAreAllowed()
    {
        $gateway = $this->createGateway($this->liveSettings(array('enabled_payment_methods' => array('ideal', 'card'))));

        $this->assertSame(array('card', 'ideal'), $this->ids($gateway->get_allowed_payment_methods()));
    }

    public function testAnEmptySelectionMeansEveryPaymentMethod()
    {
        $gateway = $this->createGateway($this->liveSettings(array('enabled_payment_methods' => array())));

        $this->assertCount(4, $gateway->get_allowed_payment_methods());
    }

    /**
     * @dataProvider totals
     */
    public function testPaymentMethodsAreLimitedToTheAmountBunqAccepts($total, array $expected)
    {
        $gateway = $this->createGateway($this->liveSettings());

        $this->assertSame($expected, $this->ids($gateway->get_allowed_payment_methods($total)));
    }

    public function totals()
    {
        return array(
            'below the card minimum' => array(0.5, array('ideal', 'bunq-transfer')),
            'card minimum' => array(1, array('card', 'ideal', 'bunq-transfer')),
            'bancontact minimum' => array(5, array('card', 'ideal', 'bancontact', 'bunq-transfer')),
            'card maximum' => array(500, array('card', 'ideal', 'bancontact', 'bunq-transfer')),
            'above the card maximum' => array(500.01, array('ideal', 'bancontact', 'bunq-transfer')),
            'above the bancontact maximum' => array(10000.01, array('ideal', 'bunq-transfer')),
        );
    }

    public function testTheEnabledSelectionAndTheAmountLimitsCombine()
    {
        $gateway = $this->createGateway($this->liveSettings(array('enabled_payment_methods' => array('card', 'bancontact'))));

        $this->assertSame(array('card'), $this->ids($gateway->get_allowed_payment_methods(2)));
        $this->assertSame(array(), $this->ids($gateway->get_allowed_payment_methods(0.5)));
    }

    public function testTheCheckoutTotalIsTheCartTotal()
    {
        $gateway = $this->createGateway($this->liveSettings());

        $this->assertNull($gateway->get_checkout_total(), 'No cart (admin, block editor)');

        WC()->cart = (object) array('total' => 0);
        $this->assertNull($gateway->get_checkout_total(), 'Empty cart');

        WC()->cart = (object) array('total' => '12.50');
        $this->assertSame(12.5, $gateway->get_checkout_total());
    }

    public function testTheCheckoutTotalIsTheOrderTotalOnThePayForOrderPage()
    {
        $gateway = $this->createGateway($this->liveSettings());
        $order = Environment::addOrder(array('total' => '42.00'));
        WC()->cart = (object) array('total' => '12.50');
        Environment::$wcEndpoint = 'order-pay';
        Environment::$queryVars['order-pay'] = (string) $order->get_id();

        $this->assertSame(42.0, $gateway->get_checkout_total());

        Environment::$queryVars['order-pay'] = '999';
        $this->assertNull($gateway->get_checkout_total(), 'Unknown order');
    }

    public function testThePaymentFieldsShowTheDescription()
    {
        $gateway = $this->createGateway($this->liveSettings(array('description' => 'Pay with bunq')));

        ob_start();
        $gateway->payment_fields();
        $html = ob_get_clean();

        $this->assertStringContainsString('<p>Pay with bunq</p>', $html);
        $this->assertStringNotContainsString('<select', $html);
    }

    public function testTheDirectGatewayOffersThePaymentMethodsThatFitTheCartTotal()
    {
        $gateway = $this->createGateway($this->liveSettings(array('direct_gateway' => 'yes')));
        WC()->cart = (object) array('total' => '0.50');

        $this->assertTrue($gateway->has_fields);

        ob_start();
        $gateway->payment_fields();
        $html = ob_get_clean();

        $this->assertStringContainsString('<select name="wc_bunq_gateway_payment_method">', $html);
        $this->assertStringContainsString('<option value="ideal">iDEAL</option>', $html);
        $this->assertStringContainsString('<option value="bunq-transfer">From a bunq account</option>', $html);
        $this->assertStringNotContainsString('value="card"', $html);
        $this->assertStringNotContainsString('value="bancontact"', $html);
    }

    public function testValidateFieldsRejectsAnUnknownPaymentMethod()
    {
        $gateway = $this->createGateway($this->liveSettings(array('direct_gateway' => 'yes')));

        $_POST['wc_bunq_gateway_payment_method'] = 'paypal';
        $this->assertFalse($gateway->validate_fields());
        $this->assertSame(array(array('type' => 'error', 'message' => 'Payment method invalid')), Environment::$notices);

        Environment::$notices = array();
        $_POST['wc_bunq_gateway_payment_method'] = 'ideal';
        $this->assertTrue($gateway->validate_fields());

        $_POST = array();
        $this->assertTrue($gateway->validate_fields(), 'Without a chosen method bunq.me offers all of them');
        $this->assertSame(array(), Environment::$notices);
    }

    public function testTheGatewayIsOnlyAvailableOnceAuthorizedWithBunq()
    {
        $this->assertTrue($this->createGateway($this->liveSettings(array('api_context' => '{"live":1}')))->is_available());

        $this->assertFalse($this->createGateway($this->liveSettings())->is_available(), 'No API context');
        $this->assertFalse($this->createGateway($this->liveSettings(array('enabled' => 'no', 'api_context' => '{"live":1}')))->is_available(), 'Disabled');
        $this->assertFalse($this->createGateway($this->liveSettings(array('testmode' => 'yes', 'api_context' => '{"live":1}')))->is_available(), 'Test mode without a test API context');

        set_transient(WC_Bunq_Gateway::CONTEXT_FAILED_TRANSIENT, 1, 5 * MINUTE_IN_SECONDS);
        $this->assertFalse($this->createGateway($this->liveSettings(array('api_context' => '{"live":1}')))->is_available(), 'Recreating the API context failed recently');
    }

    public function testTheLiveSettingsFormShowsTheLiveCredentials()
    {
        $fields = $this->createGateway($this->liveSettings())->get_form_fields();

        $this->assertSame(
            array('enabled', 'testmode', 'title', 'description', 'monetary_account_bank_id', 'direct_gateway', 'oauth_client_id', 'oauth_client_secret', 'api_key', 'api_context'),
            array_keys($fields)
        );
        $this->assertSame('readonly', $fields['api_key']['custom_attributes']['readonly']);
        $this->assertSame('readonly', $fields['api_context']['custom_attributes']['readonly']);
        $this->assertSame('password', $fields['oauth_client_secret']['type']);
    }

    public function testTheTestModeSettingsFormShowsTheTestCredentials()
    {
        $fields = $this->createGateway($this->liveSettings(array('testmode' => 'yes')))->get_form_fields();

        $this->assertSame(
            array('enabled', 'testmode', 'title', 'description', 'monetary_account_bank_id', 'direct_gateway', 'test_oauth_client_id', 'test_oauth_client_secret', 'test_api_key', 'test_api_context'),
            array_keys($fields)
        );
    }

    public function testTheDirectGatewayAddsThePaymentMethodSelection()
    {
        $fields = $this->createGateway($this->liveSettings(array('direct_gateway' => 'yes')))->get_form_fields();

        $this->assertSame('multiselect', $fields['enabled_payment_methods']['type']);
        $this->assertSame(
            array('card' => 'Credit or Debit Card', 'ideal' => 'iDEAL', 'bancontact' => 'Bancontact', 'bunq-transfer' => 'From a bunq account'),
            $fields['enabled_payment_methods']['options']
        );
    }

    public function testBankAccountsAreNotFetchedOutsideAdmin()
    {
        $fields = $this->createGateway($this->liveSettings(array('api_context' => 'not json')))->get_form_fields();

        $this->assertSame(array(), $fields['monetary_account_bank_id']['options']);
        $this->assertFalse(get_transient(WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT));
        $this->assertSame(array(), Environment::$logs, 'The API context was not even loaded');
    }

    public function testTheCachedBankAccountsFillTheSelectInAdmin()
    {
        Environment::$isAdmin = true;
        set_transient(WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT, array('' => 'Select a bank account', '123' => 'NL00BUNQ0000000000 - Shop'), HOUR_IN_SECONDS);

        $fields = $this->createGateway($this->liveSettings())->get_form_fields();

        $this->assertSame(array('' => 'Select a bank account', '123' => 'NL00BUNQ0000000000 - Shop'), $fields['monetary_account_bank_id']['options']);
    }

    public function testWithoutAnApiContextTheSelectSaysSoAndTheAnswerIsCachedBriefly()
    {
        Environment::$isAdmin = true;

        $fields = $this->createGateway($this->liveSettings())->get_form_fields();

        $this->assertSame(array('' => 'API key not valid or not setup yet'), $fields['monetary_account_bank_id']['options']);
        $this->assertSame(array('' => 'API key not valid or not setup yet'), get_transient(WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT));
        $this->assertSame(5 * MINUTE_IN_SECONDS, Environment::$transientTtls[WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT]);
    }

    public function testTheRefreshLinkDropsTheCachedBankAccounts()
    {
        Environment::$isAdmin = true;
        $cached = array('' => 'Select a bank account', '123' => 'NL00BUNQ0000000000 - Shop');
        set_transient(WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT, $cached, HOUR_IN_SECONDS);
        $_GET = array(WC_Bunq_Gateway::REFRESH_ACCOUNTS_ACTION => '1', '_wpnonce' => 'nonce');

        $fields = $this->createGateway($this->liveSettings())->get_form_fields();

        $this->assertSame(array('' => 'API key not valid or not setup yet'), $fields['monetary_account_bank_id']['options'], 'Fetched again');
    }

    public function testTheRefreshLinkNeedsAValidNonceAndTheCapability()
    {
        Environment::$isAdmin = true;
        $cached = array('' => 'Select a bank account', '123' => 'NL00BUNQ0000000000 - Shop');
        set_transient(WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT, $cached, HOUR_IN_SECONDS);
        $_GET = array(WC_Bunq_Gateway::REFRESH_ACCOUNTS_ACTION => '1', '_wpnonce' => 'nonce');

        Environment::$validNonce = false;
        $this->assertSame($cached, $this->createGateway($this->liveSettings())->get_form_fields()['monetary_account_bank_id']['options']);

        Environment::$validNonce = true;
        Environment::$currentUserCan = false;
        $this->assertSame($cached, $this->createGateway($this->liveSettings())->get_form_fields()['monetary_account_bank_id']['options']);
    }

    public function testTheSettingsPageOffersTheAuthorizationOnceTheClientIsSaved()
    {
        Environment::$isAdmin = true;

        $html = $this->adminOptions($this->createGateway($this->liveSettings()));

        $this->assertStringContainsString('OAuth Authorization Request', $html);
        $this->assertStringContainsString('https://oauth.bunq.com/auth?', $html);
        $this->assertStringContainsString('client_id=live-client-id', $html);
        $this->assertStringContainsString(urlencode('http://shop.example/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bunq'), $html);
        $this->assertStringNotContainsString('Refresh bank accounts', $html, 'Nothing to refresh without an API context');
    }

    public function testTheSettingsPageUsesTheSandboxInTestMode()
    {
        Environment::$isAdmin = true;

        $html = $this->adminOptions($this->createGateway($this->liveSettings(array('testmode' => 'yes'))));

        $this->assertStringContainsString('https://oauth.sandbox.bunq.com/auth?', $html);
        $this->assertStringContainsString('client_id=test-client-id', $html);
    }

    public function testTheSettingsPageOffersToRefreshTheBankAccountsOnceAuthorized()
    {
        Environment::$isAdmin = true;

        $html = $this->adminOptions($this->createGateway($this->liveSettings(array('api_context' => '{"live":1}'))));

        $this->assertStringContainsString('Refresh bank accounts', $html);
        $this->assertStringContainsString(WC_Bunq_Gateway::REFRESH_ACCOUNTS_ACTION . '=1', $html);
        $this->assertStringContainsString('_wpnonce=', $html);
    }

    public function testTheSettingsPageHidesTheAuthorizationWithoutAClient()
    {
        Environment::$isAdmin = true;

        $html = $this->adminOptions($this->createGateway($this->liveSettings(array('oauth_client_secret' => ''))));

        $this->assertStringNotContainsString('OAuth Authorization Request', $html);
    }

    public function testTheSettingsPageShowsTheOutcomeOfTheLastAuthorizationOnce()
    {
        Environment::$isAdmin = true;
        set_transient(WC_Bunq_Gateway::LAST_ERROR_TRANSIENT, 'bunq token request failed', 5 * MINUTE_IN_SECONDS);
        set_transient(WC_Bunq_Gateway::LAST_SUCCESS_TRANSIENT, 'bunq authorization completed.', 5 * MINUTE_IN_SECONDS);
        $gateway = $this->createGateway($this->liveSettings());

        $html = $this->adminOptions($gateway);

        $this->assertStringContainsString('notice-error', $html);
        $this->assertStringContainsString('bunq token request failed', $html);
        $this->assertStringContainsString('page=wc-status', $html);
        $this->assertStringContainsString('notice-success', $html);
        $this->assertStringContainsString('bunq authorization completed.', $html);

        $this->assertFalse(get_transient(WC_Bunq_Gateway::LAST_ERROR_TRANSIENT));
        $this->assertFalse(get_transient(WC_Bunq_Gateway::LAST_SUCCESS_TRANSIENT));
        $this->assertStringNotContainsString('notice-error', $this->adminOptions($gateway));
    }

    public function testSavingTheSettingsKeepsTheAuthorizationWhileTheClientStays()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_context' => '{"live":1}', 'test_api_context' => '{"test":1}')));
        set_transient(WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT, array('123' => 'NL00'), HOUR_IN_SECONDS);
        $this->postSettings(array(
            'oauth_client_id' => 'live-client-id',
            'oauth_client_secret' => 'live-client-secret',
            'api_key' => 'live-api-key',
            'api_context' => '{"live":1}',
        ));

        $gateway->process_admin_options();

        $saved = get_option('woocommerce_bunq_settings');
        $this->assertSame('live-api-key', $saved['api_key']);
        $this->assertSame('{"live":1}', $saved['api_context']);
        $this->assertSame('test-api-key', $saved['test_api_key']);
        $this->assertSame('{"test":1}', $saved['test_api_context']);
        $this->assertSame(array('123' => 'NL00'), get_transient(WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT));
    }

    public function testRemovingTheLiveClientForgetsTheLiveAuthorization()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_context' => '{"live":1}', 'test_api_context' => '{"test":1}')));
        set_transient(WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT, array('123' => 'NL00'), HOUR_IN_SECONDS);
        $this->postSettings(array(
            'oauth_client_id' => '',
            'oauth_client_secret' => 'live-client-secret',
            'api_key' => 'live-api-key',
            'api_context' => '{"live":1}',
        ));

        $gateway->process_admin_options();

        $saved = get_option('woocommerce_bunq_settings');
        $this->assertSame('', $saved['api_key']);
        $this->assertSame('', $saved['api_context']);
        $this->assertSame('test-api-key', $saved['test_api_key'], 'The test authorization is unaffected');
        $this->assertSame('{"test":1}', $saved['test_api_context']);
        $this->assertFalse(get_transient(WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT));
    }

    public function testAMissingTestClientForgetsTheTestAuthorization()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_context' => '{"live":1}', 'test_api_context' => '{"test":1}', 'test_oauth_client_secret' => '')));
        $this->postSettings(array(
            'oauth_client_id' => 'live-client-id',
            'oauth_client_secret' => 'live-client-secret',
            'api_key' => 'live-api-key',
            'api_context' => '{"live":1}',
        ));

        $gateway->process_admin_options();

        $saved = get_option('woocommerce_bunq_settings');
        $this->assertSame('', $saved['test_api_key']);
        $this->assertSame('', $saved['test_api_context']);
        $this->assertSame('live-api-key', $saved['api_key']);
        $this->assertSame('{"live":1}', $saved['api_context']);
    }

    public function testTheFirstSaveOnAFreshInstallDoesNotWarnAboutTheMissingTestClient()
    {
        // Outside test mode the test_* fields are not part of the form, so WooCommerce never fills their
        // defaults: reading them directly raised "Undefined array key" on the first save.
        $gateway = $this->createGateway();
        $this->postSettings(array(
            'oauth_client_id' => 'live-client-id',
            'oauth_client_secret' => 'live-client-secret',
        ));

        $gateway->process_admin_options();

        $saved = get_option('woocommerce_bunq_settings');
        $this->assertSame('', $saved['test_api_key']);
        $this->assertSame('', $saved['test_api_context']);
        $this->assertSame('live-client-id', $saved['oauth_client_id']);
    }

    public function testTheFirstSaveInTestModeDoesNotWarnAboutTheMissingLiveClient()
    {
        $gateway = $this->createGateway(array('testmode' => 'yes'));
        $this->postSettings(array(
            'testmode' => '1',
            'test_oauth_client_id' => 'test-client-id',
            'test_oauth_client_secret' => 'test-client-secret',
        ));

        $gateway->process_admin_options();

        $saved = get_option('woocommerce_bunq_settings');
        $this->assertSame('', $saved['api_key']);
        $this->assertSame('', $saved['api_context']);
        $this->assertSame('test-client-id', $saved['test_oauth_client_id']);
    }

    public function testRefreshingTheApiContextNeedsAnApiKey()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_key' => '')));

        $gateway->refresh_api_context();

        $this->assertSame('', get_option('woocommerce_bunq_settings')['api_context']);
        $this->assertSame(array(), Environment::$logs);
    }

    public function testLoadingAnUnusableApiContextFailsAndKeepsTheSetting()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_context' => 'not json')));

        $this->assertFalse($gateway->load_api_context());
        $this->assertSame('not json', get_option('woocommerce_bunq_settings')['api_context']);
        $this->assertLogged('error', 'json_decode error');
    }

    public function testLoadingWithoutAnApiContextFails()
    {
        $gateway = $this->createGateway($this->liveSettings());

        $this->assertFalse($gateway->load_api_context());
        $this->assertSame(array(), Environment::$logs);
    }

    public function testEnsuringTheApiContextRemembersThatRecreatingItFailed()
    {
        // Without an API key there is nothing to recreate the context from.
        $gateway = $this->createGateway($this->liveSettings(array('api_key' => '')));

        try {
            $gateway->ensure_api_context_loaded();
            $this->fail('No API context should have been available');
        } catch (\Exception $exception) {
            $this->assertStringContainsString('bunq API context is not available. Complete the OAuth authorization', $exception->getMessage());
        }

        $this->assertSame(1, get_transient(WC_Bunq_Gateway::CONTEXT_FAILED_TRANSIENT));
        $this->assertSame(5 * MINUTE_IN_SECONDS, Environment::$transientTtls[WC_Bunq_Gateway::CONTEXT_FAILED_TRANSIENT]);
        $this->assertLogged('warning', 'recreating it from the saved API key');
    }

    public function testEnsuringTheApiContextDoesNotRetryWhileAFailureIsRemembered()
    {
        $gateway = $this->createGateway($this->liveSettings());
        set_transient(WC_Bunq_Gateway::CONTEXT_FAILED_TRANSIENT, 1, 5 * MINUTE_IN_SECONDS);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('a recent attempt to recreate it failed');

        $gateway->ensure_api_context_loaded();
    }

    public function testAPaymentFailsGracefullyWithoutAnApiContext()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_key' => '')));
        $order = Environment::addOrder();

        $result = $gateway->process_payment($order->get_id());

        $this->assertSame(array('result' => 'failure'), $result);
        $this->assertSame(array(array(
            'type' => 'error',
            'message' => 'The payment could not be started with bunq. Please try again or choose another payment method.',
        )), Environment::$notices);
        $this->assertLogged('error', 'bunq API context is not available');
        $this->assertSame('', $order->get_meta('bunq_payment_request_id'));
        $this->assertSame(array(), $order->notes);
        $this->assertSame(array(), Environment::$scheduledActions);
    }

    public function testARefundNeedsABunqPayment()
    {
        $gateway = $this->createGateway($this->liveSettings());
        $order = Environment::addOrder(array('status' => 'processing'));

        $error = $gateway->process_refund($order->get_id(), 5);

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertSame('bunq_refund', $error->get_error_code());
        $this->assertStringContainsString('no bunq payment that can be refunded', $error->get_error_message());
    }

    public function testARefundOfAnUnknownOrderFails()
    {
        $error = $this->createGateway($this->liveSettings())->process_refund(999, 5);

        $this->assertInstanceOf(\WP_Error::class, $error);
    }

    /**
     * @dataProvider invalidRefundAmounts
     */
    public function testARefundNeedsAPositiveAmount($amount)
    {
        $gateway = $this->createGateway($this->liveSettings());
        $order = Environment::addOrder(array('status' => 'processing', 'meta' => array('bunq_payment_id' => 77)));

        $error = $gateway->process_refund($order->get_id(), $amount);

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertStringContainsString('greater than zero', $error->get_error_message());
    }

    public function invalidRefundAmounts()
    {
        return array(array(null), array(0), array('0.00'), array(-5), array(''));
    }

    public function testARefundReportsWhyBunqCouldNotBeReached()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_key' => '')));
        $order = Environment::addOrder(array('status' => 'processing', 'meta' => array('bunq_payment_id' => 77)));

        $error = $gateway->process_refund($order->get_id(), '5.00', 'Damaged');

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertStringStartsWith('bunq refund failed: bunq API context is not available', $error->get_error_message());
        $this->assertLogged('error', 'bunq API context is not available');
        $this->assertSame(array(), $order->notes);
    }

    public function testCheckingAnOrderThatIsAlreadyPaidIsSettled()
    {
        $gateway = $this->createGateway($this->liveSettings());
        $order = Environment::addOrder(array('status' => 'processing', 'meta' => array('bunq_payment_request_id' => 55)));

        $this->assertSame('settled', $gateway->check_payment_status($order));
        $this->assertSame(array(), Environment::$logs);
    }

    public function testCheckingAnOrderWithoutAPaymentRequestFails()
    {
        $gateway = $this->createGateway($this->liveSettings());
        $order = Environment::addOrder();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('has no bunq payment request');

        $gateway->check_payment_status($order);
    }

    public function testOnlyOneCheckOfAnOrderRunsAtATime()
    {
        $gateway = $this->createGateway($this->liveSettings());
        $order = Environment::addOrder(array('meta' => array('bunq_payment_request_id' => 55)));
        set_transient('wc_bunq_gateway.lock.' . $order->get_id(), 1, 30);

        $this->assertSame('pending', $gateway->check_payment_status($order));
        $this->assertSame(array(), Environment::$logs, 'bunq was not contacted');
    }

    public function testTheCheckLockIsReleasedWhenBunqCannotBeReached()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_key' => '')));
        $order = Environment::addOrder(array('meta' => array('bunq_payment_request_id' => 55)));

        try {
            $gateway->check_payment_status($order);
            $this->fail('No API context should have been available');
        } catch (\Exception $exception) {
            $this->assertStringContainsString('bunq API context is not available', $exception->getMessage());
        }

        $this->assertFalse(get_transient('wc_bunq_gateway.lock.' . $order->get_id()));
        $this->assertSame('pending', $order->get_status());
    }

    private function ids(array $payment_methods)
    {
        return array_values(array_column($payment_methods, 'id'));
    }

    private function adminOptions(WC_Bunq_Gateway $gateway)
    {
        ob_start();
        $gateway->admin_options();

        return ob_get_clean();
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
