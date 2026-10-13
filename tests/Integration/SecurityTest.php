<?php

namespace BunqTest\Integration;

use BunqTest\FakeBunqApi;
use PatrickKivits\BunqForWooCommerce\Gateway\CallbackHandler;
use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;
use PatrickKivits\BunqForWooCommerce\Plugin;

/**
 * Who can see, change or replace the bunq credentials in a real WordPress and WooCommerce.
 */
final class SecurityTest extends TestCase
{
    const PRIVATE_KEY = '-----BEGIN PRIVATE KEY-----MIIEvQIBADAN';

    public function set_up()
    {
        parent::set_up();

        update_option('woocommerce_bunq_settings', $this->liveSettings(array(
            'oauth_client_secret' => 'the-client-secret',
            'api_key' => 'the-api-key',
            'api_context' => '{"api_key":"the-api-key","installation_context":{"private_key_client":"' . self::PRIVATE_KEY . '"}}',
        )));
        WC()->payment_gateways()->init();
    }

    public function tear_down()
    {
        $_GET = array();
        unset($GLOBALS['current_screen']);
        parent::tear_down();
    }

    /**
     * @dataProvider usersWithoutAccess
     */
    public function testOnlyAnAdministratorCanCompleteTheAuthorization($role)
    {
        wp_set_current_user($role ? self::factory()->user->create(array('role' => $role)) : 0);
        $this->onTheReturnFromBunq();

        $this->assertFalse(Gateway::is_oauth_return());
        Plugin::instance()->handle_oauth_return();

        $this->assertFalse(get_transient(Gateway::LAST_ERROR_TRANSIENT), 'No token exchange was attempted');
        $this->assertSame('the-api-key', $this->credentials()['api_key']);
    }

    public function usersWithoutAccess()
    {
        return array('visitor' => array(null), 'customer' => array('customer'), 'subscriber' => array('subscriber'));
    }

    public function testAnAdministratorReturningFromBunqCompletesIt()
    {
        wp_set_current_user(self::factory()->user->create(array('role' => 'administrator')));
        $this->onTheReturnFromBunq();

        $this->assertTrue(Gateway::is_oauth_return());
    }

    public function testTheCredentialsAreNotLoadedOnEveryRequest()
    {
        global $wpdb;

        $this->assertSame('the-api-key', $this->gateway()->get_setting('api_key'));
        $this->assertContains(
            $wpdb->get_var($wpdb->prepare("SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", Gateway::CREDENTIALS_OPTION)),
            array('no', 'off')
        );
        $this->assertArrayNotHasKey('api_context', get_option('woocommerce_bunq_settings'));
    }

    public function testTheSettingsPageShowsNoSecret()
    {
        wp_set_current_user(self::factory()->user->create(array('role' => 'administrator')));
        $gateway = $this->gateway();

        $html = $gateway->generate_settings_html($gateway->get_form_fields(), false);

        $this->assertStringNotContainsString('the-client-secret', $html);
        $this->assertStringNotContainsString('the-api-key', $html);
        $this->assertStringNotContainsString(self::PRIVATE_KEY, $html);
        $this->assertStringContainsString('Saved. Enter a new value to replace it.', $html);
        $this->assertStringContainsString('Connected to bunq.', $html);
    }

    public function testTheRestApiShowsNoSecret()
    {
        wp_set_current_user(self::factory()->user->create(array('role' => 'shop_manager')));

        $response = rest_do_request(new \WP_REST_Request('GET', '/wc/v3/payment_gateways/bunq'));

        $this->assertSame(200, $response->get_status());
        $settings = $response->get_data()['settings'];
        $this->assertSame('', $settings['oauth_client_secret']['value']);
        $this->assertArrayNotHasKey('api_key', $settings);
        $this->assertArrayNotHasKey('api_context', $settings);
        $json = wp_json_encode($response->get_data());
        $this->assertStringNotContainsString('the-client-secret', $json);
        $this->assertStringNotContainsString('the-api-key', $json);
    }

    public function testARestUpdateKeepsTheSecretItWasNotShownAndCannotSetTheApiKey()
    {
        wp_set_current_user(self::factory()->user->create(array('role' => 'shop_manager')));

        $response = $this->updateThroughRest(array('oauth_client_secret' => '', 'api_key' => 'attacker-key', 'title' => 'bunq'));

        $this->assertSame(200, $response->get_status());
        $this->assertSame('the-client-secret', get_option('woocommerce_bunq_settings')['oauth_client_secret']);
        $this->assertSame('bunq', get_option('woocommerce_bunq_settings')['title']);
        $this->assertSame('the-api-key', $this->credentials()['api_key']);

        $this->updateThroughRest(array('oauth_client_secret' => 'replaced-secret'));
        $this->assertSame('replaced-secret', get_option('woocommerce_bunq_settings')['oauth_client_secret']);
    }

    public function testCallbacksInQuickSuccessionQueueOneFollowUpCheck()
    {
        $bunq = new FakeBunqApi();
        $order = $this->createOrder();
        $bunq->paymentRequests[55] = FakeBunqApi::paymentRequest(55, 'ACTIVE');
        $handler = new CallbackHandler(new Gateway($bunq));
        $body = wp_json_encode(array('NotificationUrl' => array('category' => 'BUNQME_TAB', 'object' => array('BunqMeTab' => array('id' => 55)))));

        $this->assertSame('pending', $handler->handle($body));
        $this->assertSame('deferred', $handler->handle($body));
        $this->assertSame('deferred', $handler->handle($body));

        $this->assertCount(1, $bunq->callsTo('get_payment_request'));
        $this->assertCount(1, as_get_scheduled_actions(array('hook' => 'bunq_for_woocommerce_follow_up_check', 'args' => array($order->get_id()), 'status' => \ActionScheduler_Store::STATUS_PENDING), 'ids'));
    }

    private function gateway()
    {
        return WC()->payment_gateways()->payment_gateways()['bunq'];
    }

    private function onTheReturnFromBunq()
    {
        set_current_screen('woocommerce_page_wc-settings');
        $GLOBALS['pagenow'] = 'admin.php';
        $_GET = array('page' => 'wc-settings', 'tab' => 'checkout', 'section' => 'bunq', 'code' => 'attacker-code', 'state' => 'anything');
    }

    private function updateThroughRest(array $settings)
    {
        $request = new \WP_REST_Request('PUT', '/wc/v3/payment_gateways/bunq');
        $request->set_body_params(array('settings' => $settings));

        return rest_do_request($request);
    }
}
