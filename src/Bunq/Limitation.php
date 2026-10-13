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
	 * Requests allowed within the sample.
	 *
	 * @var int
	 */
	public $number_of_allowed_requests;

	/**
	 * The sample size in number of seconds.
	 *
	 * @var int
	 */
	public $sample_size;

	/**
	 * Constructor.
	 *
	 * @param int $sample_size                The sample size in number of seconds.
	 * @param int $number_of_allowed_requests Requests allowed within the sample.
	 */
	public function __construct( $sample_size, $number_of_allowed_requests ) {
		$this->sample_size                = (int) $sample_size;
		$this->number_of_allowed_requests = (int) $number_of_allowed_requests;
	}
}
