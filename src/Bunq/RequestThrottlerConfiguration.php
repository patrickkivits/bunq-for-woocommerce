<?php
/**
 * The bunq rate limits.
 *
 * @package PatrickKivits\BunqForWooCommerce
 */

namespace PatrickKivits\BunqForWooCommerce\Bunq;

defined( 'ABSPATH' ) || exit;

/**
 * The rate limits bunq applies per endpoint and request method.
 *
 * The bunq API allows a limited amount of requests per second against its endpoints:
 *  - GET requests: 3 requests per 3 seconds
 *  - POST requests: 5 requests per 3 seconds
 *  - PUT requests: 2 requests per 3 seconds.
 *
 * However, from the returned error message these rates seem to differ per endpoint:
 *  - device-server/ (Too many requests. You can do a maximum of 9 GET call per 9 second to this endpoint.):
 *      - GET requests: 9 requests per 9 seconds
 *  - user/ (Too many requests. You can do a maximum of 5 calls per 5 second to this endpoint.):
 *      - POST requests: 5 requests per 5 seconds
 *
 * @see https://doc.bunq.com/api/1/page/errors
 */
final class RequestThrottlerConfiguration {

	const END_POINT_DEVICE_SERVER = 'device-server';
	const END_POINT_USER          = 'user';
	const REQUEST_METHOD_GET      = 'GET';
	const REQUEST_METHOD_POST     = 'POST';
	const REQUEST_METHOD_PUT      = 'PUT';

	/**
	 * Limitations per endpoint and request method, and per request method for all other endpoints.
	 *
	 * @var array
	 */
	private $limitations;

	/**
	 * Constructor.
	 *
	 * @param int $additional_buffer Requests kept below each limit as a safety margin.
	 */
	public function __construct( $additional_buffer = 1 ) {
		$this->limitations = array(
			self::END_POINT_DEVICE_SERVER => array(
				self::REQUEST_METHOD_GET => new Limitation( 9, 9 - $additional_buffer ),
			),
			self::END_POINT_USER          => array(
				self::REQUEST_METHOD_POST => new Limitation( 5, 5 - $additional_buffer ),
			),
			self::REQUEST_METHOD_GET      => new Limitation( 3, 3 - $additional_buffer ),
			self::REQUEST_METHOD_POST     => new Limitation( 3, 3 - $additional_buffer ),
			self::REQUEST_METHOD_PUT      => new Limitation( 3, 2 - $additional_buffer ),
		);
	}

	/**
	 * The limitation that applies to a request, if any.
	 *
	 * @param string $endpoint_key   First path segment of the endpoint.
	 * @param string $request_method HTTP method.
	 * @return Limitation|null
	 */
	public function get_limitation( $endpoint_key, $request_method ) {
		if ( isset( $this->limitations[ $endpoint_key ][ $request_method ] ) ) {
			return $this->limitations[ $endpoint_key ][ $request_method ];
		}

		return isset( $this->limitations[ $request_method ] ) ? $this->limitations[ $request_method ] : null;
	}
}
