<?php

namespace BunqTest;

/**
 * The OAuth flow up to the point where bunq would be contacted.
 */
final class OAuth2Test extends TestCase
{
    public function testTheProviderUsesTheSandboxInTestMode()
    {
        $provider = bunq_oauth2_get_provider('client-id', 'client-secret', 'https://shop.example/settings', true);

        $this->assertSame('https://oauth.sandbox.bunq.com/auth', $provider->getBaseAuthorizationUrl());
        $this->assertSame('https://api-oauth.sandbox.bunq.com/v1/token', $provider->getBaseAccessTokenUrl(array()));
    }

    public function testTheProviderUsesProductionOtherwise()
    {
        $provider = bunq_oauth2_get_provider('client-id', 'client-secret', 'https://shop.example/settings', false);

        $this->assertSame('https://oauth.bunq.com/auth', $provider->getBaseAuthorizationUrl());
        $this->assertSame('https://api.oauth.bunq.com/v1/token', $provider->getBaseAccessTokenUrl(array()));
    }

    public function testTheStateIsKeptPerUser()
    {
        Environment::$currentUserId = 7;
        $this->assertSame('wc_bunq_gateway.oauth2state.7', bunq_oauth2_get_state_transient_key());

        Environment::$currentUserId = 12;
        $this->assertSame('wc_bunq_gateway.oauth2state.12', bunq_oauth2_get_state_transient_key());
    }

    public function testTheAuthorizationUrlCarriesTheClientAndStoresTheStateForTheReturn()
    {
        Environment::$currentUserId = 7;

        $url = bunq_oauth2_get_authorization_url('client-id', 'client-secret', 'https://shop.example/settings?section=bunq', false);

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
        $first = bunq_oauth2_get_authorization_url('client-id', 'client-secret', 'https://shop.example/settings', false);
        $second = bunq_oauth2_get_authorization_url('client-id', 'client-secret', 'https://shop.example/settings', false);

        $this->assertNotSame($this->stateOf($first), $this->stateOf($second));
        $this->assertSame($this->stateOf($second), get_transient('wc_bunq_gateway.oauth2state.0'), 'The latest state wins');
    }

    public function testTheTokenExchangeRefusesAReturnWithoutState()
    {
        set_transient(bunq_oauth2_get_state_transient_key(), 'expected-state', HOUR_IN_SECONDS);
        $_GET = array('code' => 'authorization-code');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('OAuth state mismatch');

        bunq_oauth2_get_access_token('client-id', 'client-secret', 'https://shop.example/settings', false);
    }

    public function testTheTokenExchangeRefusesAReturnWithAnotherState()
    {
        set_transient(bunq_oauth2_get_state_transient_key(), 'expected-state', HOUR_IN_SECONDS);
        $_GET = array('code' => 'authorization-code', 'state' => 'forged-state');

        try {
            bunq_oauth2_get_access_token('client-id', 'client-secret', 'https://shop.example/settings', false);
            $this->fail('The state mismatch should have been rejected');
        } catch (\Exception $exception) {
            $this->assertStringContainsString('OAuth state mismatch', $exception->getMessage());
        }

        $this->assertFalse(get_transient(bunq_oauth2_get_state_transient_key()), 'The state is single use');
    }

    private function stateOf($url)
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        return $query['state'];
    }
}
