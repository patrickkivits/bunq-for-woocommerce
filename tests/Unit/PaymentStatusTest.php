<?php

namespace BunqTest;

use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;
use PatrickKivits\BunqForWooCommerce\Orders\PaymentMonitor;
use PatrickKivits\BunqForWooCommerce\Plugin;

/**
 * Gateway double that records what the payment monitor asks of it instead of talking to bunq.
 */
final class RecordingGateway extends Gateway
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
        $this->assertSame(5, has_action('template_redirect', array($this->monitor(), 'check_on_return')), 'Before WooCommerce clears the cart at priority 20');
        $this->assertSame(10, has_action('wc_bunq_check_payment', array($this->monitor(), 'run_scheduled_check')));
        $this->assertSame(10, has_action('woocommerce_order_status_cancelled', array($this->monitor(), 'queue_cancellation')));
        $this->assertSame(10, has_action('wc_bunq_cancel_payment_request', array($this->monitor(), 'cancel_payment_request')));
    }

    public function testTheRegisteredGatewayIsUsed()
    {
        $this->assertSame($this->gateway, Plugin::instance()->gateway());

        Environment::$gateways = array();
        $this->assertNull(Plugin::instance()->gateway());

        Environment::$gateways = array('bunq' => new \WC_Payment_Gateway());
        $this->assertNull(Plugin::instance()->gateway(), 'Another gateway registered under our id is not ours');
    }

    public function testAnOrderAwaitsPaymentWhenItIsAnUnpaidBunqOrderWithAPaymentRequest()
    {
        $this->assertTrue(PaymentMonitor::order_awaits_payment($this->unpaidOrder()));
        $this->assertTrue(PaymentMonitor::order_awaits_payment($this->unpaidOrder(array('status' => 'failed'))));

        $this->assertFalse(PaymentMonitor::order_awaits_payment(false));
        $this->assertFalse(PaymentMonitor::order_awaits_payment(null));
        $this->assertFalse(PaymentMonitor::order_awaits_payment($this->unpaidOrder(array('payment_method' => 'paypal'))));
        $this->assertFalse(PaymentMonitor::order_awaits_payment($this->unpaidOrder(array('status' => 'processing'))));
        $this->assertFalse(PaymentMonitor::order_awaits_payment($this->unpaidOrder(array('status' => 'cancelled'))));
        $this->assertFalse(PaymentMonitor::order_awaits_payment($this->unpaidOrder(array('meta' => array()))));
        $this->assertFalse(PaymentMonitor::order_awaits_payment($this->unpaidOrder(array('meta' => array('bunq_payment_request_id' => 'abc')))));
    }

    public function testTheCheckDelaysGrowUpToTwoDays()
    {
        $delays = PaymentMonitor::check_delays();

        $this->assertSame(10 * MINUTE_IN_SECONDS, $delays[0]);
        $this->assertSame(2 * DAY_IN_SECONDS, end($delays));
        $this->assertSame($delays, array_values($delays));
        for ($i = 1; $i < count($delays); $i++) {
            $this->assertGreaterThan($delays[$i - 1], $delays[$i]);
        }
    }

    public function testACheckIsQueuedWithTheDelayOfTheAttempt()
    {
        PaymentMonitor::schedule_check('42');
        PaymentMonitor::schedule_check(42, 3);

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
        PaymentMonitor::schedule_check(42, count(PaymentMonitor::check_delays()));

        $this->assertSame(array(), Environment::$scheduledActions);
    }

    public function testAScheduledCheckQueuesTheNextOneWhilePaymentIsPending()
    {
        $order = $this->unpaidOrder();

        $this->monitor()->run_scheduled_check($order->get_id(), 1);

        $this->assertSame(array($order->get_id()), $this->gateway->checked_orders);
        $this->assertCount(1, Environment::$scheduledActions);
        $this->assertSame(array($order->get_id(), 2), Environment::$scheduledActions[0]['args']);
        $this->assertLogged('debug', 'Scheduled check 2 for order #' . $order->get_order_number() . ': pending');
    }

    public function testAScheduledCheckStopsOnceTheOrderIsSettled()
    {
        $order = $this->unpaidOrder();
        $this->gateway->outcome = 'paid';

        $this->monitor()->run_scheduled_check($order->get_id(), 1);

        $this->assertSame(array($order->get_id()), $this->gateway->checked_orders);
        $this->assertSame(array(), Environment::$scheduledActions);
    }

    public function testAScheduledCheckLogsAFailureAndTriesAgainLater()
    {
        $order = $this->unpaidOrder();
        $this->gateway->check_exception = new \RuntimeException('bunq is down');

        $this->monitor()->run_scheduled_check($order->get_id(), 0);

        $this->assertLogged('error', 'RuntimeException: bunq is down');
        $this->assertCount(1, Environment::$scheduledActions);
        $this->assertSame(array($order->get_id(), 1), Environment::$scheduledActions[0]['args']);
    }

    public function testAScheduledCheckSkipsOrdersThatNoLongerAwaitPayment()
    {
        $order = $this->unpaidOrder(array('status' => 'processing'));

        $this->monitor()->run_scheduled_check($order->get_id(), 0);
        $this->monitor()->run_scheduled_check(999, 0);

        $this->assertSame(array(), $this->gateway->checked_orders);
        $this->assertSame(array(), Environment::$scheduledActions);
    }

    public function testAScheduledCheckNeedsTheGateway()
    {
        $order = $this->unpaidOrder();
        Environment::$gateways = array();

        $this->monitor()->run_scheduled_check($order->get_id(), 0);

        $this->assertSame(array(), Environment::$scheduledActions);
    }

    public function testTheOrderIsCheckedWhenTheCustomerReturnsToTheShop()
    {
        $order = $this->unpaidOrder();
        $this->onOrderReceivedPage($order, $order->get_order_key());

        $this->monitor()->check_on_return();

        $this->assertSame(array($order->get_id()), $this->gateway->checked_orders);
        $this->assertLogged('debug', 'Check on return for order #' . $order->get_order_number() . ': pending');
    }

    public function testTheReturnCheckRequiresTheOrderKey()
    {
        $order = $this->unpaidOrder();

        $this->onOrderReceivedPage($order, 'wrong-key');
        $this->monitor()->check_on_return();

        $this->onOrderReceivedPage($order, null);
        $this->monitor()->check_on_return();

        $this->assertSame(array(), $this->gateway->checked_orders);
    }

    public function testTheReturnCheckOnlyRunsOnTheOrderReceivedPage()
    {
        $order = $this->unpaidOrder();
        $this->onOrderReceivedPage($order, $order->get_order_key());
        Environment::$wcEndpoint = 'order-pay';

        $this->monitor()->check_on_return();

        $this->assertSame(array(), $this->gateway->checked_orders);
    }

    public function testTheReturnCheckSkipsOrdersThatNoLongerAwaitPayment()
    {
        $order = $this->unpaidOrder(array('status' => 'processing'));
        $this->onOrderReceivedPage($order, $order->get_order_key());

        $this->monitor()->check_on_return();

        $this->assertSame(array(), $this->gateway->checked_orders);
    }

    public function testTheReturnCheckLogsAFailure()
    {
        $order = $this->unpaidOrder();
        $this->onOrderReceivedPage($order, $order->get_order_key());
        $this->gateway->check_exception = new \RuntimeException('bunq is down');

        $this->monitor()->check_on_return();

        $this->assertLogged('error', 'RuntimeException: bunq is down');
    }

    public function testCancellingAnOrderQueuesTheCancellationOfItsPaymentRequest()
    {
        $order = $this->unpaidOrder();

        $this->monitor()->queue_cancellation($order->get_id(), $order);

        $this->assertSame(array(
            array('hook' => 'wc_bunq_cancel_payment_request', 'args' => array($order->get_id()), 'group' => 'bunq'),
        ), Environment::$asyncActions);
    }

    public function testTheOrderIsLookedUpWhenOnlyItsIdIsPassed()
    {
        $order = $this->unpaidOrder();

        $this->monitor()->queue_cancellation($order->get_id());

        $this->assertCount(1, Environment::$asyncActions);
    }

    public function testNothingIsQueuedWhenThereIsNoPaymentRequestToCancel()
    {
        $other_method = $this->unpaidOrder(array('payment_method' => 'paypal'));
        $no_request = $this->unpaidOrder(array('meta' => array()));
        $already_final = $this->unpaidOrder(array('meta' => array('bunq_payment_request_id' => 55, 'bunq_payment_request_status' => 'EXPIRED')));

        $this->monitor()->queue_cancellation($other_method->get_id(), $other_method);
        $this->monitor()->queue_cancellation($no_request->get_id(), $no_request);
        $this->monitor()->queue_cancellation($already_final->get_id(), $already_final);
        $this->monitor()->queue_cancellation(999);

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

        $this->monitor()->cancel_payment_request($order->get_id());

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

        $this->monitor()->cancel_payment_request($order->get_id());
        $this->monitor()->cancel_payment_request($other_method->get_id());
        $this->monitor()->cancel_payment_request(999);

        $this->assertSame(array(), $this->gateway->cancelled_requests);
        $this->assertSame('EXPIRED', $order->get_meta('bunq_payment_request_status'));
    }

    public function testAFailedCancellationIsLoggedAndLeavesTheOrderUntouched()
    {
        $order = $this->unpaidOrder();
        $this->gateway->cancel_exception = new \RuntimeException('bunq is down');

        $this->monitor()->cancel_payment_request($order->get_id());

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

    private function monitor()
    {
        return Plugin::instance()->payment_monitor();
    }
}
