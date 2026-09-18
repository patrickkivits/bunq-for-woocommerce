<?php

const BUNQ_OAUTH2_STATE_TRANSIENT = 'wc_bunq_gateway.oauth2state';

function bunq_oauth2_get_provider($clientId, $clientSecret, $redirectUri, $testmode) {
    return new \League\OAuth2\Client\Provider\GenericProvider([
        'clientId'                => $clientId,
        'clientSecret'            => $clientSecret,
        'redirectUri'             => $redirectUri,
        'urlAuthorize'            => $testmode ? 'https://oauth.sandbox.bunq.com/auth' : 'https://oauth.bunq.com/auth',
        'urlAccessToken'          => $testmode ? 'https://api-oauth.sandbox.bunq.com/v1/token' : 'https://api.oauth.bunq.com/v1/token',
        'urlResourceOwnerDetails' => null
    ]);
}

function bunq_oauth2_get_state_transient_key()
{
    $user_id = function_exists('get_current_user_id') ? get_current_user_id() : 0;

    return BUNQ_OAUTH2_STATE_TRANSIENT . '.' . $user_id;
}

function bunq_oauth2_get_authorization_url($clientId, $clientSecret, $redirectUri, $testmode)
{
    $provider = bunq_oauth2_get_provider($clientId, $clientSecret, $redirectUri, $testmode);

    $authorizationUrl = $provider->getAuthorizationUrl();

    // WordPress does not start a PHP session, so keep the state in a transient to verify it on return.
    set_transient(bunq_oauth2_get_state_transient_key(), $provider->getState(), HOUR_IN_SECONDS);

    return $authorizationUrl;
}

/**
 * Exchange the authorization code from the OAuth redirect for an access token.
 *
 * @return string The access token.
 * @throws Exception When the state does not match or bunq did not return an access token.
 */
function bunq_oauth2_get_access_token($clientId, $clientSecret, $redirectUri, $testmode)
{
    $provider = bunq_oauth2_get_provider($clientId, $clientSecret, $redirectUri, $testmode);

    $transient_key = bunq_oauth2_get_state_transient_key();
    $expected_state = get_transient($transient_key);
    delete_transient($transient_key);

    // Check given state against previously stored one to mitigate CSRF attack
    if (empty($_GET['state']) || ($expected_state && $_GET['state'] !== $expected_state)) {
        throw new Exception('OAuth state mismatch. Please click "OAuth Authorization Request" again and complete the flow in one go.');
    }

    $data = [
        'grant_type' => 'authorization_code',
        'code' => $_GET['code'],
        'redirect_uri' => $redirectUri,
        'client_id' => $clientId,
        'client_secret' => $clientSecret
    ];
    $http_client = $provider->getHttpClient();

    try {
        $token_response = $http_client->request('POST', $provider->getBaseAccessTokenUrl($data), [
            'query' => $data,
            'headers' => [
                'X-Bunq-Client-Request-Id' => wp_generate_uuid4()
            ]
        ]);
    } catch (\GuzzleHttp\Exception\RequestException $exception) {
        // Do not rethrow the Guzzle exception as-is: its message contains the request URL including the client secret.
        $response = $exception->getResponse();
        $details = $response
            ? 'HTTP ' . $response->getStatusCode() . ': ' . substr((string) $response->getBody(), 0, 500)
            : str_replace($clientSecret, '***', $exception->getMessage());

        throw new Exception('bunq token request failed (' . $details . ')');
    }

    $body = $token_response->getBody()->getContents();
    $bodyDecoded = json_decode($body);

    if (!is_object($bodyDecoded) || empty($bodyDecoded->access_token)) {
        throw new Exception('bunq did not return an access token. Response: ' . substr($body, 0, 500));
    }

    return $bodyDecoded->access_token;
}
