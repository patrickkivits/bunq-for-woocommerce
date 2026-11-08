<?php

namespace BunqTest;

use PatrickKivits\BunqForWooCommerce\Gateway\Gateway;
use PatrickKivits\BunqForWooCommerce\Orders\PaymentLink;
use PatrickKivits\BunqForWooCommerce\Plugin;

/**
 * The "Send bunq payment link to customer" order action: getting a bunq.me link for an unpaid order and emailing it.
 */
final class PaymentLinkTest extends TestCase
{
    /** @var FakeBunqApi */
    private $bunq;

    /** @var PaymentLink */
    private $paymentLink;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bunq = new FakeBunqApi();
        $this->bunq->createdPaymentRequest = array('id' => 56, 'url' => 'https://bunq.me/shop/new');
        $this->paymentLink = new PaymentLink(Plugin::instance());
    }

    // --- The order action -----------------------------------------------------------------------------------

    public function testThePluginRegistersTheOrderActionAndTheEmail()
    {
        $this->assertNotEmpty(bunqtest_hook_callbacks('woocommerce_order_actions'));
        $this->assertNotEmpty(bunqtest_hook_callbacks('woocommerce_order_action_bunq_send_payment_link'));
        $this->assertNotEmpty(bunqtest_hook_callbacks('woocommerce_email_classes'));
    }

    public function testTheActionIsOfferedForAnUnpaidOrderInEuros()
    {
        $this->connectedGateway();
        $order = Environment::addOrder(array('payment_method' => ''));

        $actions = $this->paymentLink->add_order_action(array('send_order_details' => 'Send order details'), $order);

        $this->assertSame(array(
            'send_order_details' => 'Send order details',
            'bunq_send_payment_link' => 'Send bunq payment link to customer',
        ), $actions);
    }

    public function testWithoutTheOrderArgumentTheActionLooksAtTheOrderBeingEdited()
    {
        $this->connectedGateway();
        $GLOBALS['theorder'] = Environment::addOrder();

        try {
            $this->assertArrayHasKey('bunq_send_payment_link', $this->paymentLink->add_order_action(array()));
        } finally {
            unset($GLOBALS['theorder']);
        }
    }

    /**
     * @dataProvider ordersWithoutTheAction
     */
    public function testTheActionIsNotOfferedWhenTheOrderCannotBePaidWithALink($order_data)
    {
        $this->connectedGateway();

        $this->assertSame(array(), $this->paymentLink->add_order_action(array(), Environment::addOrder($order_data)));
    }

    public function ordersWithoutTheAction()
    {
        return array(
            'paid' => array(array('status' => 'processing')),
            'on hold' => array(array('status' => 'on-hold')),
            'nothing to pay' => array(array('total' => '0.00')),
            'not in euros' => array(array('currency' => 'USD')),
        );
    }

    public function testTheActionIsNotOfferedWhileBunqIsNotConnected()
    {
        $this->registerGateway(new Gateway($this->bunq));

        $this->assertSame(array(), $this->paymentLink->add_order_action(array(), Environment::addOrder()));
    }

    // --- Sending the link -----------------------------------------------------------------------------------

    public function testANewPaymentRequestIsCreatedAndEmailedToTheCustomer()
    {
        $this->connectedGateway();
        $order = Environment::addOrder(array('total' => '24.95', 'payment_method' => 'bacs', 'billing_email' => 'jane@example.com'));

        do_action('woocommerce_order_action_bunq_send_payment_link', $order);

        $this->assertSame(array(array(
            '24.95',
            'EUR',
            '#' . $order->get_order_number(),
            'https://shop.example/checkout/order-received/' . $order->get_id() . '/?key=' . $order->get_order_key(),
            123,
        )), $this->bunq->callsTo('create_payment_request'));
        $this->assertSame(56, $order->get_meta('bunq_payment_request_id'));
        $this->assertCount(1, Environment::$sentEmails);
        $this->assertSame('jane@example.com', Environment::$sentEmails[0]['to']);
        $this->assertSame('Pay for your Test Shop order #' . $order->get_order_number(), Environment::$sentEmails[0]['subject']);
        $this->assertStringContainsString('<a href="https://bunq.me/shop/new"', Environment::$sentEmails[0]['message']);
        $this->assertStringContainsString('Hi Jane,', Environment::$sentEmails[0]['message']);
        $this->assertSame(array('bunq payment request 56 created', 'bunq payment link sent to jane@example.com'), $order->notes);
        $this->assertSame(array(), Environment::$adminErrors);
    }

    public function testTheOrderIsSwitchedToBunqSoThePaymentIsFollowed()
    {
        $this->connectedGateway();
        $order = Environment::addOrder(array('payment_method' => 'bacs'));

        $this->paymentLink->send($order);

        $this->assertSame('bunq', $order->get_payment_method());
        $this->assertSame('iDEAL | Wero, Credit Card or Bancontact', $order->get_payment_method_title());
        $this->assertCount(1, Environment::$scheduledActions);
        $this->assertSame('wc_bunq_check_payment', Environment::$scheduledActions[0]['hook']);
    }

    public function testASeparatePaymentMethodGatewayIsKept()
    {
        $this->connectedGateway();
        $order = Environment::addOrder(array('payment_method' => 'bunq_ideal'));

        $this->paymentLink->send($order);

        $this->assertSame('bunq_ideal', $order->get_payment_method());
    }

    public function testAnActivePaymentRequestForTheOrderTotalIsSentAgain()
    {
        $this->connectedGateway();
        $order = $this->orderWithPaymentRequest('24.95');
        $this->bunq->paymentRequests[55] = $this->activePaymentRequest(55, '24.95');

        $this->paymentLink->send($order);

        $this->assertSame(array(), $this->bunq->callsTo('create_payment_request'));
        $this->assertSame(array(), $this->bunq->callsTo('cancel_payment_request'));
        $this->assertSame(55, $order->get_meta('bunq_payment_request_id'));
        $this->assertStringContainsString('https://bunq.me/shop/existing', Environment::$sentEmails[0]['message']);
        $this->assertSame(array('bunq payment link sent to jane@example.com'), $order->notes);
    }

    public function testAnActivePaymentRequestForAnotherAmountIsReplaced()
    {
        $this->connectedGateway();
        $order = $this->orderWithPaymentRequest('30.00');
        $this->bunq->paymentRequests[55] = $this->activePaymentRequest(55, '24.95');

        $this->paymentLink->send($order);

        $this->assertSame(array(array(55, 123)), $this->bunq->callsTo('cancel_payment_request'));
        $this->assertSame('30.00', $this->bunq->callsTo('create_payment_request')[0][0]);
        $this->assertSame(56, $order->get_meta('bunq_payment_request_id'));
        $this->assertStringContainsString('https://bunq.me/shop/new', Environment::$sentEmails[0]['message']);
        $this->assertSame(array('bunq payment request 55 cancelled', 'bunq payment request 56 created', 'bunq payment link sent to jane@example.com'), $order->notes);
    }

    public function testAnExpiredPaymentRequestIsReplacedWithoutCancellingTheOrder()
    {
        $this->connectedGateway();
        $order = $this->orderWithPaymentRequest('24.95');
        $this->bunq->paymentRequests[55] = FakeBunqApi::paymentRequest(55, 'EXPIRED');

        $this->paymentLink->send($order);

        $this->assertSame('pending', $order->get_status());
        $this->assertSame(array(), $this->bunq->callsTo('cancel_payment_request'));
        $this->assertSame(56, $order->get_meta('bunq_payment_request_id'));
        $this->assertCount(1, Environment::$sentEmails);
    }

    public function testAPaymentRequestBunqReportedAsFinalIsNotAskedForAndItsStatusIsCleared()
    {
        $this->connectedGateway();
        $order = Environment::addOrder(array('status' => 'failed', 'meta' => array('bunq_payment_request_id' => 55, 'bunq_payment_request_status' => 'CANCELLED')));

        $this->paymentLink->send($order);

        $this->assertSame(array(), $this->bunq->callsTo('get_payment_request'));
        $this->assertSame(56, $order->get_meta('bunq_payment_request_id'));
        $this->assertSame('', $order->get_meta('bunq_payment_request_status'));
    }

    public function testAPaymentThatAlreadyCameInIsRecordedInsteadOfSendingALink()
    {
        $this->connectedGateway();
        $order = $this->orderWithPaymentRequest('24.95');
        $this->bunq->paymentRequests[55] = FakeBunqApi::paymentRequest(55, 'ACTIVE', array(FakeBunqApi::payment(777, '24.95')));

        $this->paymentLink->send($order);

        $this->assertSame('processing', $order->get_status());
        $this->assertSame(array(), $this->bunq->callsTo('create_payment_request'));
        $this->assertSame(array(), Environment::$sentEmails);
        $this->assertSame(array('The bunq payment link was not sent: a payment for this order already came in through bunq. Check the order notes.'), Environment::$adminErrors);
    }

    // --- Problems -------------------------------------------------------------------------------------------

    public function testNothingIsSentWhenBunqIsNotConnected()
    {
        $this->registerGateway(new Gateway($this->bunq));

        $this->paymentLink->send(Environment::addOrder());

        $this->assertSame(array(), $this->bunq->calls);
        $this->assertSame(array('The bunq payment link was not sent: connect your bunq account in the bunq payment settings first.'), Environment::$adminErrors);
    }

    public function testNothingIsSentForAnOrderThatDoesNotNeedPayment()
    {
        $this->connectedGateway();

        $this->paymentLink->send(Environment::addOrder(array('status' => 'completed')));

        $this->assertSame(array(), $this->bunq->callsTo('create_payment_request'));
        $this->assertSame(array('The bunq payment link was not sent: only unpaid orders in euros can be paid with a bunq payment link.'), Environment::$adminErrors);
    }

    public function testNothingIsSentWithoutABillingEmailAddress()
    {
        $this->connectedGateway();

        $this->paymentLink->send(Environment::addOrder(array('billing_email' => '')));

        $this->assertSame(array(), $this->bunq->callsTo('create_payment_request'));
        $this->assertSame(array('The bunq payment link was not sent: the order has no billing email address.'), Environment::$adminErrors);
    }

    public function testNothingIsSentWhileTheEmailIsDisabled()
    {
        $this->connectedGateway();
        update_option('woocommerce_customer_bunq_payment_link_settings', array('enabled' => 'no'));

        $this->paymentLink->send(Environment::addOrder());

        $this->assertSame(array(), $this->bunq->callsTo('create_payment_request'));
        $this->assertSame(array(), Environment::$sentEmails);
        $this->assertSame(array('The bunq payment link was not sent: the "bunq payment link" email is disabled in WooCommerce > Settings > Emails.'), Environment::$adminErrors);
    }

    public function testABunqFailureIsShownAndLogged()
    {
        $this->connectedGateway();
        $order = Environment::addOrder(array('payment_method' => 'bacs'));
        $this->bunq->exceptions['create_payment_request'] = new \RuntimeException('Monetary account not found');

        $this->paymentLink->send($order);

        $this->assertLogged('error', 'RuntimeException: Monetary account not found');
        $this->assertSame(array('The bunq payment link could not be created. The WooCommerce logs (source: bunq) have the details.'), Environment::$adminErrors);
        $this->assertSame(array(), Environment::$sentEmails);
        $this->assertSame('bacs', $order->get_payment_method());
    }

    public function testAnEmailThatCouldNotBeSentIsShown()
    {
        $this->connectedGateway();
        Environment::$mailWorks = false;
        $order = Environment::addOrder();

        $this->paymentLink->send($order);

        $this->assertSame(array('The bunq payment link could not be emailed to the customer. Check that your site can send email.'), Environment::$adminErrors);
        $this->assertNotContains('bunq payment link sent to jane@example.com', $order->notes);
    }

    // --- The email ------------------------------------------------------------------------------------------

    public function testThePlainTextEmailHasTheLink()
    {
        $this->connectedGateway();
        $order = Environment::addOrder(array('total' => '24.95'));
        $email = WC()->mailer()->get_emails()['WC_Email_Bunq_Payment_Link'];

        $email->object = $order;
        $email->payment_url = 'https://bunq.me/shop/new';
        $content = $email->get_content_plain();

        $this->assertStringContainsString('https://bunq.me/shop/new', $content);
        $this->assertStringContainsString('You can pay for order #' . $order->get_order_number() . ' of €24.95 with the link below.', $content);
    }

    private function connectedGateway()
    {
        update_option('woocommerce_bunq_settings', $this->liveSettings(array('api_context' => '{"live":1}')));
        $gateway = new Gateway($this->bunq);
        $this->registerGateway($gateway);

        return $gateway;
    }

    private function orderWithPaymentRequest($total)
    {
        return Environment::addOrder(array('total' => $total, 'meta' => array('bunq_payment_request_id' => 55)));
    }

    private function activePaymentRequest($id, $value)
    {
        return array_merge(FakeBunqApi::paymentRequest($id, 'ACTIVE'), array(
            'bunqme_tab_share_url' => 'https://bunq.me/shop/existing',
            'bunqme_tab_entry' => array('amount_inquired' => array('value' => $value, 'currency' => 'EUR')),
        ));
    }
}
