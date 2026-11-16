<?php

namespace BunqTest;

use PatrickKivits\BunqForWooCommerce\Bunq\ApiClient;
use PatrickKivits\BunqForWooCommerce\Bunq\RequestThrottler;
use PatrickKivits\BunqForWooCommerce\Bunq\RequestThrottlerConfiguration;

/**
 * Stands in for bunq's HTTP API below the ApiClient: answers its requests (through pre_http_request) from a queue,
 * signed with bunq's server key the way bunq signs them, and records every request. Like WordPress' cURL transport
 * it first runs http_api_curl on a cURL handle, so the key pinning of production calls takes part.
 */
final class FakeBunqServer
{
    /** @var array[] Every request: method, url, path (below /v1/, with the query), headers (name => value), body, args, pinned. */
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
        add_filter('pre_http_request', function ($response, $args, $url) {
            if (false !== $response || !preg_match('#^https://(api\.bunq\.com|public-api\.sandbox\.bunq\.com)/v1/#', $url)) {
                return $response;
            }

            // What WordPress' cURL transport does before sending; the handle is not used further.
            $handle = curl_init();
            $pinnedBefore = has_action('http_api_curl');
            do_action_ref_array('http_api_curl', array(&$handle, $args, $url));

            $uri = parse_url($url);
            $this->requests[] = array(
                'method' => $args['method'],
                'url' => $url,
                'path' => substr($uri['path'], strlen('/v1/')) . (isset($uri['query']) ? '?' . $uri['query'] : ''),
                'headers' => $args['headers'],
                'body' => isset($args['body']) ? $args['body'] : '',
                'args' => $args,
                'pinned' => false !== $pinnedBefore,
            );

            if (!$this->answers) {
                throw new \LogicException('Unexpected request: ' . $args['method'] . ' ' . $url);
            }

            list($status, $body, $signingKey) = array_shift($this->answers);
            openssl_sign($body, $signature, $signingKey, OPENSSL_ALGO_SHA256);

            return bunqtest_http_response($status, $body, array('X-Bunq-Server-Signature' => base64_encode($signature), 'X-Bunq-Client-Response-Id' => 'response-id'));
        }, 10, 3);

        return new ApiClient(new RequestThrottler(new RequestThrottlerConfiguration(-100)));
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
