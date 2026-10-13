<?php
/**
 * The bunq OAuth authorization.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Bunq;

use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use League\OAuth2\Client\Provider\GenericProvider;
use PatrickKivits\BunqForWooCommerce\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * The OAuth flow that connects the shop to the merchant's bunq account and yields the API key.
 */
class OAuthClient {

	/**
	 * Prefix of the transient that keeps the OAuth state per user.
	 */
	const STATE_TRANSIENT = 'wc_bunq_gateway.oauth2state';

	/**
	 * The HTTP client for the token request; the OAuth library's own by default.
	 *
	 * @var ClientInterface|null
	 */
	private $http_client;

	/**
	 * Constructor.
	 *
	 * @param ClientInterface|null $http_client The HTTP client for the token request.
	 */
	public function __construct( ?ClientInterface $http_client = null ) {
		$this->http_client = $http_client;
	}

	/**
	 * The OAuth provider.
	 *
	 * @param string $client_id     OAuth client id.
	 * @param string $client_secret OAuth client secret.
	 * @param string $redirect_uri  Where bunq sends the merchant back to, as registered in the bunq app.
	 * @param bool   $testmode      Whether to use the sandbox.
	 * @return GenericProvider
	 */
	public function get_provider( $client_id, $client_secret, $redirect_uri, $testmode ) {
		return new GenericProvider(
			array(
				'clientId'                => $client_id,
				'clientSecret'            => $client_secret,
				'redirectUri'             => $redirect_uri,
				'urlAuthorize'            => $testmode ? 'https://oauth.sandbox.bunq.com/auth' : 'https://oauth.bunq.com/auth',
				'urlAccessToken'          => $testmode ? 'https://api-oauth.sandbox.bunq.com/v1/token' : 'https://api.oauth.bunq.com/v1/token',
				'urlResourceOwnerDetails' => null,
			),
			$this->http_client ? array( 'httpClient' => $this->http_client ) : array()
		);
	}

	/**
	 * The transient that keeps the OAuth state of the current user.
	 *
	 * @return string
	 */
	public function get_state_transient_key() {
		$user_id = function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0;

		return self::STATE_TRANSIENT . '.' . $user_id;
	}

	/**
	 * The URL that starts the authorization at bunq. Remembers the state to verify it on return.
	 *
	 * @param string $client_id     OAuth client id.
	 * @param string $client_secret OAuth client secret.
	 * @param string $redirect_uri  Where bunq sends the merchant back to.
	 * @param bool   $testmode      Whether to use the sandbox.
	 * @return string
	 */
	public function get_authorization_url( $client_id, $client_secret, $redirect_uri, $testmode ) {
		$provider = $this->get_provider( $client_id, $client_secret, $redirect_uri, $testmode );

		$authorization_url = $provider->getAuthorizationUrl();

		// WordPress does not start a PHP session, so keep the state in a transient to verify it on return.
		set_transient( $this->get_state_transient_key(), $provider->getState(), HOUR_IN_SECONDS );

		return $authorization_url;
	}

	/**
	 * Exchange the authorization code from the OAuth redirect for an access token.
	 *
	 * @param string $client_id     OAuth client id.
	 * @param string $client_secret OAuth client secret.
	 * @param string $redirect_uri  The redirect URI the authorization was requested with.
	 * @param bool   $testmode      Whether to use the sandbox.
	 * @param string $code          The "code" bunq returned.
	 * @param string $state         The "state" bunq returned.
	 * @return string The access token.
	 * @throws Exception When the state does not match or bunq did not return an access token. The message never
	 *                   contains the client secret, the client id or the code.
	 */
	public function get_access_token( $client_id, $client_secret, $redirect_uri, $testmode, $code, $state ) {
		$provider = $this->get_provider( $client_id, $client_secret, $redirect_uri, $testmode );

		$transient_key  = $this->get_state_transient_key();
		$expected_state = get_transient( $transient_key );
		delete_transient( $transient_key );

		// The state must be the one this user's authorization request stored: without that check anyone could make
		// the shop exchange an authorization code of their own (CSRF).
		if ( ! is_string( $expected_state ) || '' === $expected_state || ! is_string( $state ) || ! hash_equals( $expected_state, $state ) ) {
			throw new Exception( 'OAuth state mismatch. Please click "OAuth Authorization Request" again and complete the flow in one go.' );
		}

		// bunq echoes the submitted values in its error responses, and the HTTP client puts the request URL (which
		// carries them) in its error messages.
		Logger::add_secret( $client_secret, 'client secret' );
		Logger::add_secret( $client_id, 'client ID' );
		Logger::add_secret( $code, 'authorization code' );

		$data = array(
			'grant_type'    => 'authorization_code',
			'code'          => $code,
			'redirect_uri'  => $redirect_uri,
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
		);

		try {
			$token_response = $provider->getHttpClient()->request(
				'POST',
				$provider->getBaseAccessTokenUrl( $data ),
				array(
					'query'   => $data,
					'headers' => array(
						'X-Bunq-Client-Request-Id' => wp_generate_uuid4(),
					),
				)
			);
		} catch ( GuzzleException $exception ) {
			// Never rethrow the HTTP client's exception: its message contains the request URL with the client secret.
			$response = $exception instanceof RequestException ? $exception->getResponse() : null;
			$details  = $response
				? 'HTTP ' . $response->getStatusCode() . ': ' . substr( (string) $response->getBody(), 0, 500 )
				: preg_replace( '#(https?://[^\s?]+)\?\S*#', '$1', $exception->getMessage() );

			throw new Exception( Logger::redact( 'bunq token request failed (' . $details . ')' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Escaped where it is shown.
		}

		$body         = (string) $token_response->getBody();
		$body_decoded = json_decode( $body );

		if ( ! is_object( $body_decoded ) || empty( $body_decoded->access_token ) || ! is_string( $body_decoded->access_token ) ) {
			throw new Exception( Logger::redact( 'bunq did not return an access token. Response: ' . substr( $body, 0, 500 ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Escaped where it is shown.
		}

		Logger::add_secret( $body_decoded->access_token, 'API key' );

		return $body_decoded->access_token;
	}
}
