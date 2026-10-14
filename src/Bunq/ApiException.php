<?php
/**
 * An error response of the bunq API.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Bunq;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * An error answer of bunq. The code is the HTTP status; the message holds bunq's error descriptions.
 */
class ApiException extends RuntimeException {

	/**
	 * The id bunq asks for when its support investigates a request.
	 *
	 * @var string
	 */
	private $response_id;

	/**
	 * Whether bunq refused a new session for the API context, see is_context_rejected().
	 *
	 * @var bool
	 */
	private $context_rejected;

	/**
	 * Constructor.
	 *
	 * @param string $message          bunq's error descriptions.
	 * @param int    $status           HTTP status.
	 * @param string $response_id      The X-Bunq-Client-Response-Id header.
	 * @param bool   $context_rejected Whether bunq refused a new session for the API context.
	 */
	public function __construct( $message, $status, $response_id = '', $context_rejected = false ) {
		parent::__construct( $message, $status );
		$this->response_id      = (string) $response_id;
		$this->context_rejected = (bool) $context_rejected;
	}

	/**
	 * The exception for an error response.
	 *
	 * @param int    $status      HTTP status.
	 * @param string $body        Response body, {"Error": [{"error_description": "..."}]}.
	 * @param string $response_id The X-Bunq-Client-Response-Id header.
	 * @return self
	 */
	public static function from_response( $status, $body, $response_id ) {
		$decoded      = json_decode( $body, true );
		$descriptions = array();

		foreach ( isset( $decoded['Error'] ) && is_array( $decoded['Error'] ) ? $decoded['Error'] : array() as $error ) {
			if ( isset( $error['error_description'] ) ) {
				$descriptions[] = $error['error_description'];
			}
		}

		$message = $descriptions ? implode( ' ', $descriptions ) : 'bunq returned HTTP ' . $status;

		if ( '' !== (string) $response_id ) {
			$message .= ' (HTTP ' . $status . ', response id ' . $response_id . ')';
		}

		return new self( $message, $status, $response_id );
	}

	/**
	 * The X-Bunq-Client-Response-Id header of the response.
	 *
	 * @return string
	 */
	public function get_response_id() {
		return $this->response_id;
	}

	/**
	 * Whether bunq did not accept the session token ("Insufficient authentication."): the session expired or was
	 * ended. Other 403 answers, such as missing permissions, are not about the session.
	 *
	 * @return bool
	 */
	public function is_authentication_error() {
		return 401 === $this->getCode() || ( 403 === $this->getCode() && false !== stripos( $this->getMessage(), 'authentication' ) );
	}

	/**
	 * Whether bunq refused a new session for the API context: the access was revoked, or the shop now calls from
	 * another IP address than the one the API key was registered from. Only a new API context can fix this.
	 *
	 * @return bool
	 */
	public function is_context_rejected() {
		return $this->context_rejected;
	}
}
