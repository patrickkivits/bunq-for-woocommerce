<?php
/**
 * The bunq OAuth authorization.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Bunq;

use Exception;
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

	const AUTHORIZE_URL_PRODUCTION = 'https://oauth.bunq.com/auth';
	const AUTHORIZE_URL_SANDBOX    = 'https://oauth.sandbox.bunq.com/auth';
	const TOKEN_URL_PRODUCTION     = 'https://api.oauth.bunq.com/v1/token';
	const TOKEN_URL_SANDBOX        = 'https://api-oauth.sandbox.bunq.com/v1/token';

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
		$state = bin2hex( random_bytes( 16 ) );

		// WordPress does not start a PHP session, so keep the state in a transient to verify it on return.
		set_transient( $this->get_state_transient_key(), $state, HOUR_IN_SECONDS );

		return ( $testmode ? self::AUTHORIZE_URL_SANDBOX : self::AUTHORIZE_URL_PRODUCTION ) . '?' . http_build_query(
			array(
				'response_type' => 'code',
				'client_id'     => $client_id,
				'redirect_uri'  => $redirect_uri,
				'state'         => $state,
			),
			'',
			'&',
			PHP_QUERY_RFC3986
		);
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
		$transient_key  = $this->get_state_transient_key();
		$expected_state = get_transient( $transient_key );
		delete_transient( $transient_key );

		// The state must be the one this user's authorization request stored: without that check anyone could make
		// the shop exchange an authorization code of their own (CSRF).
		if ( ! is_string( $expected_state ) || '' === $expected_state || ! is_string( $state ) || ! hash_equals( $expected_state, $state ) ) {
			throw new Exception( 'OAuth state mismatch. Please click "Connect to bunq" again and complete the flow in one go.' );
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

		// bunq takes the parameters of the token request in the query string.
		$response = wp_remote_post(
			( $testmode ? self::TOKEN_URL_SANDBOX : self::TOKEN_URL_PRODUCTION ) . '?' . http_build_query( $data, '', '&', PHP_QUERY_RFC3986 ),
			array(
				'timeout' => 30,
				'headers' => array(
					'X-Bunq-Client-Request-Id' => wp_generate_uuid4(),
				),
			)
		);
		$status   = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

		if ( $status < 200 || $status >= 300 ) {
			// Error messages of the HTTP API can contain the request URL, which carries the client secret.
			$details = is_wp_error( $response )
				? preg_replace( '#(https?://[^\s?]+)\?\S*#', '$1', $response->get_error_message() )
				: 'HTTP ' . $status . ': ' . self::error_descriptions( wp_remote_retrieve_body( $response ) );

			throw new Exception( Logger::redact( 'bunq token request failed (' . $details . ')' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Escaped where it is shown.
		}

		$body         = wp_remote_retrieve_body( $response );
		$body_decoded = json_decode( $body );

		if ( ! is_object( $body_decoded ) || empty( $body_decoded->access_token ) || ! is_string( $body_decoded->access_token ) ) {
			throw new Exception( Logger::redact( 'bunq did not return an access token. Response: ' . substr( $body, 0, 500 ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Escaped where it is shown.
		}

		Logger::add_secret( $body_decoded->access_token, 'API key' );

		return $body_decoded->access_token;
	}

	/**
	 * The error descriptions of a bunq error response ({"Error":[{"error_description":"..."}]}), or the start of the
	 * response when it is not one.
	 *
	 * @param string $body Response body.
	 * @return string
	 */
	private static function error_descriptions( string $body ): string {
		$decoded      = json_decode( $body, true );
		$descriptions = array();

		if ( is_array( $decoded ) && isset( $decoded['Error'] ) && is_array( $decoded['Error'] ) ) {
			foreach ( $decoded['Error'] as $error ) {
				if ( is_array( $error ) && isset( $error['error_description'] ) && is_string( $error['error_description'] ) ) {
					$descriptions[] = $error['error_description'];
				}
			}
		}

		return $descriptions ? implode( '; ', $descriptions ) : substr( $body, 0, 500 );
	}
}
