<?php

namespace BunqTest;

use PatrickKivits\BunqForWooCommerce\Bunq\RequestThrottler;
use PatrickKivits\BunqForWooCommerce\Bunq\RequestThrottlerConfiguration;

/**
 * The throttler paces calls made within one PHP process so bunq's per-endpoint rate limits are not hit.
 */
final class RequestThrottlerTest extends TestCase
{
    public function testEndpointSpecificLimitTakesPrecedenceOverTheMethodDefault()
    {
        $configuration = new RequestThrottlerConfiguration();

        $device_server_get = $configuration->get_limitation('device-server', 'GET');
        $this->assertSame(9, $device_server_get->sample_size);
        $this->assertSame(8, $device_server_get->number_of_allowed_requests);

        $user_post = $configuration->get_limitation('user', 'POST');
        $this->assertSame(5, $user_post->sample_size);
        $this->assertSame(4, $user_post->number_of_allowed_requests);

        // No endpoint-specific entry: the method default applies.
        $user_get = $configuration->get_limitation('user', 'GET');
        $this->assertSame(3, $user_get->sample_size);
        $this->assertSame(2, $user_get->number_of_allowed_requests);

        $user_put = $configuration->get_limitation('user', 'PUT');
        $this->assertSame(3, $user_put->sample_size);
        $this->assertSame(1, $user_put->number_of_allowed_requests);
    }

    public function testTheBufferIsSubtractedFromTheAllowedNumberOfRequests()
    {
        $configuration = new RequestThrottlerConfiguration(0);

        $this->assertSame(9, $configuration->get_limitation('device-server', 'GET')->number_of_allowed_requests);
        $this->assertSame(5, $configuration->get_limitation('user', 'POST')->number_of_allowed_requests);
        $this->assertSame(3, $configuration->get_limitation('user', 'GET')->number_of_allowed_requests);
        $this->assertSame(2, $configuration->get_limitation('user', 'PUT')->number_of_allowed_requests);
    }

    public function testMethodsWithoutAConfiguredLimitAreUnlimited()
    {
        $configuration = new RequestThrottlerConfiguration();

        $this->assertNull($configuration->get_limitation('user', 'DELETE'));
        $this->assertNull($configuration->get_limitation('device-server', 'DELETE'));
    }

    public function testRequestsWithinTheLimitAreNotDelayed()
    {
        $throttler = new RequestThrottler(new RequestThrottlerConfiguration());

        $start = microtime(true);
        for ($i = 0; $i < 4; $i++) {
            $throttler->ensure_api_limits_are_respected('user/%s/monetary-account/%s/bunqme-tab', 'POST');
        }

        $this->assertLessThan(0.5, microtime(true) - $start);
        $this->assertCount(4, $this->submittedTimestamps($throttler, 'user', 'POST'));
    }

    public function testEndpointsAreGroupedByTheirFirstPathSegment()
    {
        $throttler = new RequestThrottler(new RequestThrottlerConfiguration());

        $throttler->ensure_api_limits_are_respected('user/%s/monetary-account/%s/bunqme-tab', 'POST');
        $throttler->ensure_api_limits_are_respected('user/%s/monetary-account/%s/draft-payment', 'POST');
        $throttler->ensure_api_limits_are_respected('user', 'POST');
        $throttler->ensure_api_limits_are_respected('device-server', 'GET');

        $this->assertCount(3, $this->submittedTimestamps($throttler, 'user', 'POST'));
        $this->assertCount(1, $this->submittedTimestamps($throttler, 'device-server', 'GET'));
        $this->assertCount(0, $this->submittedTimestamps($throttler, 'user', 'GET'));
    }

    public function testUnlimitedMethodsAreNotRegistered()
    {
        $throttler = new RequestThrottler(new RequestThrottlerConfiguration());

        $throttler->ensure_api_limits_are_respected('user/%s/monetary-account/%s/bunqme-tab', 'DELETE');

        $this->assertCount(0, $this->submittedTimestamps($throttler, 'user', 'DELETE'));
    }

    public function testRequestsOlderThanTheSampleWindowAreForgotten()
    {
        $throttler = new RequestThrottler(new RequestThrottlerConfiguration());
        // Four POSTs (the limit) that all fell out of the 5 second window long ago.
        $this->injectTimestamps($throttler, 'user', 'POST', array_fill(0, 4, microtime(true) - 10));

        $start = microtime(true);
        $throttler->ensure_api_limits_are_respected('user/%s/monetary-account/%s/bunqme-tab', 'POST');

        $this->assertLessThan(0.5, microtime(true) - $start);
        $this->assertCount(1, $this->submittedTimestamps($throttler, 'user', 'POST'), 'Only the new request is left');
    }

    /**
     * @group slow
     */
    public function testWaitsUntilTheOldestRequestLeavesTheSampleWindow()
    {
        $throttler = new RequestThrottler(new RequestThrottlerConfiguration());
        // The limit is reached and the oldest request leaves the 5 second window in half a second, which the
        // throttler rounds up to a full second of waiting.
        $this->injectTimestamps($throttler, 'user', 'POST', array_fill(0, 4, microtime(true) - 4.5));

        $start = microtime(true);
        $throttler->ensure_api_limits_are_respected('user/%s/monetary-account/%s/bunqme-tab', 'POST');
        $elapsed = microtime(true) - $start;

        $this->assertGreaterThanOrEqual(1.0, $elapsed);
        $this->assertLessThan(2.5, $elapsed);
        $this->assertCount(1, $this->submittedTimestamps($throttler, 'user', 'POST'), 'The expired requests are dropped and the new one registered');
    }

    private function submittedTimestamps(RequestThrottler $throttler, $endpoint_key, $method)
    {
        $timestamps = $this->timestampsProperty()->getValue($throttler);

        return isset($timestamps[$endpoint_key][$method]) ? $timestamps[$endpoint_key][$method] : array();
    }

    private function injectTimestamps(RequestThrottler $throttler, $endpoint_key, $method, array $timestamps)
    {
        $property = $this->timestampsProperty();
        $all = $property->getValue($throttler);
        $all[$endpoint_key][$method] = $timestamps;
        $property->setValue($throttler, $all);
    }

    private function timestampsProperty()
    {
        $property = new \ReflectionProperty(RequestThrottler::class, 'submitted_request_timestamps');
        $property->setAccessible(true);

        return $property;
    }
}
