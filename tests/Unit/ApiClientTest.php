<?php

namespace BunqTest;

use PatrickKivits\BunqForWooCommerce\Bunq\ApiClient;
use PatrickKivits\BunqForWooCommerce\Bunq\ApiException;

/**
 * How the API client talks to bunq: the API context, signing and verification, errors and rate limits.
 */
final class ApiClientTest extends TestCase
{
    /** @var FakeBunqServer */
    private $server;

    /** @var ApiClient */
    private $api;

    protected function setUp(): void
    {
        parent::setUp();
        $this->server = new FakeBunqServer();
        $this->api = $this->server->apiClient();
    }

    // --- Calls ----------------------------------------------------------------------------------------------

    public function testACallIsAuthenticatedWithTheSessionAndItsBodySignedWithTheInstallationKey()
    {
        $this->api->load_api_context_from_json(FakeBunqServer::context());
        $this->server->respond(array(array('Id' => array('id' => 5))));

        $response = $this->api->request('POST', 'user/7/monetary-account/123/bunqme-tab', array('description' => 'Café / #1'));

        $this->assertSame(array(array('Id' => array('id' => 5))), $response);
        $request = $this->server->requests[0];
        $this->assertSame('https://public-api.sandbox.bunq.com/v1/user/7/monetary-account/123/bunqme-tab', $request['url']);
        $this->assertSame('session-token', $request['headers']['X-Bunq-Client-Authentication']);
        $this->assertSame('application/json', $request['headers']['Content-Type']);
        $this->assertSame(array('description' => 'Café / #1'), $this->server->body(0));
        $this->assertTrue($this->server->isSignedBy(0, FakeBunqServer::keys()['client']['public']), 'The exact body sent is signed');
        $this->assertArrayNotHasKey('curl', $request['options'], 'The sandbox certificate is not pinned');
    }

    public function testProductionCallsGoToBunqWithItsCertificatePinned()
    {
        $this->api->load_api_context_from_json(FakeBunqServer::context(array('environment_type' => 'PRODUCTION')));
        $this->server->respond(array());

        $this->api->request('GET', 'user/7/monetary-account-bank');

        $request = $this->server->requests[0];
        $this->assertSame('https://api.bunq.com/v1/user/7/monetary-account-bank', $request['url']);
        $this->assertSame('', $request['body']);
        $this->assertSame(array(CURLOPT_PINNEDPUBLICKEY => ApiClient::PINNED_PUBLIC_KEY_FILE), $request['options']['curl']);
        $this->assertStringContainsString('BEGIN PUBLIC KEY', file_get_contents(ApiClient::PINNED_PUBLIC_KEY_FILE));
    }

    public function testAnAnswerNotSignedByBunqIsRejected()
    {
        $this->api->load_api_context_from_json(FakeBunqServer::context());
        $this->server->respond(array(array('Id' => array('id' => 5))), 200, FakeBunqServer::keys()['client']['private']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The signature of bunq\'s answer to GET user/7/monetary-account-bank is invalid.');

        $this->api->request('GET', 'user/7/monetary-account-bank');
    }

    public function testAnErrorAnswerCarriesBunqsDescriptionAndResponseId()
    {
        $this->api->load_api_context_from_json(FakeBunqServer::context());
        $this->server->respondWithError(403, 'Not enough permissions to create payment.');
        $this->server->respondWithError(404, 'Payment for id "1" not found.');

        try {
            $this->api->request('POST', 'user/7/monetary-account/123/payment', array());
            $this->fail('The error should have been thrown');
        } catch (ApiException $exception) {
            $this->assertSame('Not enough permissions to create payment. (HTTP 403, response id response-id)', $exception->getMessage());
            $this->assertSame(403, $exception->getCode());
            $this->assertSame('response-id', $exception->get_response_id());
            $this->assertFalse($exception->is_authentication_error(), 'A 403 about permissions is not about the session');
            $this->assertFalse($exception->is_context_rejected());
        }

        try {
            $this->api->get_payment(1, 123);
            $this->fail('The error should have been thrown');
        } catch (ApiException $exception) {
            $this->assertSame(404, $exception->getCode());
        }

        $this->assertCount(2, $this->server->requests, 'Errors other than rate limits and ended sessions are not retried');
        $this->assertSame(0, $this->api->get_call_count());
    }

    public function testASessionBunqEndedEarlyIsRenewedAndOnlyTheRejectedCallRepeated()
    {
        $saved = array();
        $this->api->load_api_context_from_json(FakeBunqServer::context(), function ($json) use (&$saved) {
            $saved[] = $json;
        });
        $this->server
            ->respond(array(array('Id' => array('id' => 55))))
            ->respondWithError(403, 'Insufficient authentication.')
            ->respond($this->companySession('renewed-session-token'))
            ->respond(array(array('Id' => array('id' => 56))));

        $this->api->request('POST', 'user/7/monetary-account/123/bunqme-tab', array('description' => 'first'));
        $response = $this->api->request('POST', 'user/7/monetary-account/123/bunqme-tab', array('description' => 'second'));

        $this->assertSame(array(array('Id' => array('id' => 56))), $response);
        $this->assertSame(array('bunqme-tab', 'bunqme-tab', 'session-server', 'bunqme-tab'), array_map('basename', array_column($this->server->requests, 'path')));
        $this->assertSame(array('first', 'second', null, 'second'), array_map(function ($index) {
            $body = $this->server->body($index);

            return isset($body['description']) ? $body['description'] : null;
        }, array_keys($this->server->requests)));
        $this->assertSame('renewed-session-token', $this->server->requests[3]['headers']['X-Bunq-Client-Authentication']);
        $this->assertCount(1, $saved, 'The renewed context is stored');
        $this->assertSame('renewed-session-token', json_decode($saved[0], true)['session_context']['token']);
        $this->assertSame(2, $this->api->get_call_count());
        $this->assertLogged('warning', 'bunq rejected the session, opening a new one: Insufficient authentication.');
    }

    public function testWhenBunqRefusesANewSessionTheContextIsRejected()
    {
        $this->api->load_api_context_from_json(FakeBunqServer::context());
        $this->server
            ->respondWithError(403, 'Insufficient authentication.')
            ->respondWithError(400, 'User credentials are incorrect. Incorrect API key or IP address.');

        try {
            $this->api->request('GET', 'user/7/monetary-account-bank');
            $this->fail('The error should have been thrown');
        } catch (ApiException $exception) {
            $this->assertTrue($exception->is_context_rejected());
            $this->assertSame(400, $exception->getCode());
            $this->assertStringStartsWith('User credentials are incorrect.', $exception->getMessage());
        }

        $this->assertCount(2, $this->server->requests, 'The call is not repeated without a session');
    }

    /**
     * @group slow
     */
    public function testASessionThatExpiresDuringTheRequestIsRenewedBeforeTheNextCall()
    {
        $json = FakeBunqServer::context(array('session_context' => array('expiry_time' => gmdate('Y-m-d H:i:s.u', time() + 32))));
        $this->assertTrue($this->api->load_api_context_from_json($json));
        sleep(3);
        $this->server->respond($this->companySession('renewed-session-token'))->respond(array());

        $this->api->request('GET', 'user/7/monetary-account-bank');

        $this->assertSame(array('session-server', 'user/7/monetary-account-bank'), array_column($this->server->requests, 'path'));
        $this->assertSame('renewed-session-token', $this->server->requests[1]['headers']['X-Bunq-Client-Authentication']);
    }

    public function testACallNeedsALoadedApiContext()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No bunq API context is loaded.');

        $this->api->request('GET', 'user');
    }

    /**
     * @group slow
     */
    public function testARateLimitedCallIsRetriedAfterThreeSeconds()
    {
        $this->api->load_api_context_from_json(FakeBunqServer::context());
        $this->server->respondWithError(429, 'Too many requests.');
        $this->server->respond(array(array('Id' => array('id' => 5))));

        $start = microtime(true);
        $response = $this->api->request('GET', 'user/7/monetary-account-bank');

        $this->assertSame(array(array('Id' => array('id' => 5))), $response);
        $this->assertCount(2, $this->server->requests);
        $this->assertNotSame($this->server->requests[0]['headers']['X-Bunq-Client-Request-Id'], $this->server->requests[1]['headers']['X-Bunq-Client-Request-Id'], 'bunq rejects a request id it saw before');
        $this->assertGreaterThanOrEqual(3.0, microtime(true) - $start);
        $this->assertSame(array('bunq rate limit hit, retrying in 3 seconds (attempt 1 of 3)'), Environment::logMessages('warning'));
    }

    /**
     * @group slow
     */
    public function testARateLimitedCallGivesUpAfterThreeAttempts()
    {
        $this->api->load_api_context_from_json(FakeBunqServer::context());
        $this->server->respondWithError(429, 'Too many requests.')->respondWithError(429, 'Too many requests.')->respondWithError(429, 'Too many requests.');

        try {
            $this->api->request('GET', 'user/7/monetary-account-bank');
            $this->fail('The rate limit error should have been thrown');
        } catch (ApiException $exception) {
            $this->assertSame(429, $exception->getCode());
        }

        $this->assertCount(3, $this->server->requests);
    }

    // --- The API context ------------------------------------------------------------------------------------

    public function testCreatingAnApiContextInstallsAKeyRegistersThisServerAndOpensASession()
    {
        $this->server
            ->respond(array(
                array('Id' => array('id' => 1)),
                array('Token' => array('id' => 2, 'token' => 'installation-token')),
                array('ServerPublicKey' => array('server_public_key' => FakeBunqServer::keys()['server']['public'])),
            ))
            ->respond(array(array('Id' => array('id' => 3))))
            ->respond($this->oauthSession('session-token'));

        $json = $this->api->create_api_context('oauth-access-token', true);

        $this->assertSame(array('installation', 'device-server', 'session-server'), array_column($this->server->requests, 'path'));

        // The installation registers the public key of the key pair the context keeps; it is the only unsigned call.
        $context = json_decode($json, true);
        $installation = $this->server->requests[0];
        $client_public_key = openssl_pkey_get_details(openssl_pkey_get_private($context['installation_context']['private_key_client']))['key'];
        $this->assertSame(array('client_public_key' => $client_public_key), $this->server->body(0));
        $this->assertArrayNotHasKey('X-Bunq-Client-Authentication', $installation['headers']);
        $this->assertArrayNotHasKey('X-Bunq-Client-Signature', $installation['headers']);

        $this->assertSame(array('description' => 'bunq for WooCommerce', 'secret' => 'oauth-access-token', 'permitted_ips' => array()), $this->server->body(1));
        $this->assertSame(array('secret' => 'oauth-access-token'), $this->server->body(2));
        foreach (array(1, 2) as $index) {
            $this->assertSame('installation-token', $this->server->requests[$index]['headers']['X-Bunq-Client-Authentication']);
            $this->assertTrue($this->server->isSignedBy($index, $client_public_key));
        }

        $this->assertSame('oauth-access-token', $context['api_key']);
        $this->assertSame('SANDBOX', $context['environment_type']);
        $this->assertSame('installation-token', $context['installation_context']['token']);
        $this->assertSame(FakeBunqServer::keys()['server']['public'], $context['installation_context']['public_key_server']);
        $this->assertSame('session-token', $context['session_context']['token']);
        $this->assertSame(42, $context['session_context']['user_id'], 'An OAuth session acts as the API key\'s user');
        $this->assertSame('Kivits Webshop', $context['session_context']['display_name'], 'The user that granted access, not the app');
        $this->assertEqualsWithDelta(time() + 3600, $this->expiry($json), 2, 'The app\'s session timeout, not the merchant\'s');
        $this->assertSame('Kivits Webshop', $this->api->get_account_name($json));

        // The new context is loaded for the calls that follow.
        $this->server->respond(array());
        $this->api->request('GET', 'user/42/monetary-account-bank');
        $this->assertSame('session-token', $this->server->requests[3]['headers']['X-Bunq-Client-Authentication']);
    }

    public function testACreationBunqRejectsLeavesNoContextLoaded()
    {
        $this->server
            ->respond(array(
                array('Id' => array('id' => 1)),
                array('Token' => array('id' => 2, 'token' => 'installation-token')),
                array('ServerPublicKey' => array('server_public_key' => FakeBunqServer::keys()['server']['public'])),
            ))
            ->respondWithError(400, 'User credentials are incorrect. Incorrect API key or IP address.');

        try {
            $this->api->create_api_context('revoked-key', false);
            $this->fail('The error should have been thrown');
        } catch (ApiException $exception) {
            $this->assertStringStartsWith('User credentials are incorrect. Incorrect API key or IP address.', $exception->getMessage());
        }

        $this->expectExceptionMessage('No bunq API context is loaded.');
        $this->api->request('GET', 'user');
    }

    public function testAStoredContextWhoseSessionLastsAnotherMinuteIsUsedWithoutCallingBunq()
    {
        $json = FakeBunqServer::context(array('session_context' => array('expiry_time' => gmdate('Y-m-d H:i:s.u', time() + 60))));

        $saved = array();
        $this->assertTrue($this->api->load_api_context_from_json($json, function ($json) use (&$saved) {
            $saved[] = $json;
        }));
        $this->assertSame(array(), $this->server->requests);
        $this->assertSame(array(), $saved, 'Nothing changed to store');
    }

    public function testASessionThatIsAboutToExpireIsRenewedWhenTheContextIsLoaded()
    {
        $json = FakeBunqServer::context(array('session_context' => array('expiry_time' => gmdate('Y-m-d H:i:s.u', time() + 10))));
        $this->server->respond($this->companySession('renewed-session-token'));
        $saved = array();

        $this->assertTrue($this->api->load_api_context_from_json($json, function ($json) use (&$saved) {
            $saved[] = $json;
        }));

        $this->assertCount(1, $saved);
        $renewed = $saved[0];

        $this->assertSame(array('session-server'), array_column($this->server->requests, 'path'));
        $this->assertSame('installation-token', $this->server->requests[0]['headers']['X-Bunq-Client-Authentication']);
        $this->assertSame(array('secret' => 'api-key'), $this->server->body(0));
        $this->assertSame(json_decode($json, true)['installation_context'], json_decode($renewed, true)['installation_context']);
        $this->assertSame('renewed-session-token', json_decode($renewed, true)['session_context']['token']);
        $this->assertEqualsWithDelta(time() + 600, $this->expiry($renewed), 2);
        $this->assertSame('Renamed B.V.', $this->api->get_account_name($renewed));

        $this->server->respond(array());
        $this->api->request('GET', 'user/7/monetary-account-bank');
        $this->assertSame('renewed-session-token', $this->server->requests[1]['headers']['X-Bunq-Client-Authentication']);
    }

    public function testAContextWhoseSessionBunqWillNotRenewCannotBeUsed()
    {
        $json = FakeBunqServer::context(array('session_context' => array('expiry_time' => '2020-01-01 00:00:00.000000')));
        $this->server->respondWithError(403, 'Insufficient authentication.');

        $this->assertFalse($this->api->load_api_context_from_json($json));
        $this->assertLogged('error', 'Insufficient authentication.');
    }

    public function testAContextStoredByTheBunqSdkKeepsWorking()
    {
        // As bunq/sdk_php 1.x stored it: users of the session instead of their name, and fields the client ignores.
        $json = json_encode(array(
            'api_key' => 'api-key',
            'environment_type' => 'SANDBOX',
            'installation_context' => array(
                'token' => 'installation-token',
                'private_key_client' => FakeBunqServer::keys()['client']['private'],
                'public_key_client' => FakeBunqServer::keys()['client']['public'],
                'public_key_server' => FakeBunqServer::keys()['server']['public'],
            ),
            'proxy_url' => null,
            'session_context' => array(
                'token' => 'sdk-session-token',
                'expiry_time' => gmdate('Y-m-d H:i:s.u', time() + DAY_IN_SECONDS),
                'user_id' => 42,
                'userCompany' => null,
                'userPerson' => null,
                'userApiKey' => array(
                    'id' => 42,
                    'requested_by_user' => array('user_payment_service_provider' => array('id' => 1, 'display_name' => 'bunq for WooCommerce')),
                    'granted_by_user' => array('user_company' => array('id' => 7, 'display_name' => 'Kivits Webshop')),
                ),
                'userPaymentServiceProvider' => null,
            ),
        ), JSON_PRETTY_PRINT);

        $this->assertTrue($this->api->load_api_context_from_json($json));
        $this->assertSame('Kivits Webshop', $this->api->get_account_name($json));

        $this->server->respond(array());
        $this->api->request('GET', 'user/42/monetary-account-bank');
        $this->assertSame('sdk-session-token', $this->server->requests[0]['headers']['X-Bunq-Client-Authentication']);
        $this->assertTrue($this->server->isSignedBy(0, FakeBunqServer::keys()['client']['public']));
    }

    public function testTheAccountNameIsReadFromTheStoredApiContext()
    {
        $this->assertSame('Kivits Webshop', $this->api->get_account_name(FakeBunqServer::context()));
        // As the bunq SDK stored a plain API key's session (the sandbox): the key's own user.
        $this->assertSame('Sandbox B.V.', $this->api->get_account_name(json_encode(array('session_context' => array('userCompany' => array('id' => 7, 'display_name' => 'Sandbox B.V.'), 'userPerson' => null)))));
        $this->assertSame('J. Doe', $this->api->get_account_name(json_encode(array('session_context' => array('userPerson' => array('id' => 7, 'display_name' => 'J. Doe'))))));

        $this->assertNull($this->api->get_account_name(FakeBunqServer::context(array('session_context' => array('display_name' => null)))));
        $this->assertNull($this->api->get_account_name(json_encode(array('session_context' => array()))));
        $this->assertNull($this->api->get_account_name('not json'));
        $this->assertSame(array(), $this->server->requests);
    }

    public function testLoadingAnApiContextFromInvalidJsonFailsAndIsLogged()
    {
        $this->assertFalse($this->api->load_api_context_from_json('not json'));
        $this->assertFalse($this->api->load_api_context_from_json(''));

        $this->assertCount(1, Environment::$logs, 'Only the invalid JSON is logged; an empty context is a normal state');
        $this->assertSame('error', Environment::$logs[0]['level']);
        $this->assertSame('bunq', Environment::$logs[0]['source']);
        $this->assertStringContainsString('The stored bunq API context is not valid JSON: Syntax error', Environment::$logs[0]['message']);
    }

    public function testLoadingAnIncompleteApiContextNamesWhatIsMissing()
    {
        $context = json_decode(FakeBunqServer::context(), true);
        unset($context['installation_context']['public_key_server']);

        $this->assertFalse($this->api->load_api_context_from_json(json_encode($context)));
        $this->assertLogged('error', 'The stored bunq API context has no installation_context.public_key_server.');
    }

    // --- Helpers --------------------------------------------------------------------------------------------

    /**
     * @dataProvider descriptions
     */
    public function testPaymentDescriptionOnlyKeepsWhatSepaAllows($description, $expected)
    {
        $this->assertSame($expected, ApiClient::sanitize_payment_description($description));
    }

    public function descriptions()
    {
        return array(
            'plain text' => array('Refund order 123 Test Shop', 'Refund order 123 Test Shop'),
            'hash sign' => array('Refund order #123 Test Shop', 'Refund order 123 Test Shop'),
            'emoji' => array('Thanks 🙏 for your order 🎉', 'Thanks for your order'),
            'allowed punctuation' => array("Order 12/3 - ref? (x): 1,5 'a' +b.", "Order 12/3 - ref? (x): 1,5 'a' +b."),
            'accented letters' => array('Café Zürich Ærø', 'Café Zürich Ærø'),
            'other punctuation' => array('A & B "C" _D_ E*F G=H I;J K!L', 'A B C D E F G H I J K L'),
            'multiple spaces' => array('a    b', 'a b'),
            'whitespace characters' => array("a\tb\nc\r\nd", 'a b c d'),
            'surrounding whitespace' => array('   padded   ', 'padded'),
            'only rejected characters' => array('###', ''),
            'empty string' => array('', ''),
            'null' => array(null, ''),
            'number' => array(123, '123'),
        );
    }

    /**
     * bunq's answer to session-server for an OAuth access token: the session acts as the API key, requested by the
     * OAuth app and granted by the merchant.
     */
    private function oauthSession($token)
    {
        return array(
            array('Id' => array('id' => 4)),
            array('Token' => array('id' => 4, 'token' => $token)),
            array('UserApiKey' => array(
                'id' => 42,
                'requested_by_user' => array('UserPaymentServiceProvider' => array('id' => 1, 'display_name' => 'bunq for WooCommerce', 'session_timeout' => 3600)),
                'granted_by_user' => array('UserCompany' => array('id' => 7, 'display_name' => 'Kivits Webshop', 'session_timeout' => 604800)),
            )),
        );
    }

    /**
     * bunq's answer to session-server for a plain API key of a company with a 10 minute session timeout.
     */
    private function companySession($token)
    {
        return array(
            array('Id' => array('id' => 9)),
            array('Token' => array('id' => 9, 'token' => $token)),
            array('UserCompany' => array('id' => 7, 'display_name' => 'Renamed B.V.', 'session_timeout' => 600)),
        );
    }

    /**
     * The session expiry of a stored context as timestamp.
     */
    private function expiry($json)
    {
        return \DateTime::createFromFormat('Y-m-d H:i:s.u', json_decode($json, true)['session_context']['expiry_time'], new \DateTimeZone('UTC'))->getTimestamp();
    }
}
