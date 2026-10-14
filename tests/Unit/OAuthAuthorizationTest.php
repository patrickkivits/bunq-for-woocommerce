<?php

namespace BunqTest;

use PatrickKivits\BunqForWooCommerce\Bunq\OAuthClient;
use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;

/**
 * OAuth client that answers the token exchange itself instead of asking bunq.
 */
final class FakeOAuthClient extends OAuthClient
{
    /** @var array[] The arguments of every token exchange. */
    public $exchanges = array();
    public $accessToken = 'fresh-access-token';
    public $exception = null;

    public function get_access_token($client_id, $client_secret, $redirect_uri, $testmode, $code, $state)
    {
        $this->exchanges[] = func_get_args();

        if ($this->exception) {
            throw $this->exception;
        }

        return $this->accessToken;
    }
}

/**
 * The merchant's return from bunq after authorizing the plugin: the access token is saved and the API context
 * created with it.
 */
final class OAuthAuthorizationTest extends TestCase
{
    /** @var FakeBunqApi */
    private $bunq;

    /** @var FakeOAuthClient */
    private $oauth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bunq = new FakeBunqApi();
        $this->oauth = new FakeOAuthClient();
        set_transient(Gateway::BANK_ACCOUNTS_TRANSIENT, array('' => 'Connect your bunq account first'), 5 * MINUTE_IN_SECONDS);
        set_transient(Gateway::LAST_ERROR_TRANSIENT, 'An earlier error', 5 * MINUTE_IN_SECONDS);

        // bunq sends the merchant back to the settings page with the code and the state.
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bunq&code=authorization-code&state=returned-state';
        $_GET = array('page' => 'wc-settings', 'tab' => 'checkout', 'section' => 'bunq', 'code' => 'authorization-code', 'state' => 'returned-state');
    }

    public function testTheTokenIsRequestedWithTheRegisteredRedirectUrlHoweverThePageWasOpened()
    {
        // The redirect URL in the token request must match the one bunq sent the merchant to, which is the one
        // registered in the bunq app; extra parameters on the page URL do not make it into either.
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM&section=bunq&code=authorization-code&state=returned-state';
        $gateway = $this->gateway($this->liveSettings(array('api_key' => '', 'api_context' => '')));

        $gateway->complete_oauth_authorization();

        $this->assertSame('https://shop.example/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bunq', $this->oauth->exchanges[0][2]);
    }

    public function testTheAccessTokenBecomesTheApiKeyAndTheApiContextIsCreated()
    {
        $gateway = $this->gateway($this->liveSettings(array('api_key' => '', 'api_context' => '')));

        $redirect = $gateway->complete_oauth_authorization();

        $settings_url = 'https://shop.example/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bunq';
        $this->assertSame($settings_url, $redirect, 'Back to the settings page, without the OAuth parameters');
        $this->assertSame(array(array('live-client-id', 'live-client-secret', $settings_url, false, 'authorization-code', 'returned-state')), $this->oauth->exchanges);

        $saved = get_option('woocommerce_bunq_settings');
        $this->assertSame('fresh-access-token', $this->credentials()['api_key']);
        $this->assertSame('{"created":1}', $this->credentials()['api_context']);
        $this->assertSame('test-api-key', $this->credentials()['test_api_key'], 'The other mode is left alone');
        $this->assertSame(array(array('fresh-access-token', false)), $this->bunq->callsTo('create_api_context'));
        $this->assertSame(array(array(123)), $this->bunq->callsTo('create_notification_filters'));

        $this->assertFalse(get_transient(Gateway::BANK_ACCOUNTS_TRANSIENT), 'The accounts are looked up again with the new key');
        $this->assertFalse(get_transient(Gateway::LAST_ERROR_TRANSIENT));
        $this->assertSame('bunq authorization completed. Select your bank account and save the settings.', get_transient(Gateway::LAST_SUCCESS_TRANSIENT));
    }

    public function testInTestModeTheSandboxCredentialsAreUsedAndFilled()
    {
        $gateway = $this->gateway($this->liveSettings(array('testmode' => 'yes')));

        $gateway->complete_oauth_authorization();

        $this->assertSame(array('test-client-id', 'test-client-secret'), array_slice($this->oauth->exchanges[0], 0, 2));
        $this->assertTrue($this->oauth->exchanges[0][3]);

        $saved = get_option('woocommerce_bunq_settings');
        $this->assertSame('fresh-access-token', $this->credentials()['test_api_key']);
        $this->assertSame('{"created":1}', $this->credentials()['test_api_context']);
        $this->assertSame('live-api-key', $this->credentials()['api_key']);
        $this->assertSame(array(array('fresh-access-token', true)), $this->bunq->callsTo('create_api_context'));
    }

    public function testARejectedTokenExchangeIsShownOnTheSettingsPage()
    {
        $gateway = $this->gateway($this->liveSettings());
        $this->oauth->exception = new \Exception('OAuth state mismatch. Please click "Connect to bunq" again and complete the flow in one go.');

        $redirect = $gateway->complete_oauth_authorization();

        $this->assertSame('https://shop.example/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bunq', $redirect);
        $this->assertSame('OAuth state mismatch. Please click "Connect to bunq" again and complete the flow in one go.', get_transient(Gateway::LAST_ERROR_TRANSIENT));
        $this->assertFalse(get_transient(Gateway::LAST_SUCCESS_TRANSIENT));
        $this->assertSame('live-api-key', $this->credentials()['api_key']);
        $this->assertSame(array(), $this->bunq->calls);
        $this->assertLogged('error', 'OAuth state mismatch');
    }

    public function testAKeyBunqRejectsIsKeptButTheErrorIsShown()
    {
        $gateway = $this->gateway($this->liveSettings(array('api_context' => '')));
        $this->bunq->exceptions['create_api_context'] = new \RuntimeException('Incorrect API key or IP address');

        $gateway->complete_oauth_authorization();

        $this->assertSame('Incorrect API key or IP address', get_transient(Gateway::LAST_ERROR_TRANSIENT));
        $this->assertSame('fresh-access-token', $this->credentials()['api_key']);
        $this->assertSame('', $this->credentials()['api_context']);
    }

    public function testOnAServerWhoseOpcacheStripsDocCommentsBunqIsNotCalledAndTheHostSettingIsShown()
    {
        $gateway = $this->gateway($this->liveSettings(array('api_context' => '')));
        $this->bunq->canDecodeResponses = false;

        $gateway->complete_oauth_authorization();

        $this->assertStringContainsString('Ask your hosting provider to set opcache.save_comments=1', get_transient(Gateway::LAST_ERROR_TRANSIENT));
        $this->assertFalse(get_transient(Gateway::LAST_SUCCESS_TRANSIENT));
        $this->assertSame(array(), $this->bunq->callsTo('create_api_context'));
        $this->assertSame('', $this->credentials()['api_context']);
    }

    public function testOutputDuringTheAuthorizationDoesNotReachTheResponse()
    {
        $gateway = $this->gateway($this->liveSettings());
        $this->oauth->exception = new \Exception('failed');
        $level = ob_get_level();

        ob_start();
        $gateway->complete_oauth_authorization();
        $this->assertSame('', ob_get_clean());
        $this->assertSame($level, ob_get_level(), 'The output buffer is closed again');
    }

    private function gateway(array $settings)
    {
        update_option('woocommerce_bunq_settings', $settings);

        return new Gateway($this->bunq, $this->oauth);
    }
}
