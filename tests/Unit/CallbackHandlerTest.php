<?php

namespace BunqTest;

use PatrickKivits\BunqForWooCommerce\Gateway\CallbackHandler;
use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;

/**
 * The callback bunq sends when something happens to a bunq.me payment request.
 */
final class CallbackHandlerTest extends TestCase
{
    /** @var FakeBunqApi */
    private $bunq;

    /** @var CallbackHandler */
    private $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bunq = new FakeBunqApi();
        update_option('woocommerce_bunq_settings', $this->liveSettings(array('api_context' => '{"live":1}')));
        $this->handler = new CallbackHandler(new Gateway($this->bunq));
    }

    public function testTheCallbackUrlIsTheOneRegisteredAtBunq()
    {
        $this->assertSame('https://shop.example/wc-api/wc_bunq_gateway/', CallbackHandler::url());
    }

    public function testAPaymentConfirmsTheOrderAfterAskingBunq()
    {
        $order = Environment::addOrder(array('total' => '10.00', 'meta' => array('bunq_payment_request_id' => 55)));
        Environment::addOrder(array('meta' => array('bunq_payment_request_id' => 56)));
        $this->bunq->paymentRequests[55] = FakeBunqApi::paymentRequest(55, 'ACTIVE', array(FakeBunqApi::payment(777, '10.00')));

        $this->assertSame('paid', $this->handler->handle($this->notification('BUNQME_TAB', 55)));

        $this->assertSame(array(array(55, 123)), $this->bunq->callsTo('get_payment_request'));
        $this->assertSame('processing', $order->get_status());
        $this->assertLogged('debug', 'bunq callback for bunqme-tab 55: order #' . $order->get_order_number() . ' is paid');
    }

    public function testTheBodyIsNotTrustedForThePaymentItself()
    {
        $order = Environment::addOrder(array('total' => '10.00', 'meta' => array('bunq_payment_request_id' => 55)));
        $this->bunq->paymentRequests[55] = FakeBunqApi::paymentRequest(55, 'ACTIVE');
        $body = json_encode(array('NotificationUrl' => array(
            'category' => 'BUNQME_TAB',
            'object' => array('BunqMeTab' => array('id' => 55, 'status' => 'PAID', 'result_inquiries' => array(array('payment' => array('id' => 1))))),
        )));

        $this->assertSame('pending', $this->handler->handle($body));
        $this->assertSame('pending', $order->get_status());
    }

    /**
     * @dataProvider ignoredBodies
     */
    public function testOtherNotificationsAreIgnored($body)
    {
        Environment::addOrder(array('meta' => array('bunq_payment_request_id' => 55)));

        $this->assertSame('ignored', $this->handler->handle($body));

        $this->assertSame(array(), $this->bunq->calls);
        $this->assertLogged('debug', 'bunq callback ignored');
    }

    public function ignoredBodies()
    {
        return array(
            'empty' => array(''),
            'not JSON' => array('<xml/>'),
            'JSON without notification' => array('[]'),
            'another category' => array($this->notification('PAYMENT', 55)),
            'no id' => array(json_encode(array('NotificationUrl' => array('category' => 'BUNQME_TAB', 'object' => array('BunqMeTab' => array()))))),
            'id that is not a number' => array($this->notification('BUNQME_TAB', 'abc')),
        );
    }

    public function testANumericStringIdIsAccepted()
    {
        $order = Environment::addOrder(array('total' => '10.00', 'meta' => array('bunq_payment_request_id' => 55)));
        $this->bunq->paymentRequests[55] = FakeBunqApi::paymentRequest(55, 'ACTIVE');

        $this->assertSame('pending', $this->handler->handle($this->notification('BUNQME_TAB', '55')));
        $this->assertSame(array(array(55, 123)), $this->bunq->callsTo('get_payment_request'));
    }

    public function testAPaymentRequestWithoutOrderIsLeftAlone()
    {
        $this->assertSame('unknown', $this->handler->handle($this->notification('BUNQME_TAB', 55)));

        $this->assertSame(array(), $this->bunq->calls);
        $this->assertLogged('warning', 'bunq callback for bunqme-tab 55: found 0 orders, expected exactly one');
    }

    public function testAPaymentRequestOfTwoOrdersIsLeftAlone()
    {
        $first = Environment::addOrder(array('meta' => array('bunq_payment_request_id' => 55)));
        $second = Environment::addOrder(array('meta' => array('bunq_payment_request_id' => 55)));

        $this->assertSame('unknown', $this->handler->handle($this->notification('BUNQME_TAB', 55)));

        $this->assertSame(array(), $this->bunq->calls);
        $this->assertSame('pending', $first->get_status());
        $this->assertSame('pending', $second->get_status());
        $this->assertLogged('warning', 'found 2 orders, expected exactly one');
    }

    public function testAnOrderThatNoLongerNeedsPaymentIsLeftAlone()
    {
        $order = Environment::addOrder(array('status' => 'processing', 'meta' => array('bunq_payment_request_id' => 55)));

        $this->assertSame('settled', $this->handler->handle($this->notification('BUNQME_TAB', 55)));

        $this->assertSame(array(), $this->bunq->calls);
        $this->assertLogged('debug', 'order #' . $order->get_order_number() . ' no longer needs payment (status processing)');
    }

    public function testABunqErrorIsLogged()
    {
        Environment::addOrder(array('meta' => array('bunq_payment_request_id' => 55)));
        $this->bunq->exceptions['get_payment_request'] = new \RuntimeException('bunq is down');

        $this->assertSame('error', $this->handler->handle($this->notification('BUNQME_TAB', 55)));

        $this->assertLogged('error', 'RuntimeException: bunq is down');
    }

    public function testCallbacksInQuickSuccessionAskBunqOnceAndQueueOneFollowUp()
    {
        // Anyone can call the callback URL; each call used to make the shop ask bunq.
        $order = Environment::addOrder(array('total' => '10.00', 'meta' => array('bunq_payment_request_id' => 55)));
        $this->bunq->paymentRequests[55] = FakeBunqApi::paymentRequest(55, 'ACTIVE');

        $this->assertSame('pending', $this->handler->handle($this->notification('BUNQME_TAB', 55)));
        $this->assertSame('deferred', $this->handler->handle($this->notification('BUNQME_TAB', 55)));
        $this->assertSame('deferred', $this->handler->handle($this->notification('BUNQME_TAB', 55)));

        $this->assertCount(1, $this->bunq->callsTo('get_payment_request'));
        $this->assertSame(10, Environment::$transientTtls['bunq_for_woocommerce_callback_55']);
        $this->assertCount(1, Environment::$scheduledActions, 'One follow-up, however many callbacks');
        $this->assertSame('bunq_for_woocommerce_follow_up_check', Environment::$scheduledActions[0]['hook']);
        $this->assertSame(array($order->get_id()), Environment::$scheduledActions[0]['args']);
        $this->assertEqualsWithDelta(time() + 10, Environment::$scheduledActions[0]['timestamp'], 2);
    }

    public function testTheFollowUpConfirmsAPaymentThatArrivedMeanwhile()
    {
        $gateway = new Gateway($this->bunq);
        $this->registerGateway($gateway);
        $order = Environment::addOrder(array('total' => '10.00', 'meta' => array('bunq_payment_request_id' => 55)));
        $this->bunq->paymentRequests[55] = FakeBunqApi::paymentRequest(55, 'ACTIVE');
        $handler = new CallbackHandler($gateway);
        $handler->handle($this->notification('BUNQME_TAB', 55));
        $handler->handle($this->notification('BUNQME_TAB', 55));

        $this->bunq->paymentRequests[55] = FakeBunqApi::paymentRequest(55, 'ACTIVE', array(FakeBunqApi::payment(777, '10.00')));
        do_action(Environment::$scheduledActions[0]['hook'], ...Environment::$scheduledActions[0]['args']);

        $this->assertSame('processing', $order->get_status());
        $this->assertLogged('debug', 'Follow-up check for order #' . $order->get_order_number() . ': paid');
    }

    public function testOtherPaymentRequestsAreNotHeldUp()
    {
        Environment::addOrder(array('meta' => array('bunq_payment_request_id' => 55)));
        Environment::addOrder(array('meta' => array('bunq_payment_request_id' => 56)));
        $this->bunq->paymentRequests[55] = FakeBunqApi::paymentRequest(55, 'ACTIVE');
        $this->bunq->paymentRequests[56] = FakeBunqApi::paymentRequest(56, 'ACTIVE');

        $this->handler->handle($this->notification('BUNQME_TAB', 55));

        $this->assertSame('pending', $this->handler->handle($this->notification('BUNQME_TAB', 56)));
    }

    private function notification($category, $id)
    {
        return json_encode(array('NotificationUrl' => array(
            'category' => $category,
            'object' => array('BunqMeTab' => array('id' => $id)),
        )));
    }
}
