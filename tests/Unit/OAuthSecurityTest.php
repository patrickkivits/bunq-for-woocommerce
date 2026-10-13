<?php

namespace BunqTest;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PatrickKivits\BunqForWooCommerce\Bunq\OAuthClient;
use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;
use PatrickKivits\BunqForWooCommerce\Logger;
use PatrickKivits\BunqForWooCommerce\Plugin;

/**
 * Who can complete the bunq authorization, and what the token exchange may reveal.
 */
final class OAuthSecurityTest extends TestCase
{
    const SETTINGS_URL = 'https://shop.example/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bunq';

    protected function setUp(): void
    {
        parent::setUp();

        // The merchant's return from bunq, as an administrator on the settings page.
        Environment::$isAdmin = true;
        $GLOBALS['pagenow'] = 'admin.php';
        $_GET = array('page' => 'wc-settings', 'tab' => 'checkout', 'section' => 'bunq', 'code' => 'authorization-code', 'state' => 'returned-state');
    }

    public function testTheReturnFromBunqIsRecognised()
    {
        $this->assertTrue(Gateway::is_oauth_return());
    }

    /**
     * @dataProvider requestsThatMustNotCompleteTheAuthorization
     */
    public function testOnlyAnAdministratorOnTheSettingsPageCompletesTheAuthorization(callable $change)
    {
        $change();

        $this->assertFalse(Gateway::is_oauth_return());
    }

    public function requestsThatMustNotCompleteTheAuthorization()
    {
        return array(
            'without the capability (visitor, customer)' => array(function () {
                Environment::$currentUserCan = false;
            }),
            'admin-ajax.php' => array(function () {
                $GLOBALS['pagenow'] = 'admin-ajax.php';
                Environment::$doingAjax = true;
            }),
            'an AJAX request' => array(function () {
                Environment::$doingAjax = true;
            }),
            'admin-post.php' => array(function () {
                $GLOBALS['pagenow'] = 'admin-post.php';
            }),
            'another admin page' => array(function () {
                $_GET['page'] = 'wc-orders';
            }),
            'another gateway' => array(function () {
                $_GET['section'] = 'bacs';
            }),
            'the frontend' => array(function () {
                Environment::$isAdmin = false;
            }),
            'without code' => array(function () {
                unset($_GET['code']);
            }),
            'without state' => array(function () {
                $_GET['state'] = '';
            }),
        );
    }

    public function testARequestThatIsNotTheReturnLeavesTheConnectionAlone()
    {
        Environment::$currentUserCan = false;
        $this->registerGateway($this->gateway());

        Plugin::instance()->handle_oauth_return();

        $this->assertSame(array(), Environment::$redirects);
        $this->assertFalse(get_transient(Gateway::LAST_ERROR_TRANSIENT));
        $this->assertSame('live-api-key', $this->credentials()['api_key']);
    }

    public function testConstructingTheGatewayNoLongerCompletesTheAuthorization()
    {
        $this->gateway();

        $this->assertSame(array(), Environment::$redirects);
        $this->assertFalse(get_transient(Gateway::LAST_ERROR_TRANSIENT));
    }

    public function testTheStateIsRequiredEvenWhenNoneWasStored()
    {
        // Nobody started an authorization: any state used to be accepted then.
        $oauth = new OAuthClient($this->http(new Response(200, array(), '{"access_token":"attacker-token"}')));

        $this->expectExceptionMessage('OAuth state mismatch');

        $oauth->get_access_token('client-id', 'client-secret', self::SETTINGS_URL, false, 'authorization-code', 'anything');
    }

    public function testTheStoredStateIsAcceptedOnce()
    {
        $oauth = new OAuthClient($this->http(new Response(200, array(), '{"access_token":"fresh-token"}')));
        set_transient($oauth->get_state_transient_key(), 'stored-state', HOUR_IN_SECONDS);

        $this->assertSame('fresh-token', $oauth->get_access_token('client-id', 'client-secret', self::SETTINGS_URL, false, 'authorization-code', 'stored-state'));
        $this->assertFalse(get_transient($oauth->get_state_transient_key()));
    }

    public function testAnErrorResponseDoesNotRevealTheCredentials()
    {
        // bunq echoes what it was sent.
        $body = '{"Error":[{"error_description":"Invalid hash \"authorization-code-1234\"."},{"error_description":"Invalid hash \"my-client-id\"."},{"error_description":"Invalid hash \"my-client-secret\"."}]}';

        $message = $this->failedExchange(new Response(400, array(), $body));

        $this->assertStringStartsWith('bunq token request failed (HTTP 400: ', $message);
        $this->assertStringContainsString('Invalid hash \"[authorization code]\"', $message);
        $this->assertStringContainsString('Invalid hash \"[client ID]\"', $message);
        $this->assertStringContainsString('Invalid hash \"[client secret]\"', $message);
        $this->assertStringNotContainsString('my-client-secret', $message);
    }

    public function testANetworkErrorDoesNotRevealTheCredentials()
    {
        $url = 'https://api.oauth.bunq.com/v1/token?grant_type=authorization_code&code=authorization-code-1234&client_id=my-client-id&client_secret=my-client-secret';

        $message = $this->failedExchange(new ConnectException('cURL error 7: Failed to connect for ' . $url, new Request('POST', $url)));

        $this->assertSame('bunq token request failed (cURL error 7: Failed to connect for https://api.oauth.bunq.com/v1/token)', $message);
    }

    public function testTheFailureShownOnTheSettingsPageAndLoggedHasNoCredentials()
    {
        set_transient((new OAuthClient())->get_state_transient_key(), 'returned-state', HOUR_IN_SECONDS);
        update_option('woocommerce_bunq_settings', $this->liveSettings(array('oauth_client_id' => 'my-client-id', 'oauth_client_secret' => 'my-client-secret')));
        $oauth = new OAuthClient($this->http(new Response(400, array(), '{"Error":[{"error_description":"Invalid hash \"my-client-secret\"."}]}')));
        $gateway = new Gateway(new FakeBunqApi(), $oauth);

        $gateway->complete_oauth_authorization();

        $this->assertStringContainsString('[client secret]', get_transient(Gateway::LAST_ERROR_TRANSIENT));
        $this->assertStringNotContainsString('my-client-secret', get_transient(Gateway::LAST_ERROR_TRANSIENT));
        $this->assertStringNotContainsString('my-client-secret', json_encode(Environment::$logs));
        $this->assertLogged('error', 'Invalid hash \"[client secret]\"');
    }

    public function testTheSavedSecretsAreKeptOutOfEveryLogMessage()
    {
        $this->gateway(array('oauth_client_secret' => 'live-client-secret', 'test_oauth_client_secret' => 'test-client-secret'));

        Logger::log(new \RuntimeException('Request with live-client-secret, test-client-secret, live-api-key and ' . rawurlencode('test-api-key') . ' failed'));

        $this->assertLogged('error', 'Request with [client secret], [client secret], [API key] and [API key] failed');
        $this->assertSame('Bad [API key]', Logger::format_error(new \RuntimeException('Bad live-api-key')));
    }

    private function gateway(array $settings = array())
    {
        update_option('woocommerce_bunq_settings', $this->liveSettings($settings));

        return new Gateway(new FakeBunqApi(), new OAuthClient($this->http(new Response(200, array(), '{"access_token":"attacker-token"}'))));
    }

    /**
     * @param Response|\Throwable $answer What bunq's token endpoint answers.
     */
    private function failedExchange($answer)
    {
        $oauth = new OAuthClient($this->http($answer));
        set_transient($oauth->get_state_transient_key(), 'stored-state', HOUR_IN_SECONDS);

        try {
            $oauth->get_access_token('my-client-id', 'my-client-secret', self::SETTINGS_URL, false, 'authorization-code-1234', 'stored-state');
        } catch (\Exception $exception) {
            $this->assertStringNotContainsString('my-client-secret', $exception->getMessage());
            $this->assertStringNotContainsString(rawurlencode('my-client-secret'), $exception->getMessage());

            return $exception->getMessage();
        }

        $this->fail('The token exchange should have failed');
    }

    private function http($answer)
    {
        return new Client(array('handler' => HandlerStack::create(new MockHandler(array($answer)))));
    }
}
