<?php
/**
 * Logging to the WooCommerce log.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce;

use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Writes to the WooCommerce log (WooCommerce > Status > Logs, source "bunq").
 */
final class Logger {

	/**
	 * The WooCommerce log source.
	 */
	const SOURCE = 'bunq';

	/**
	 * Values that must never be logged or shown, with the label shown instead.
	 *
	 * @var string[]
	 */
	private static $secrets = array();

	/**
	 * Keep a value out of everything this class logs or formats. Applies for the rest of the request.
	 *
	 * @param string $secret The value (an OAuth client secret, an API key, ...).
	 * @param string $label  What to show instead, e.g. "client secret".
	 */
	public static function add_secret( $secret, $label ) {
		$secret = is_scalar( $secret ) ? (string) $secret : '';

		// Very short values would mask ordinary words.
		if ( strlen( $secret ) >= 6 ) {
			self::$secrets[ $secret ] = $label;
		}
	}

	/**
	 * Replace the registered secrets in a text, also in their URL encoded forms.
	 *
	 * @param string $text Text to clean.
	 * @return string
	 */
	public static function redact( $text ) {
		$secrets = self::$secrets;
		// Longest first, so a secret that contains another one is replaced as a whole.
		uksort(
			$secrets,
			function ( $a, $b ) {
				return strlen( $b ) - strlen( $a );
			}
		);

		foreach ( $secrets as $secret => $label ) {
			$text = str_replace( array( $secret, rawurlencode( $secret ), urlencode( $secret ) ), '[' . $label . ']', (string) $text ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode -- Form encoding is how http_build_query() sends them.
		}

		return (string) $text;
	}

	/**
	 * Log a message to the WooCommerce log and to the PHP error log when WP_DEBUG is enabled.
	 *
	 * @param string|Throwable $message Message or exception to log.
	 * @param string           $level   One of the WC_Log_Levels constants (error, warning, info, debug).
	 */
	public static function log( $message, $level = 'error' ) {
		if ( $message instanceof Throwable ) {
			$message = get_class( $message ) . ': ' . $message->getMessage() . ' in ' . $message->getFile() . ':' . $message->getLine();
		}

		$message = self::redact( $message );

		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->log( $level, $message, array( 'source' => self::SOURCE ) );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only under WP_DEBUG.
			error_log( '[bunq for WooCommerce] ' . $message );
		}
	}

	/**
	 * Format a throwable into a short, human readable message for the admin UI.
	 *
	 * @param Throwable $throwable The error.
	 * @return string
	 */
	public static function format_error( Throwable $throwable ) {
		$message = trim( self::redact( $throwable->getMessage() ) );

		if ( '' === $message ) {
			$message = get_class( $throwable );
		}

		return $message;
	}
}
