<?php

namespace BunqTest;

use PatrickKivits\BunqForWooCommerce\Bunq\OAuthClient;

/**
 * The OAuth flow up to the point where bunq would be contacted.
 */
final class OAuthClientTest extends TestCase
{
    public function testTheAuthorizationUsesTheSandboxInTestMode()
    {
        $url = (new OAuthClient())->get_authorization_url('client-id', 'client-secret', 'https://shop.example/settings', true);

        $this->assertStringStartsWith('https://oauth.sandbox.bunq.com/auth?', $url);
    }

    public function testTheTokenRequestGoesToTheSandboxInTestModeAndToProductionOtherwise()
    {
        $urls = array();
        add_filter('pre_http_request', function ($response, $args, $url) use (&$urls) {
            $urls[] = strtok($url, '?');

            return bunqtest_http_response(200, '{"access_token":"token"}');
        }, 10, 3);

        foreach (array(true, false) as $testmode) {
            set_transient((new OAuthClient())->get_state_transient_key(), 'expected-state', HOUR_IN_SECONDS);
            (new OAuthClient())->get_access_token('client-id', 'client-secret', 'https://shop.example/settings', $testmode, 'code', 'expected-state');
        }

        $this->assertSame(array('https://api-oauth.sandbox.bunq.com/v1/token', 'https://api.oauth.bunq.com/v1/token'), $urls);
    }

    public function testTheStateIsKeptPerUser()
    {
        Environment::$currentUserId = 7;
        $this->assertSame('wc_bunq_gateway.oauth2state.7', (new OAuthClient())->get_state_transient_key());

        Environment::$currentUserId = 12;
        $this->assertSame('wc_bunq_gateway.oauth2state.12', (new OAuthClient())->get_state_transient_key());
    }

    public function testTheAuthorizationUrlCarriesTheClientAndStoresTheStateForTheReturn()
    {
        Environment::$currentUserId = 7;

        $url = (new OAuthClient())->get_authorization_url('client-id', 'client-secret', 'https://shop.example/settings?section=bunq', false);

        $parts = parse_url($url);
        parse_str($parts['query'], $query);

        $this->assertSame('oauth.bunq.com', $parts['host']);
        $this->assertSame('/auth', $parts['path']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('client-id', $query['client_id']);
        $this->assertSame('https://shop.example/settings?section=bunq', $query['redirect_uri']);
        $this->assertNotEmpty($query['state']);

        $this->assertSame($query['state'], get_transient('wc_bunq_gateway.oauth2state.7'));
        $this->assertSame(HOUR_IN_SECONDS, Environment::$transientTtls['wc_bunq_gateway.oauth2state.7']);
    }

    public function testEveryAuthorizationRequestGetsANewState()
    {
        $first = (new OAuthClient())->get_authorization_url('client-id', 'client-secret', 'https://shop.example/settings', false);
        $second = (new OAuthClient())->get_authorization_url('client-id', 'client-secret', 'https://shop.example/settings', false);

        $this->assertNotSame($this->stateOf($first), $this->stateOf($second));
        $this->assertSame($this->stateOf($second), get_transient('wc_bunq_gateway.oauth2state.0'), 'The latest state wins');
    }

    public function testTheTokenExchangeRefusesAReturnWithoutState()
    {
        set_transient((new OAuthClient())->get_state_transient_key(), 'expected-state', HOUR_IN_SECONDS);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OAuth state mismatch');

        (new OAuthClient())->get_access_token('client-id', 'client-secret', 'https://shop.example/settings', false, 'authorization-code', '');
    }

    public function testTheTokenExchangeRefusesAReturnWithAnotherState()
    {
        set_transient((new OAuthClient())->get_state_transient_key(), 'expected-state', HOUR_IN_SECONDS);

        try {
            (new OAuthClient())->get_access_token('client-id', 'client-secret', 'https://shop.example/settings', false, 'authorization-code', 'forged-state');
            $this->fail('The state mismatch should have been rejected');
        } catch (\Exception $exception) {
            $this->assertStringContainsString('OAuth state mismatch', $exception->getMessage());
        }

        $this->assertFalse(get_transient((new OAuthClient())->get_state_transient_key()), 'The state is single use');
    }

    private function stateOf($url)
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        return $query['state'];
    }
}
