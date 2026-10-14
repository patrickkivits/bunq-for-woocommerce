<?php

namespace BunqTest;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use PatrickKivits\BunqForWooCommerce\Bunq\ApiClient;
use PatrickKivits\BunqForWooCommerce\Bunq\RequestThrottler;
use PatrickKivits\BunqForWooCommerce\Bunq\RequestThrottlerConfiguration;
use Psr\Http\Message\RequestInterface;

/**
 * Stands in for bunq's HTTP API below the ApiClient: answers its requests from a queue, signed with bunq's server key
 * the way bunq signs them, and records every request.
 */
final class FakeBunqServer
{
    /** @var array[] Every request: method, url, path (below /v1/, with the query), headers (name => line), body, options. */
    public $requests = array();

    /** @var array[] The queued answers as array(status, body, signing key). */
    private $answers = array();

    /** @var array The key pairs of the client ("client") and of bunq ("server"), generated once per run. */
    private static $keys;

    public static function keys()
    {
        if (!self::$keys) {
            self::$keys = array('client' => ApiClient::generate_key_pair(), 'server' => ApiClient::generate_key_pair());
        }

        return self::$keys;
    }

    /**
     * Queue an answer.
     *
     * @param array       $body       The "Response" list of a 200 answer, or the complete body of an error.
     * @param int         $status     HTTP status.
     * @param string|null $signingKey The private key that signs the answer; bunq's own by default.
     */
    public function respond(array $body, $status = 200, $signingKey = null)
    {
        $this->answers[] = array($status, json_encode($status === 200 ? array('Response' => $body) : $body), $signingKey ?: self::keys()['server']['private']);

        return $this;
    }

    /**
     * Queue bunq's error answer.
     */
    public function respondWithError($status, $description)
    {
        return $this->respond(array('Error' => array(array('error_description' => $description, 'error_description_translated' => $description))), $status);
    }

    /**
     * An API client that talks to this server, without pacing the calls.
     */
    public function apiClient()
    {
        $http = new Client(array('handler' => function (RequestInterface $request, array $options) {
            $uri = $request->getUri();
            $headers = array();
            foreach ($request->getHeaders() as $name => $values) {
                $headers[$name] = implode(', ', $values);
            }

            $this->requests[] = array(
                'method' => $request->getMethod(),
                'url' => (string) $uri,
                'path' => substr($uri->getPath(), strlen('/v1/')) . ($uri->getQuery() !== '' ? '?' . $uri->getQuery() : ''),
                'headers' => $headers,
                'body' => (string) $request->getBody(),
                'options' => $options,
            );

            if (!$this->answers) {
                throw new \LogicException('Unexpected request: ' . $request->getMethod() . ' ' . $uri);
            }

            list($status, $body, $signingKey) = array_shift($this->answers);
            openssl_sign($body, $signature, $signingKey, OPENSSL_ALGO_SHA256);

            return Create::promiseFor(new Response($status, array('X-Bunq-Server-Signature' => base64_encode($signature), 'X-Bunq-Client-Response-Id' => 'response-id'), $body));
        }));

        return new ApiClient(new RequestThrottler(new RequestThrottlerConfiguration(-100)), $http);
    }

    /**
     * A stored API context for user 7 whose session is active for another day.
     *
     * @param array $overrides Replaces parts of the context; "session_context" is merged into the session.
     */
    public static function context(array $overrides = array())
    {
        $session = array(
            'token' => 'session-token',
            'expiry_time' => gmdate('Y-m-d H:i:s.u', time() + DAY_IN_SECONDS),
            'user_id' => 7,
            'display_name' => 'Kivits Webshop',
        );

        if (isset($overrides['session_context'])) {
            $session = array_merge($session, $overrides['session_context']);
            unset($overrides['session_context']);
        }

        return json_encode(array_merge(array(
            'api_key' => 'api-key',
            'environment_type' => 'SANDBOX',
            'installation_context' => array(
                'token' => 'installation-token',
                'private_key_client' => self::keys()['client']['private'],
                'public_key_server' => self::keys()['server']['public'],
            ),
            'session_context' => $session,
        ), $overrides));
    }

    /**
     * A request's body as array.
     */
    public function body($index)
    {
        return json_decode($this->requests[$index]['body'], true);
    }

    /**
     * Whether a request carries a valid signature of its body by the key pair's private key.
     */
    public function isSignedBy($index, $publicKey)
    {
        $request = $this->requests[$index];

        return isset($request['headers']['X-Bunq-Client-Signature'])
            && openssl_verify($request['body'], base64_decode($request['headers']['X-Bunq-Client-Signature']), $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }
}
