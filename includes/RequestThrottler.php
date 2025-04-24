<?php

// Based on: https://gist.github.com/holtkamp/bae00c495f80eace6aa49d12b46f5bca

class RequestThrottler {

	/**
	 * @var RequestThrottlerConfiguration
	 */
	private $requestThrottlerConfiguration;

	/**
	 * Keep track of the timestamps when requests are sent to the API client using a specific request method.
	 *
	 * This allows us to respect the maximum amount of requests that are allowed per second.
	 *
	 * @var []
	 */
	private $submittedRequestTimestamps = array();

	public function __construct( RequestThrottlerConfiguration $requestThrottlerConfiguration ) {
		$this->requestThrottlerConfiguration = $requestThrottlerConfiguration;
	}

	public function ensureApiLimitsAreRespected( string $endpoint, string $requestMethod ) {
		$endpointKey                                      = $this->getEndpointKey( $endpoint );
		$this->submittedRequestTimestamps[ $endpointKey ] = $this->submittedRequestTimestamps[ $endpointKey ] ?? array();
		$this->submittedRequestTimestamps[ $endpointKey ][ $requestMethod ] = $this->submittedRequestTimestamps[ $endpointKey ][ $requestMethod ] ?? array();

		if ( $limitation = $this->requestThrottlerConfiguration->getAllowedNumberOfSubmittedRequests( $endpointKey, $requestMethod ) ) {
			$numberOfSubmittedRequests = $this->getNumberOfSubmittedRequests( $endpointKey, $requestMethod, $limitation->sampleSize );

			if ( $numberOfSubmittedRequests >= $limitation->numberOfAllowedRequests ) {
				$numberOfSecondsToSleep = $this->getNumberOfSecondsToSleep( $endpointKey, $requestMethod, $limitation->sampleSize );
				\sleep( $numberOfSecondsToSleep );
				$this->ensureApiLimitsAreRespected( $endpointKey, $requestMethod ); // Recursively invoke this function to ensure API limits are (eventually) respected
				return;
			}

			$this->registerRequest( $requestMethod, $endpointKey );
		}
	}

	/**
	 * Take the starting part of the complete endpoint until the first forward slash.
	 *
	 * @param string $endpoint
	 *
	 * @return string
	 */
	private function getEndpointKey( string $endpoint ): string {
		return \current( \explode( '/', $endpoint ) );
	}

	private function getNumberOfSubmittedRequests( string $endpointKey, string $requestMethod, int $numberOfConsideredSeconds ): int {
		$now                = \microtime( true );
		$thresholdTimestamp = $now - $numberOfConsideredSeconds;

		// Reject all requests that were issued before the threshold
		$leftOverRequests = array();
		foreach ( $this->submittedRequestTimestamps[ $endpointKey ][ $requestMethod ] as $requestTimestamp ) {
			if ( $requestTimestamp >= $thresholdTimestamp ) {
				$leftOverRequests[] = $requestTimestamp;
			}
		}

		$this->submittedRequestTimestamps[ $endpointKey ][ $requestMethod ] = $leftOverRequests;

		return count( $this->submittedRequestTimestamps[ $endpointKey ][ $requestMethod ] );
	}

	private function getNumberOfSecondsToSleep( string $endpointKey, string $requestMethod, int $sampleSizeInSeconds ): int {
		$earliestRequestTimestamp = min( $this->submittedRequestTimestamps[ $endpointKey ][ $requestMethod ] );
		$availableAgain           = $earliestRequestTimestamp + $sampleSizeInSeconds;
		$now                      = \microtime( true );

		return $availableAgain > $now ? (int) \ceil( $availableAgain - $now ) : 1;
	}

	private function registerRequest( string $requestMethod, string $endpointKey ) {
		$this->submittedRequestTimestamps[ $endpointKey ][ $requestMethod ][] = \microtime( true );
	}
}


class RequestThrottlerConfiguration {

	/**
	 * @var string
	 */
	const END_POINT_DEVICE_SERVER = 'device-server';

	/**
	 * @var string
	 */
	const END_POINT_USER = 'user';

	/**
	 * @var string
	 */
	const REQUEST_METHOD_GET = 'GET';

	/**
	 * @var string
	 */
	const REQUEST_METHOD_POST = 'POST';

	/**
	 * @var string
	 */
	const REQUEST_METHOD_PUT = 'PUT';

	/**
	 * The Bunq API allows a limited amount request per second against their endpoints:
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
	 *
	 * @var array
	 */
	private $limitationConfiguration;

	public function __construct( int $additionalBuffer = 1 ) {
		$this->limitationConfiguration = array(
			self::END_POINT_DEVICE_SERVER => array(
				self::REQUEST_METHOD_GET => new Limitation( 9, 9 - $additionalBuffer ),
			),
			self::END_POINT_USER          => array(
				self::REQUEST_METHOD_POST => new Limitation( 5, 5 - $additionalBuffer ),
			),
			self::REQUEST_METHOD_GET      => new Limitation( 3, 3 - $additionalBuffer ),
			self::REQUEST_METHOD_POST     => new Limitation( 3, 3 - $additionalBuffer ),
			self::REQUEST_METHOD_PUT      => new Limitation( 3, 2 - $additionalBuffer ),
		);
	}

	public function getAllowedNumberOfSubmittedRequests( string $endpointKey, string $requestMethod ) {
		return $this->limitationConfiguration[ $endpointKey ][ $requestMethod ]
				?? $this->limitationConfiguration[ $requestMethod ]
					?? null;
	}
}

class Limitation {

	/**
	 * @var int
	 */
	public $numberOfAllowedRequests;

	/**
	 * The sample size in number of seconds.
	 *
	 * @var int
	 */
	public $sampleSize;

	public function __construct( int $sampleSize, int $numberOfAllowedRequests ) {
		$this->sampleSize              = $sampleSize;
		$this->numberOfAllowedRequests = $numberOfAllowedRequests;
	}
}
