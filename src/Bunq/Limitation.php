<?php
/**
 * A bunq rate limit.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Bunq;

defined( 'ABSPATH' ) || exit;

/**
 * The number of requests bunq allows within a number of seconds.
 */
final class Limitation {

	/**
	 * Constructor.
	 *
	 * @param int $sample_size                The sample size in number of seconds.
	 * @param int $number_of_allowed_requests Requests allowed within the sample.
	 */
	public function __construct(
		public readonly int $sample_size,
		public readonly int $number_of_allowed_requests
	) {}
}
