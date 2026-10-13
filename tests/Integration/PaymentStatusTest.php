<?php

namespace BunqTest\Integration;

use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;
use PatrickKivits\BunqForWooCommerce\Orders\PaymentMonitor;
use PatrickKivits\BunqForWooCommerce\Plugin;

/**
 * Gateway double that records what the payment monitor asks of it instead of talking to bunq.
 */
final class RecordingGateway extends Gateway
{
    public $outcome = 'pending';
    public $check_exception = null;
    public $cancel_exception = null;
    public $checked_orders = array();
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
 * Payment confirmation outside the bunq callback, on real orders with the real Action Scheduler and the real
 * WooCommerce order status transitions.
 */
final class PaymentStatusTest extends TestCase
{
    /** @var RecordingGateway */
    private $gateway;

    public function set_up()
    {
        parent::set_up();
        $this->gateway = new RecordingGateway();
        $this->useGateway($this->gateway);
    }

    public function testTheRegisteredGatewayIsUsed()
    {
        $this->assertSame($this->gateway, Plugin::instance()->gateway());
    }

    public function testAnOrderAwaitsPaymentWhenItIsAnUnpaidBunqOrderWithAPaymentRequest()
    {
        $this->assertTrue(PaymentMonitor::order_awaits_payment($this->createOrder()));
        $this->assertTrue(PaymentMonitor::order_awaits_payment($this->createOrder(array('status' => 'failed'))));

        $this->assertFalse(PaymentMonitor::order_awaits_payment(false));
        $this->assertFalse(PaymentMonitor::order_awaits_payment($this->createOrder(array('payment_method' => 'bacs'))));
        $this->assertFalse(PaymentMonitor::order_awaits_payment($this->createOrder(array('status' => 'processing'))));
        $this->assertFalse(PaymentMonitor::order_awaits_payment($this->createOrder(array('status' => 'on-hold'))));
        $this->assertFalse(PaymentMonitor::order_awaits_payment($this->createOrder(array('meta' => array()))));
    }

    public function testACheckIsQueuedWithActionSchedulerForTheDelayOfTheAttempt()
    {
        $order = $this->createOrder();

        PaymentMonitor::schedule_check($order->get_id());
        PaymentMonitor::schedule_check($order->get_id(), 3);

        $this->assertEqualsWithDelta(time() + 10 * MINUTE_IN_SECONDS, as_next_scheduled_action('wc_bunq_check_payment', array($order->get_id(), 0), 'bunq'), 5);
        $this->assertEqualsWithDelta(time() + 6 * HOUR_IN_SECONDS, as_next_scheduled_action('wc_bunq_check_payment', array($order->get_id(), 3), 'bunq'), 5);
        $this->assertFalse(as_next_scheduled_action('wc_bunq_check_payment', array($order->get_id(), 1), 'bunq'));
    }

    public function testNoCheckIsQueuedAfterTheLastAttempt()
    {
        $order = $this->createOrder();

        PaymentMonitor::schedule_check($order->get_id(), count(PaymentMonitor::check_delays()));

        $this->assertFalse(as_has_scheduled_action('wc_bunq_check_payment', null, 'bunq'));
    }

    public function testAScheduledCheckQueuesTheNextOneWhilePaymentIsPending()
    {
        $order = $this->createOrder();

        $this->monitor()->run_scheduled_check($order->get_id(), 1);

        $this->assertSame(array($order->get_id()), $this->gateway->checked_orders);
        $this->assertTrue(as_has_scheduled_action('wc_bunq_check_payment', array($order->get_id(), 2), 'bunq'));
        $this->assertLogged('debug', 'Scheduled check 2 for order #' . $order->get_order_number() . ': pending');
    }

    public function testAScheduledCheckStopsOnceTheOrderIsSettled()
    {
        $order = $this->createOrder();
        $this->gateway->outcome = 'paid';

        $this->monitor()->run_scheduled_check($order->get_id(), 1);

        $this->assertSame(array($order->get_id()), $this->gateway->checked_orders);
        $this->assertFalse(as_has_scheduled_action('wc_bunq_check_payment', null, 'bunq'));
    }

    public function testAScheduledCheckLogsAFailureAndTriesAgainLater()
    {
        $order = $this->createOrder();
        $this->gateway->check_exception = new \RuntimeException('bunq is down');

        $this->monitor()->run_scheduled_check($order->get_id(), 0);

        $this->assertLogged('error', 'RuntimeException: bunq is down');
        $this->assertTrue(as_has_scheduled_action('wc_bunq_check_payment', array($order->get_id(), 1), 'bunq'));
    }

    public function testAScheduledCheckSkipsOrdersThatNoLongerAwaitPayment()
    {
        $order = $this->createOrder(array('status' => 'processing'));

        $this->monitor()->run_scheduled_check($order->get_id(), 0);
        $this->monitor()->run_scheduled_check(999999, 0);

        $this->assertSame(array(), $this->gateway->checked_orders);
        $this->assertFalse(as_has_scheduled_action('wc_bunq_check_payment', null, 'bunq'));
    }

    public function testTheOrderIsCheckedWhenTheCustomerReturnsToTheShop()
    {
        $order = $this->createOrder();
        $this->onOrderReceivedPage($order, $order->get_order_key());

        $this->monitor()->check_on_return();

        $this->assertSame(array($order->get_id()), $this->gateway->checked_orders);
        $this->assertLogged('debug', 'Check on return for order #' . $order->get_order_number() . ': pending');
    }

    public function testTheReturnCheckRequiresTheOrderKey()
    {
        $order = $this->createOrder();

        $this->onOrderReceivedPage($order, 'wrong-key');
        $this->monitor()->check_on_return();

        $this->onOrderReceivedPage($order, null);
        $this->monitor()->check_on_return();

        $this->assertSame(array(), $this->gateway->checked_orders);
    }

    public function testTheReturnCheckOnlyRunsOnTheOrderReceivedPage()
    {
        $order = $this->createOrder();
        $_GET['key'] = $order->get_order_key();

        $this->monitor()->check_on_return();

        $this->assertSame(array(), $this->gateway->checked_orders);
    }

    public function testCancellingAnOrderInWooCommerceQueuesTheCancellationOfItsPaymentRequest()
    {
        $order = $this->createOrder();

        $order->update_status('cancelled', 'Unpaid for too long');

        $this->assertTrue(as_has_scheduled_action('wc_bunq_cancel_payment_request', array($order->get_id()), 'bunq'));
    }

    public function testARequestBunqAlreadyClosedIsNotCancelledAgain()
    {
        // check_payment_status() records bunq's final state before it cancels the order.
        $order = $this->createOrder(array('meta' => array('bunq_payment_request_id' => 55, 'bunq_payment_request_status' => 'EXPIRED')));

        $order->update_status('cancelled', 'bunq payment request 55 is expired');

        $this->assertFalse(as_has_scheduled_action('wc_bunq_cancel_payment_request', array($order->get_id()), 'bunq'));
    }

    public function testCancellingOrdersOfOtherGatewaysDoesNotInvolveBunq()
    {
        $order = $this->createOrder(array('payment_method' => 'bacs'));
        $without_request = $this->createOrder(array('meta' => array()));

        $order->update_status('cancelled');
        $without_request->update_status('cancelled');

        $this->assertFalse(as_has_scheduled_action('wc_bunq_cancel_payment_request', null, 'bunq'));
    }

    public function testTheQueuedCancellationCancelsTheRequestAtBunq()
    {
        $order = $this->createOrder();

        $this->monitor()->cancel_payment_request($order->get_id());

        $this->assertSame(array(55), $this->gateway->cancelled_requests);

        $order = wc_get_order($order->get_id());
        $this->assertSame('CANCELLED', $order->get_meta('bunq_payment_request_status'));
        $this->assertSame(array('bunq payment request 55 cancelled'), $this->bunqOrderNotes($order));
        $this->assertLogged('info', 'bunqme-tab 55 cancelled for order #' . $order->get_order_number());
    }

    public function testTheQueuedCancellationIsSkippedWhenTheRequestIsAlreadyFinal()
    {
        $order = $this->createOrder(array('meta' => array('bunq_payment_request_id' => 55, 'bunq_payment_request_status' => 'EXPIRED')));

        $this->monitor()->cancel_payment_request($order->get_id());
        $this->monitor()->cancel_payment_request(999999);

        $this->assertSame(array(), $this->gateway->cancelled_requests);
        $this->assertSame('EXPIRED', wc_get_order($order->get_id())->get_meta('bunq_payment_request_status'));
    }

    public function testAFailedCancellationIsLoggedAndLeavesTheOrderUntouched()
    {
        $order = $this->createOrder();
        $this->gateway->cancel_exception = new \RuntimeException('bunq is down');

        $this->monitor()->cancel_payment_request($order->get_id());

        $this->assertLogged('warning', 'RuntimeException: bunq is down');

        $order = wc_get_order($order->get_id());
        $this->assertSame('', $order->get_meta('bunq_payment_request_status'));
        $this->assertSame(array(), $this->bunqOrderNotes($order));
    }

    private function onOrderReceivedPage(\WC_Order $order, $key)
    {
        $GLOBALS['wp']->query_vars['order-received'] = (string) $order->get_id();
        $_GET = $key === null ? array() : array('key' => $key);
    }

    private function monitor()
    {
        return Plugin::instance()->payment_monitor();
    }
}
