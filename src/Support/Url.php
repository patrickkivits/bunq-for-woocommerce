<?php
/**
 * URL helpers.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Support;

defined( 'ABSPATH' ) || exit;

/**
 * URL helpers.
 */
final class Url {

	/**
	 * Whether a URL points at a host that only resolves locally (development sites), so bunq cannot call it.
	 *
	 * @param string $url URL to check.
	 * @return bool
	 */
	public static function is_local( $url ) {
		$host = strtolower( trim( (string) wp_parse_url( $url, PHP_URL_HOST ), '[]' ) );

		if ( '' === $host || in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) ) {
			return true;
		}

		foreach ( array( '.localhost', '.local', '.test' ) as $suffix ) {
			if ( substr( $host, -strlen( $suffix ) ) === $suffix ) {
				return true;
			}
		}

		return false;
	}
}
