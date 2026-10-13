<?php

namespace BunqTest;

/**
 * Gateway double that records what payment-status.php asks of it instead of talking to bunq.
 */
final class RecordingGateway extends \WC_Bunq_Gateway
{
    /** @var string What check_payment_status() reports. */
    public $outcome = 'pending';
    /** @var \Throwable|null Thrown by check_payment_status() when set. */
    public $check_exception = null;
    /** @var \Throwable|null Thrown by cancel_payment_request() when set. */
    public $cancel_exception = null;
    /** @var int[] Ids of the orders whose payment status was checked. */
    public $checked_orders = array();
    /** @var int[] Payment request ids that were cancelled. */
    public $cancelled_requests = array();

    public function check_payment_status($order)
    {
        $this->checked_orders[] = $order->get_id();

        if ($this->check_exception) {
            throw $this->check_exception;
        }

        return $this->outcome;
    }

    public function cancel_payment_request($payment_request_id)
    {
        if ($this->cancel_exception) {
            throw $this->cancel_exception;
        }

        $this->cancelled_requests[] = $payment_request_id;
    }
}

/**
 * Payment confirmation outside the bunq callback (customer return, Action Scheduler) and cancellation of the
 * bunq payment request when an order is cancelled.
 */
final class PaymentStatusTest extends TestCase
{
    /** @var RecordingGateway */
    private $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new RecordingGateway();
        $this->registerGateway($this->gateway);
    }

    public function testTheHooksAreRegistered()
    {
        $this->assertSame(5, has_action('template_redirect', 'bunq_check_payment_on_return'), 'Before WooCommerce clears the cart at priority 20');
        $this->assertSame(10, has_action('wc_bunq_check_payment', 'bunq_scheduled_payment_check'));
        $this->assertSame(10, has_action('woocommerce_order_status_cancelled', 'bunq_cancel_payment_request_for_order'));
        $this->assertSame(10, has_action('wc_bunq_cancel_payment_request', 'bunq_cancel_payment_request_now'));
    }

    public function testTheRegisteredGatewayIsUsed()
    {
        $this->assertSame($this->gateway, bunq_get_gateway());

        Environment::$gateways = array();
        $this->assertNull(bunq_get_gateway());

        Environment::$gateways = array('bunq' => new \WC_Payment_Gateway());
        $this->assertNull(bunq_get_gateway(), 'Another gateway registered under our id is not ours');
    }

    public function testAnOrderAwaitsPaymentWhenItIsAnUnpaidBunqOrderWithAPaymentRequest()
    {
        $this->assertTrue(bunq_order_awaits_payment($this->unpaidOrder()));
        $this->assertTrue(bunq_order_awaits_payment($this->unpaidOrder(array('status' => 'failed'))));

        $this->assertFalse(bunq_order_awaits_payment(false));
        $this->assertFalse(bunq_order_awaits_payment(null));
        $this->assertFalse(bunq_order_awaits_payment($this->unpaidOrder(array('payment_method' => 'paypal'))));
        $this->assertFalse(bunq_order_awaits_payment($this->unpaidOrder(array('status' => 'processing'))));
        $this->assertFalse(bunq_order_awaits_payment($this->unpaidOrder(array('status' => 'cancelled'))));
        $this->assertFalse(bunq_order_awaits_payment($this->unpaidOrder(array('meta' => array()))));
        $this->assertFalse(bunq_order_awaits_payment($this->unpaidOrder(array('meta' => array('bunq_payment_request_id' => 'abc')))));
    }

    public function testTheCheckDelaysGrowUpToTwoDays()
    {
        $delays = bunq_payment_check_delays();

        $this->assertSame(10 * MINUTE_IN_SECONDS, $delays[0]);
        $this->assertSame(2 * DAY_IN_SECONDS, end($delays));
        $this->assertSame($delays, array_values($delays));
        for ($i = 1; $i < count($delays); $i++) {
            $this->assertGreaterThan($delays[$i - 1], $delays[$i]);
        }
    }

    public function testACheckIsQueuedWithTheDelayOfTheAttempt()
    {
        bunq_schedule_payment_check('42');
        bunq_schedule_payment_check(42, 3);

        $this->assertCount(2, Environment::$scheduledActions);

        $first = Environment::$scheduledActions[0];
        $this->assertSame('wc_bunq_check_payment', $first['hook']);
        $this->assertSame(array(42, 0), $first['args']);
        $this->assertSame('bunq', $first['group']);
        $this->assertEqualsWithDelta(time() + 10 * MINUTE_IN_SECONDS, $first['timestamp'], 2);

        $second = Environment::$scheduledActions[1];
        $this->assertSame(array(42, 3), $second['args']);
        $this->assertEqualsWithDelta(time() + 6 * HOUR_IN_SECONDS, $second['timestamp'], 2);
    }

    public function testNoCheckIsQueuedAfterTheLastAttempt()
    {
        bunq_schedule_payment_check(42, count(bunq_payment_check_delays()));

        $this->assertSame(array(), Environment::$scheduledActions);
    }

    public function testAScheduledCheckQueuesTheNextOneWhilePaymentIsPending()
    {
        $order = $this->unpaidOrder();

        bunq_scheduled_payment_check($order->get_id(), 1);

        $this->assertSame(array($order->get_id()), $this->gateway->checked_orders);
        $this->assertCount(1, Environment::$scheduledActions);
        $this->assertSame(array($order->get_id(), 2), Environment::$scheduledActions[0]['args']);
        $this->assertLogged('debug', 'Scheduled check 2 for order #' . $order->get_order_number() . ': pending');
    }

    public function testAScheduledCheckStopsOnceTheOrderIsSettled()
    {
        $order = $this->unpaidOrder();
        $this->gateway->outcome = 'paid';

        bunq_scheduled_payment_check($order->get_id(), 1);

        $this->assertSame(array($order->get_id()), $this->gateway->checked_orders);
        $this->assertSame(array(), Environment::$scheduledActions);
    }

    public function testAScheduledCheckLogsAFailureAndTriesAgainLater()
    {
        $order = $this->unpaidOrder();
        $this->gateway->check_exception = new \RuntimeException('bunq is down');

        bunq_scheduled_payment_check($order->get_id(), 0);

        $this->assertLogged('error', 'RuntimeException: bunq is down');
        $this->assertCount(1, Environment::$scheduledActions);
        $this->assertSame(array($order->get_id(), 1), Environment::$scheduledActions[0]['args']);
    }

    public function testAScheduledCheckSkipsOrdersThatNoLongerAwaitPayment()
    {
        $order = $this->unpaidOrder(array('status' => 'processing'));

        bunq_scheduled_payment_check($order->get_id(), 0);
        bunq_scheduled_payment_check(999, 0);

        $this->assertSame(array(), $this->gateway->checked_orders);
        $this->assertSame(array(), Environment::$scheduledActions);
    }

    public function testAScheduledCheckNeedsTheGateway()
    {
        $order = $this->unpaidOrder();
        Environment::$gateways = array();

        bunq_scheduled_payment_check($order->get_id(), 0);

        $this->assertSame(array(), Environment::$scheduledActions);
    }

    public function testTheOrderIsCheckedWhenTheCustomerReturnsToTheShop()
    {
        $order = $this->unpaidOrder();
        $this->onOrderReceivedPage($order, $order->get_order_key());

        bunq_check_payment_on_return();

        $this->assertSame(array($order->get_id()), $this->gateway->checked_orders);
        $this->assertLogged('debug', 'Check on return for order #' . $order->get_order_number() . ': pending');
    }

    public function testTheReturnCheckRequiresTheOrderKey()
    {
        $order = $this->unpaidOrder();

        $this->onOrderReceivedPage($order, 'wrong-key');
        bunq_check_payment_on_return();

        $this->onOrderReceivedPage($order, null);
        bunq_check_payment_on_return();

        $this->assertSame(array(), $this->gateway->checked_orders);
    }

    public function testTheReturnCheckOnlyRunsOnTheOrderReceivedPage()
    {
        $order = $this->unpaidOrder();
        $this->onOrderReceivedPage($order, $order->get_order_key());
        Environment::$wcEndpoint = 'order-pay';

        bunq_check_payment_on_return();

        $this->assertSame(array(), $this->gateway->checked_orders);
    }

    public function testTheReturnCheckSkipsOrdersThatNoLongerAwaitPayment()
    {
        $order = $this->unpaidOrder(array('status' => 'processing'));
        $this->onOrderReceivedPage($order, $order->get_order_key());

        bunq_check_payment_on_return();

        $this->assertSame(array(), $this->gateway->checked_orders);
    }

    public function testTheReturnCheckLogsAFailure()
    {
        $order = $this->unpaidOrder();
        $this->onOrderReceivedPage($order, $order->get_order_key());
        $this->gateway->check_exception = new \RuntimeException('bunq is down');

        bunq_check_payment_on_return();

        $this->assertLogged('error', 'RuntimeException: bunq is down');
    }

    public function testCancellingAnOrderQueuesTheCancellationOfItsPaymentRequest()
    {
        $order = $this->unpaidOrder();

        bunq_cancel_payment_request_for_order($order->get_id(), $order);

        $this->assertSame(array(
            array('hook' => 'wc_bunq_cancel_payment_request', 'args' => array($order->get_id()), 'group' => 'bunq'),
        ), Environment::$asyncActions);
    }

    public function testTheOrderIsLookedUpWhenOnlyItsIdIsPassed()
    {
        $order = $this->unpaidOrder();

        bunq_cancel_payment_request_for_order($order->get_id());

        $this->assertCount(1, Environment::$asyncActions);
    }

    public function testNothingIsQueuedWhenThereIsNoPaymentRequestToCancel()
    {
        $other_method = $this->unpaidOrder(array('payment_method' => 'paypal'));
        $no_request = $this->unpaidOrder(array('meta' => array()));
        $already_final = $this->unpaidOrder(array('meta' => array('bunq_payment_request_id' => 55, 'bunq_payment_request_status' => 'EXPIRED')));

        bunq_cancel_payment_request_for_order($other_method->get_id(), $other_method);
        bunq_cancel_payment_request_for_order($no_request->get_id(), $no_request);
        bunq_cancel_payment_request_for_order($already_final->get_id(), $already_final);
        bunq_cancel_payment_request_for_order(999);

        $this->assertSame(array(), Environment::$asyncActions);
    }

    public function testTheCancelledStatusHookQueuesTheCancellation()
    {
        $order = $this->unpaidOrder();

        $order->update_status('cancelled', 'Unpaid for too long');

        $this->assertCount(1, Environment::$asyncActions);
        $this->assertSame(array($order->get_id()), Environment::$asyncActions[0]['args']);
    }

    public function testARequestBunqAlreadyClosedIsNotCancelledAgain()
    {
        // check_payment_status() records bunq's final state before cancelling the order.
        $order = $this->unpaidOrder();
        $order->update_meta_data('bunq_payment_request_status', 'EXPIRED');

        $order->update_status('cancelled', 'bunq payment request 55 is expired');

        $this->assertSame(array(), Environment::$asyncActions);
    }

    public function testTheQueuedCancellationCancelsTheRequestAtBunq()
    {
        $order = $this->unpaidOrder();

        bunq_cancel_payment_request_now($order->get_id());

        $this->assertSame(array(55), $this->gateway->cancelled_requests);
        $this->assertSame('CANCELLED', $order->get_meta('bunq_payment_request_status'));
        $this->assertSame(1, $order->save_count);
        $this->assertSame(array('bunq payment request 55 cancelled'), $order->notes);
        $this->assertLogged('info', 'bunqme-tab 55 cancelled for order #' . $order->get_order_number());
    }

    public function testTheQueuedCancellationIsSkippedWhenTheRequestIsAlreadyFinal()
    {
        $order = $this->unpaidOrder(array('meta' => array('bunq_payment_request_id' => 55, 'bunq_payment_request_status' => 'EXPIRED')));
        $other_method = $this->unpaidOrder(array('payment_method' => 'paypal'));

        bunq_cancel_payment_request_now($order->get_id());
        bunq_cancel_payment_request_now($other_method->get_id());
        bunq_cancel_payment_request_now(999);

        $this->assertSame(array(), $this->gateway->cancelled_requests);
        $this->assertSame('EXPIRED', $order->get_meta('bunq_payment_request_status'));
    }

    public function testAFailedCancellationIsLoggedAndLeavesTheOrderUntouched()
    {
        $order = $this->unpaidOrder();
        $this->gateway->cancel_exception = new \RuntimeException('bunq is down');

        bunq_cancel_payment_request_now($order->get_id());

        $this->assertLogged('warning', 'RuntimeException: bunq is down');
        $this->assertSame('', $order->get_meta('bunq_payment_request_status'));
        $this->assertSame(0, $order->save_count);
        $this->assertSame(array(), $order->notes);
    }

    private function unpaidOrder(array $data = array())
    {
        return Environment::addOrder(array_merge(array(
            'meta' => array('bunq_payment_request_id' => 55),
        ), $data));
    }

    private function onOrderReceivedPage(\WC_Order $order, $key)
    {
        Environment::$wcEndpoint = 'order-received';
        $GLOBALS['wp'] = (object) array('query_vars' => array('order-received' => (string) $order->get_id()));
        $_GET = $key === null ? array() : array('key' => $key);
    }
}
