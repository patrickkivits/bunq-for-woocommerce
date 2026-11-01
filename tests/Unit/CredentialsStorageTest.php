<?php

namespace BunqTest;

use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;

/**
 * Where the API keys, API contexts and OAuth client secrets are kept, and who can see or change them.
 */
final class CredentialsStorageTest extends TestCase
{
    public function testCredentialsOfAnEarlierVersionMoveOutOfTheSettings()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_context' => '{"live":1}', 'test_api_context' => '{"test":1}')));

        $this->assertSame(array(
            'api_key' => 'live-api-key',
            'api_context' => '{"live":1}',
            'test_api_key' => 'test-api-key',
            'test_api_context' => '{"test":1}',
        ), $this->credentials());

        $settings = get_option('woocommerce_bunq_settings');
        foreach (Gateway::CREDENTIAL_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $settings);
        }
        $this->assertSame('live-client-secret', $settings['oauth_client_secret'], 'The other settings stay');
        $this->assertSame('{"live":1}', $gateway->get_setting('api_context'));
        $this->assertSame('live-api-key', $gateway->api_key);
    }

    public function testSettingsWrittenByAnOlderVersionAfterAnUpgradeWin()
    {
        // Downgrade, reauthorize, upgrade again.
        $this->createGateway($this->liveSettings(array('api_key' => 'old-key')));
        update_option('woocommerce_bunq_settings', $this->liveSettings(array('api_key' => 'newer-key')));

        $this->assertSame('newer-key', (new Gateway())->get_setting('api_key'));
    }

    public function testSavingTheSettingsDoesNotPutTheCredentialsBack()
    {
        $gateway = $this->createGateway($this->liveSettings(array('api_context' => '{"live":1}')));
        $this->postSettings(array('oauth_client_id' => 'live-client-id', 'oauth_client_secret' => ''));

        $gateway->process_admin_options();

        $this->assertArrayNotHasKey('api_key', get_option('woocommerce_bunq_settings'));
        $this->assertSame('{"live":1}', $this->credentials()['api_context']);
    }

    public function testThePostedFormCannotSetTheApiKeyOrApiContext()
    {
        // A shop manager posting the fields earlier versions had, e.g. to route bunq calls through a proxy.
        $gateway = $this->createGateway($this->liveSettings(array('api_context' => '{"live":1}')));
        $this->postSettings(array(
            'oauth_client_id' => 'live-client-id',
            'api_key' => 'attacker-key',
            'api_context' => '{"proxy_url":"http://attacker.example"}',
        ));

        $gateway->process_admin_options();

        $this->assertSame('live-api-key', $this->credentials()['api_key']);
        $this->assertSame('{"live":1}', $this->credentials()['api_context']);
    }

    public function testAnEmptySecretFieldKeepsTheSavedSecret()
    {
        $gateway = $this->createGateway($this->liveSettings());
        $this->postSettings(array('oauth_client_id' => 'live-client-id', 'oauth_client_secret' => ''));

        $gateway->process_admin_options();

        $this->assertSame('live-client-secret', get_option('woocommerce_bunq_settings')['oauth_client_secret']);
        $this->assertSame('live-api-key', $this->credentials()['api_key'], 'Still connected');
    }

    public function testANewSecretReplacesTheSavedOne()
    {
        $gateway = $this->createGateway($this->liveSettings());
        $this->postSettings(array('oauth_client_id' => 'live-client-id', 'oauth_client_secret' => ' new-secret '));

        $gateway->process_admin_options();

        $this->assertSame('new-secret', get_option('woocommerce_bunq_settings')['oauth_client_secret']);
    }

    public function testTheSettingsFormHasNoFieldForTheApiKeyOrApiContext()
    {
        foreach (array('no', 'yes') as $testmode) {
            $fields = $this->createGateway($this->liveSettings(array('testmode' => $testmode)))->get_form_fields();

            foreach (Gateway::CREDENTIAL_KEYS as $key) {
                $this->assertArrayNotHasKey($key, $fields);
            }
        }
    }

    private function postSettings(array $fields)
    {
        $_POST = array(
            'woocommerce_bunq_enabled' => '1',
            'woocommerce_bunq_title' => 'iDEAL | Wero, Credit Card or Bancontact',
            'woocommerce_bunq_description' => 'Pay with iDEAL | Wero, Credit Card or Bancontact',
            'woocommerce_bunq_monetary_account_bank_id' => '123',
        );

        // The form has the OAuth clients of both modes: the browser posts the client ids as they are shown and the
        // secrets empty (they are never shown).
        $saved = get_option('woocommerce_bunq_settings', array());
        foreach (array('oauth_client_id', 'test_oauth_client_id') as $key) {
            $_POST['woocommerce_bunq_' . $key] = isset($saved[$key]) ? $saved[$key] : '';
        }
        $_POST['woocommerce_bunq_oauth_client_secret'] = '';
        $_POST['woocommerce_bunq_test_oauth_client_secret'] = '';

        foreach ($fields as $key => $value) {
            $_POST['woocommerce_bunq_' . $key] = $value;
        }
    }
}
