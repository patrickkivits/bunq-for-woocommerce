<?php

namespace BunqTest;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Environment::reset();
    }

    /**
     * Instantiate the gateway the way WooCommerce does, optionally with saved settings.
     *
     * @param array|null $settings The saved woocommerce_bunq_settings option; null for a shop that never saved them.
     * @return \PatrickKivits\BunqForWooCommerce\Gateway\Gateway
     */
    protected function createGateway(?array $settings = null)
    {
        if ($settings !== null) {
            update_option('woocommerce_bunq_settings', $settings);
        }

        return new \PatrickKivits\BunqForWooCommerce\Gateway\Gateway();
    }

    /**
     * Make WooCommerce hand out this gateway instance (WC()->payment_gateways()->payment_gateways()).
     */
    protected function registerGateway(\PatrickKivits\BunqForWooCommerce\Gateway\Gateway $gateway)
    {
        Environment::$gateways['bunq'] = $gateway;
    }

    /**
     * The stored API keys and API contexts (kept apart from the settings since 1.7.0).
     *
     * @return array
     */
    protected function credentials()
    {
        return get_option(\PatrickKivits\BunqForWooCommerce\Gateway\Gateway::CREDENTIALS_OPTION, array());
    }

    /**
     * A complete settings array as saved by a shop that finished the setup in live mode. The API keys and contexts
     * are among them the way versions before 1.7.0 stored them; the gateway moves them to their own option.
     *
     * @param array $overrides
     * @return array
     */
    protected function liveSettings(array $overrides = array())
    {
        return array_merge(array(
            'enabled' => 'yes',
            'testmode' => 'no',
            'title' => 'iDEAL | Wero, Credit Card or Bancontact',
            'description' => 'Pay with iDEAL | Wero, Credit Card or Bancontact',
            'monetary_account_bank_id' => '123',
            'direct_gateway' => 'no',
            'oauth_client_id' => 'live-client-id',
            'oauth_client_secret' => 'live-client-secret',
            'api_key' => 'live-api-key',
            'api_context' => '',
            'test_oauth_client_id' => 'test-client-id',
            'test_oauth_client_secret' => 'test-client-secret',
            'test_api_key' => 'test-api-key',
            'test_api_context' => '',
        ), $overrides);
    }

    /**
     * Assert that one of the logged messages at the given level contains the text.
     */
    protected function assertLogged($level, $needle)
    {
        foreach (Environment::logMessages($level) as $message) {
            if (strpos($message, $needle) !== false) {
                $this->addToAssertionCount(1);

                return;
            }
        }

        $this->fail(sprintf(
            'No "%s" log message contains "%s". Logged: %s',
            $level,
            $needle,
            json_encode(Environment::$logs)
        ));
    }
}
