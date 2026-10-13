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
	 * The URL of the current request.
	 *
	 * @return string
	 */
	public static function current() {
		$scheme = ( function_exists( 'is_ssl' ) && is_ssl() ) || ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) ? 'https' : 'http';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Used as a URL that is escaped wherever it is output.
		$host = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Used as a URL that is escaped wherever it is output.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

		return $scheme . '://' . $host . $request_uri;
	}

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
