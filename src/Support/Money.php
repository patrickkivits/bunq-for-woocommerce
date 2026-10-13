<?php
/**
 * Money helpers.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Money helpers.
 */
final class Money {

	/**
	 * Compare two money amounts (strings or floats) as decimals with a tolerance of half a cent, so the number of
	 * decimals in the shop does not matter ("10" equals bunq's "10.00").
	 *
	 * @param string|float $a First amount.
	 * @param string|float $b Second amount.
	 * @return bool
	 */
	public static function amounts_match( $a, $b ) {
		return abs( (float) $a - (float) $b ) < 0.005;
	}
}
