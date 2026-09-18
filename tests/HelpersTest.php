<?php

namespace BunqTest;

final class HelpersTest extends TestCase
{
    /**
     * @dataProvider localUrls
     */
    public function testLocalUrlsAreRecognised($url)
    {
        $this->assertTrue(bunq_helper_is_local_url($url), $url);
    }

    public function localUrls()
    {
        return array(
            array('http://localhost/wc-api/wc_bunq_gateway/'),
            array('http://LOCALHOST:8080/'),
            array('https://127.0.0.1/'),
            array('http://[::1]/'),
            array('https://shop.localhost/'),
            array('https://shop.local/'),
            array('https://shop.test/wc-api/wc_bunq_gateway/'),
            array('https://sub.shop.test:8443/'),
            array(''),
            array('/wc-api/wc_bunq_gateway/'),
        );
    }

    /**
     * @dataProvider publicUrls
     */
    public function testPublicUrlsAreNotLocal($url)
    {
        $this->assertFalse(bunq_helper_is_local_url($url), $url);
    }

    public function publicUrls()
    {
        return array(
            array('https://example.com/wc-api/wc_bunq_gateway/'),
            array('https://shop.example.com/'),
            array('https://localhost.example.com/'),
            array('https://mylocal.dev/'),
            array('https://test.example/'),
            array('https://10.0.0.1/'),
        );
    }

    /**
     * @dataProvider amounts
     */
    public function testAmountsAreComparedAsDecimals($a, $b, $expected)
    {
        $this->assertSame($expected, bunq_helper_amounts_match($a, $b));
        $this->assertSame($expected, bunq_helper_amounts_match($b, $a));
    }

    public function amounts()
    {
        return array(
            'same string' => array('10.00', '10.00', true),
            'string and integer' => array('10.00', 10, true),
            'string and float' => array('10.50', 10.5, true),
            'more decimals in the shop' => array('10.004', '10.00', true),
            'float arithmetic noise' => array('0.3', 0.1 + 0.2, true),
            'one cent off' => array('10.01', '10.00', false),
            'different amount' => array('9.99', '19.99', false),
            'zero and empty' => array('', '0.00', true),
        );
    }

    public function testFormattedErrorIsTheTrimmedMessage()
    {
        $this->assertSame('bunq said no', bunq_helper_format_error(new \RuntimeException("  bunq said no \n")));
    }

    public function testFormattedErrorFallsBackToTheExceptionClass()
    {
        $this->assertSame('RuntimeException', bunq_helper_format_error(new \RuntimeException('')));
        $this->assertSame('RuntimeException', bunq_helper_format_error(new \RuntimeException('   ')));
    }

    public function testMessagesAreLoggedToTheWooCommerceLogUnderTheBunqSource()
    {
        bunq_helper_log('something happened');
        bunq_helper_log('for the record', 'info');

        $this->assertSame(array(
            array('level' => 'error', 'message' => 'something happened', 'source' => 'bunq'),
            array('level' => 'info', 'message' => 'for the record', 'source' => 'bunq'),
        ), Environment::$logs);
    }

    public function testThrowablesAreLoggedWithTheirClassAndLocation()
    {
        $exception = new \RuntimeException('bunq is down');
        bunq_helper_log($exception, 'warning');

        $this->assertCount(1, Environment::$logs);
        $this->assertSame('warning', Environment::$logs[0]['level']);
        $this->assertSame(
            'RuntimeException: bunq is down in ' . __FILE__ . ':' . $exception->getLine(),
            Environment::$logs[0]['message']
        );
    }

    public function testCurrentUrlUsesTheRequestHostAndUri()
    {
        $_SERVER['HTTP_HOST'] = 'shop.example';
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=wc-settings&section=bunq';

        $this->assertSame('http://shop.example/wp-admin/admin.php?page=wc-settings&section=bunq', bunq_helper_get_current_url());
    }

    public function testCurrentUrlIsHttpsBehindSsl()
    {
        $_SERVER['HTTP_HOST'] = 'shop.example';
        $_SERVER['REQUEST_URI'] = '/';

        Environment::$isSsl = true;
        $this->assertSame('https://shop.example/', bunq_helper_get_current_url());

        Environment::$isSsl = false;
        $_SERVER['HTTPS'] = 'on';
        $this->assertSame('https://shop.example/', bunq_helper_get_current_url());
    }

    public function testRemoveUrlParameter()
    {
        $this->assertSame(
            'https://shop.example/wp-admin/admin.php?page=wc-settings&section=bunq',
            bunq_helper_remove_url_parameter('code', 'https://shop.example/wp-admin/admin.php?page=wc-settings&code=abc&section=bunq')
        );
    }
}
