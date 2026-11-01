<?php

namespace BunqTest\Integration;

use BunqTest\FakeBunqApi;
use WC_Bunq_Gateway;

/**
 * Payments, payment confirmation and refunds on real WooCommerce orders, with only bunq itself faked.
 */
final class BunqPaymentFlowTest extends TestCase
{
    /** @var FakeBunqApi */
    private $bunq;

    /** @var WC_Bunq_Gateway */
    private $gateway;

    public function set_up()
    {
        parent::set_up();

        $this->bunq = new FakeBunqApi();
        update_option('woocommerce_bunq_settings', $this->liveSettings(array('api_context' => '{"live":1}')));
        $this->gateway = new WC_Bunq_Gateway($this->bunq);
        $this->useGateway($this->gateway);
    }

    public function testAPaymentRequestIsCreatedAndCheckedInTheBackground()
    {
        $order = $this->createOrder(array('price' => '24.95', 'meta' => array()));

        $result = $this->gateway->process_payment($order->get_id());

        $this->assertSame(array('result' => 'success', 'redirect' => 'https://bunq.me/shop/24.95/1'), $result);
        $this->assertSame(array(array('24.95', 'EUR', '#' . $order->get_order_number(), $this->gateway->get_return_url($order), 123)), $this->bunq->callsTo('create_payment_request'));

        $order = wc_get_order($order->get_id());
        $this->assertSame('55', (string) $order->get_meta('bunq_payment_request_id'));
        $this->assertSame(array('bunq payment request 55 created'), $this->bunqOrderNotes($order));
        $this->assertEqualsWithDelta(time() + 10 * MINUTE_IN_SECONDS, as_next_scheduled_action('wc_bunq_check_payment', array($order->get_id(), 0), 'bunq'), 5);
    }

    public function testAMatchingPaymentCompletesTheOrder()
    {
        $order = $this->createOrder(array('price' => '24.95'));
        $this->bunqReports('ACTIVE', array(FakeBunqApi::payment(777, '24.95', 'EUR', 456)));

        $this->assertSame('paid', $this->gateway->check_payment_status($order));

        $order = wc_get_order($order->get_id());
        $this->assertTrue($order->is_paid());
        $this->assertSame('processing', $order->get_status());
        $this->assertSame('777', $order->get_transaction_id());
        $this->assertSame('456', (string) $order->get_meta('bunq_monetary_account_id'));
        $this->assertSame(array('bunq payment 777 received'), $this->bunqOrderNotes($order));
    }

    /**
     * @dataProvider orderStorages
     */
    public function testTwoChecksOfTheSameOrderRecordThePaymentOnce($highPerformanceOrderStorage)
    {
        if ($highPerformanceOrderStorage) {
            $this->useHighPerformanceOrderStorage();
        }
        $order = $this->createOrder(array('price' => '24.95'));
        $this->bunqReports('ACTIVE', array(FakeBunqApi::payment(777, '24.95', 'EUR', 456)));

        // The callback and the customer's return load the order at the same time; the return checks once the
        // callback is done.
        $callback = wc_get_order($order->get_id());
        $return = wc_get_order($order->get_id());

        $this->assertSame('paid', $this->gateway->check_payment_status($callback));
        $this->assertSame('settled', $this->gateway->check_payment_status($return));

        $order = wc_get_order($order->get_id());
        $this->assertSame(array('777'), $this->metaValues($order, 'bunq_payment_id'));
        $this->assertSame(array('456'), $this->metaValues($order, 'bunq_monetary_account_id'));
        $this->assertSame(array('bunq payment 777 received'), $this->bunqOrderNotes($order));
    }

    public function testAPaymentOfAnotherAmountPutsTheOrderOnHold()
    {
        $order = $this->createOrder(array('price' => '24.95'));
        $this->bunqReports('ACTIVE', array(FakeBunqApi::payment(777, '20.00')));

        $this->assertSame('on-hold', $this->gateway->check_payment_status($order));

        $order = wc_get_order($order->get_id());
        $this->assertSame('on-hold', $order->get_status());
        $this->assertSame('', $order->get_transaction_id());
        $this->assertSame('777', (string) $order->get_meta('bunq_payment_id'));
        $this->assertStringContainsString('bunq payment 777 of EUR 20.00 received, but the order total is EUR 24.95.', implode("\n", $this->bunqOrderNotes($order)));
    }

    public function testAnExpiredRequestCancelsTheOrderWithoutAnotherCallToBunq()
    {
        $order = $this->createOrder();
        $this->bunqReports('EXPIRED');

        $this->assertSame('cancelled', $this->gateway->check_payment_status($order));

        $order = wc_get_order($order->get_id());
        $this->assertSame('cancelled', $order->get_status());
        $this->assertSame('EXPIRED', $order->get_meta('bunq_payment_request_status'));
        $this->assertStringContainsString('bunq payment request 55 is expired', implode("\n", $this->bunqOrderNotes($order)));
        $this->assertFalse(as_has_scheduled_action('wc_bunq_cancel_payment_request', null, 'bunq'));
    }

    public function testCancellingAnOrderCancelsItsPaymentRequestAtBunqInTheBackground()
    {
        $order = $this->createOrder();

        $order->update_status('cancelled');
        $this->assertTrue(as_has_scheduled_action('wc_bunq_cancel_payment_request', array($order->get_id()), 'bunq'));
        do_action('wc_bunq_cancel_payment_request', $order->get_id());

        $this->assertSame(array(array(55, 123)), $this->bunq->callsTo('cancel_payment_request'));
        $this->assertSame('CANCELLED', wc_get_order($order->get_id())->get_meta('bunq_payment_request_status'));
    }

    public function testAScheduledCheckCompletesAPaidOrder()
    {
        $order = $this->createOrder();
        $this->bunqReports('ACTIVE', array(FakeBunqApi::payment(777, '10.00')));

        do_action('wc_bunq_check_payment', $order->get_id(), 0);

        $this->assertSame('processing', wc_get_order($order->get_id())->get_status());
        $this->assertFalse(as_has_scheduled_action('wc_bunq_check_payment', null, 'bunq'));
    }

    public function testARefundFromTheOrderScreenCreatesADraftPaymentAtBunq()
    {
        $order = $this->createOrder(array('status' => 'processing', 'price' => '24.95', 'meta' => array('bunq_payment_request_id' => 55, 'bunq_payment_id' => 777, 'bunq_monetary_account_id' => 456)));
        $this->bunq->payments[777] = FakeBunqApi::payment(777, '24.95', 'EUR', 456, 'NL12BUNQ0123456789', 'J. Doe');

        $refund = wc_create_refund(array(
            'order_id' => $order->get_id(),
            'amount' => '10.00',
            'reason' => 'Damaged',
            'refund_payment' => true,
        ));

        $this->assertInstanceOf(\WC_Order_Refund::class, $refund);
        $this->assertSame(array(array(
            10.0,
            'EUR',
            'NL12BUNQ0123456789',
            'J. Doe',
            'Refund order ' . $order->get_order_number() . ' ' . get_bloginfo('name') . ': Damaged',
            456,
        )), $this->bunq->callsTo('create_refund'));
        $this->assertMatchesRegularExpression('/^bunq refund of .*10\.00.* created as draft payment 9001\. Approve it in the bunq app to send the money\.$/', implode("\n", $this->bunqOrderNotes(wc_get_order($order->get_id()))));
    }

    public function testARefundBunqCannotMakeIsNotRecorded()
    {
        $order = $this->createOrder(array('status' => 'processing', 'meta' => array('bunq_payment_request_id' => 55, 'bunq_payment_id' => 777)));
        $this->bunq->payments[777] = FakeBunqApi::payment(777, '10.00', 'EUR', 123, null);

        $refund = wc_create_refund(array('order_id' => $order->get_id(), 'amount' => '5.00', 'refund_payment' => true));

        $this->assertWPError($refund);
        $this->assertStringContainsString('has no IBAN to refund to', $refund->get_error_message());
        $this->assertSame(array(), wc_get_order($order->get_id())->get_refunds());
    }

    public function orderStorages()
    {
        return array('posts' => array(false), 'HPOS' => array(true));
    }

    /**
     * @return string[] Every value stored for the meta key, so a duplicate shows.
     */
    private function metaValues(\WC_Order $order, $key)
    {
        return array_map(function ($meta) {
            return (string) $meta->value;
        }, array_values($order->get_meta($key, false)));
    }

    private function bunqReports($status, array $payments = array())
    {
        $this->bunq->paymentRequests[55] = FakeBunqApi::paymentRequest(55, $status, $payments);
    }
}
