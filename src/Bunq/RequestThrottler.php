<?php
/**
 * Client-side pacing of bunq API calls.
 *
 * Based on: https://gist.github.com/holtkamp/bae00c495f80eace6aa49d12b46f5bca
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Bunq;

defined( 'ABSPATH' ) || exit;

/**
 * Paces the bunq API calls made within one PHP process so bunq's per-endpoint rate limits are not hit.
 */
final class RequestThrottler {

	/**
	 * The rate limits.
	 *
	 * @var RequestThrottlerConfiguration
	 */
	private $configuration;

	/**
	 * The timestamps of the requests sent per endpoint key and request method.
	 *
	 * @var array
	 */
	private $submitted_request_timestamps = array();

	/**
	 * Constructor.
	 *
	 * @param RequestThrottlerConfiguration $configuration The rate limits.
	 */
	public function __construct( RequestThrottlerConfiguration $configuration ) {
		$this->configuration = $configuration;
	}

	/**
	 * Wait until a request to the endpoint stays within bunq's rate limit, and register it.
	 *
	 * @param string $endpoint       Endpoint path (e.g. "user/1/monetary-account-bank").
	 * @param string $request_method HTTP method.
	 */
	public function ensure_api_limits_are_respected( $endpoint, $request_method ) {
		$endpoint_key = $this->get_endpoint_key( $endpoint );

		if ( ! isset( $this->submitted_request_timestamps[ $endpoint_key ][ $request_method ] ) ) {
			$this->submitted_request_timestamps[ $endpoint_key ][ $request_method ] = array();
		}

		$limitation = $this->configuration->get_limitation( $endpoint_key, $request_method );

		if ( ! $limitation ) {
			return;
		}

		while ( $this->get_number_of_submitted_requests( $endpoint_key, $request_method, $limitation->sample_size ) >= $limitation->number_of_allowed_requests ) {
			sleep( $this->get_number_of_seconds_to_sleep( $endpoint_key, $request_method, $limitation->sample_size ) );
		}

		$this->submitted_request_timestamps[ $endpoint_key ][ $request_method ][] = microtime( true );
	}

	/**
	 * The starting part of the endpoint until the first forward slash.
	 *
	 * @param string $endpoint Endpoint URL.
	 * @return string
	 */
	private function get_endpoint_key( $endpoint ) {
		return current( explode( '/', $endpoint ) );
	}

	/**
	 * Forget the requests that left the sample window and count the rest.
	 *
	 * @param string $endpoint_key   Endpoint key.
	 * @param string $request_method HTTP method.
	 * @param int    $sample_size    Seconds to consider.
	 * @return int
	 */
	private function get_number_of_submitted_requests( $endpoint_key, $request_method, $sample_size ) {
		$threshold = microtime( true ) - $sample_size;

		$this->submitted_request_timestamps[ $endpoint_key ][ $request_method ] = array_values(
			array_filter(
				$this->submitted_request_timestamps[ $endpoint_key ][ $request_method ],
				function ( $timestamp ) use ( $threshold ) {
					return $timestamp >= $threshold;
				}
			)
		);

		return count( $this->submitted_request_timestamps[ $endpoint_key ][ $request_method ] );
	}

	/**
	 * Seconds until the oldest request leaves the sample window, at least one.
	 *
	 * @param string $endpoint_key   Endpoint key.
	 * @param string $request_method HTTP method.
	 * @param int    $sample_size    Seconds to consider.
	 * @return int
	 */
	private function get_number_of_seconds_to_sleep( $endpoint_key, $request_method, $sample_size ) {
		$available_again = min( $this->submitted_request_timestamps[ $endpoint_key ][ $request_method ] ) + $sample_size;
		$now             = microtime( true );

		return $available_again > $now ? (int) ceil( $available_again - $now ) : 1;
	}
}
