<?php

namespace BunqTest;

use PatrickKivits\BunqForWooCommerce\Bunq\ApiException;
use WC_Bunq_Gateway;

/**
 * The parts of the gateway that talk to bunq, against a fake bunq: starting a payment, matching what bunq reports
 * back to the order, refunds and the upkeep of the API context.
 */
final class BunqPaymentFlowTest extends TestCase
{
    /** @var FakeBunqApi */
    private $bunq;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bunq = new FakeBunqApi();
    }

    // --- Starting a payment ---------------------------------------------------------------------------------

    public function testAPaymentRequestIsCreatedForTheOrderAndTheCustomerIsSentToBunqMe()
    {
        $gateway = $this->authorizedGateway();
        $order = Environment::addOrder(array('total' => '24.95', 'currency' => 'EUR'));

        $result = $gateway->process_payment($order->get_id());

        $this->assertSame(array('result' => 'success', 'redirect' => 'https://bunq.me/shop/24.95/1'), $result);
        $this->assertSame(array(array(
            '24.95',
            'EUR',
            '#' . $order->get_order_number(),
            'https://shop.example/checkout/order-received/' . $order->get_id() . '/?key=' . $order->get_order_key(),
            123,
        )), $this->bunq->callsTo('create_payment_request'));
        $this->assertSame(55, $order->get_meta('bunq_payment_request_id'));
        $this->assertSame(array('bunq payment request 55 created'), $order->notes);
        $this->assertSame(1, $order->save_count);
        $this->assertSame(array(), Environment::$notices);
    }

    public function testAPaymentQueuesTheFirstBackgroundCheck()
    {
        $gateway = $this->authorizedGateway();
        $order = Environment::addOrder();

        $gateway->process_payment($order->get_id());

        $this->assertCount(1, Environment::$scheduledActions);
        $this->assertSame('wc_bunq_check_payment', Environment::$scheduledActions[0]['hook']);
        $this->assertSame(array($order->get_id(), 0), Environment::$scheduledActions[0]['args']);
        $this->assertSame('bunq', Environment::$scheduledActions[0]['group']);
        $this->assertEqualsWithDelta(time() + 10 * MINUTE_IN_SECONDS, Environment::$scheduledActions[0]['timestamp'], 2);
    }

    public function testWithoutASelectedBankAccountBunqUsesTheDefaultAccount()
    {
        $gateway = $this->authorizedGateway(array('monetary_account_bank_id' => ''));

        $gateway->process_payment(Environment::addOrder()->get_id());

        $this->assertNull($this->bunq->callsTo('create_payment_request')[0][4]);
    }

    /**
     * @dataProvider chosenPaymentMethods
     */
    public function testTheChosenPaymentMethodIsOpenedDirectlyOnBunqMe($posted, $expected_redirect)
    {
        $gateway = $this->authorizedGateway(array('direct_gateway' => 'yes'));
        $_POST['wc_bunq_gateway_payment_method'] = $posted;

        $result = $gateway->process_payment(Environment::addOrder()->get_id());

        $this->assertSame('success', $result['result']);
        $this->assertSame($expected_redirect, $result['redirect']);
    }

    public function chosenPaymentMethods()
    {
        return array(
            'card' => array('card', 'https://bunq.me/shop/24.95/1?paymentMethod=CARD'),
            'iDEAL' => array('ideal', 'https://bunq.me/shop/24.95/1?paymentMethod=IDEAL'),
            'Bancontact' => array('bancontact', 'https://bunq.me/shop/24.95/1?paymentMethod=BANCONTACT'),
            'bunq transfer' => array('bunq-transfer', 'https://bunq.me/shop/24.95/1?paymentMethod=BUNQ_TRANSFER'),
            'unknown method: bunq.me offers all of them' => array('paypal', 'https://bunq.me/shop/24.95/1'),
            'slashed input is unslashed first' => array('ide\\al', 'https://bunq.me/shop/24.95/1?paymentMethod=IDEAL'),
        );
    }

    public function testAPaymentFailsWhenBunqDoesNotReturnAPaymentRequest()
    {
        $gateway = $this->authorizedGateway();
        $order = Environment::addOrder();
        $this->bunq->createdPaymentRequest = array('id' => 55, 'url' => '');

        $this->assertSame(array('result' => 'failure'), $gateway->process_payment($order->get_id()));

        $this->assertLogged('error', 'bunq did not return a payment request for order #' . $order->get_order_number());
        $this->assertSame('The payment could not be started with bunq. Please try again or choose another payment method.', Environment::$notices[0]['message']);
        $this->assertSame('', $order->get_meta('bunq_payment_request_id'));
        $this->assertSame(array(), Environment::$scheduledActions);
    }

    public function testAPaymentFailsGracefullyWhenBunqRejectsTheRequest()
    {
        $gateway = $this->authorizedGateway();
        $order = Environment::addOrder();
        $this->bunq->exceptions['create_payment_request'] = new \RuntimeException('Monetary account not found');

        $this->assertSame(array('result' => 'failure'), $gateway->process_payment($order->get_id()));

        $this->assertLogged('error', 'RuntimeException: Monetary account not found');
        $this->assertSame(array(array(
            'type' => 'error',
            'message' => 'The payment could not be started with bunq. Please try again or choose another payment method.',
        )), Environment::$notices, 'The customer does not see bunq\'s error');
        $this->assertSame(array(), $order->notes);
    }

    // --- Matching what bunq reports to the order ------------------------------------------------------------

    public function testAMatchingPaymentCompletesTheOrder()
    {
        $gateway = $this->authorizedGateway();
        $order = $this->unpaidOrder('24.95');
        $this->bunqReports('ACTIVE', array(FakeBunqApi::payment(777, '24.95', 'EUR', 456)));

        $this->assertSame('paid', $gateway->check_payment_status($order));

        $this->assertSame(array(array(55, 123)), $this->bunq->callsTo('get_payment_request'));
        $this->assertSame('processing', $order->get_status());
        $this->assertSame('777', $order->get_transaction_id());
        $this->assertSame(777, $order->get_meta('bunq_payment_id'));
        $this->assertSame(456, $order->get_meta('bunq_monetary_account_id'), 'The account the money landed on, for refunds');
        $this->assertSame(array('bunq payment 777 received'), $order->notes);
        $this->assertLogged('info', 'bunqme-tab 55: payment 777 received for order #' . $order->get_order_number());
        $this->assertFalse(get_transient('wc_bunq_gateway.lock.' . $order->get_id()), 'The lock is released');
    }

    /**
     * @dataProvider matchingAmounts
     */
    public function testAmountsAreComparedAsDecimals($paid, $total)
    {
        $gateway = $this->authorizedGateway();
        $order = $this->unpaidOrder($total);
        $this->bunqReports('ACTIVE', array(FakeBunqApi::payment(777, $paid)));

        $this->assertSame('paid', $gateway->check_payment_status($order));
    }

    public function matchingAmounts()
    {
        return array(
            'same' => array('10.00', '10.00'),
            'shop without decimals' => array('10.00', '10'),
            'shop with three decimals' => array('10.50', '10.500'),
        );
    }

    /**
     * @dataProvider mismatchingPayments
     */
    public function testAPaymentThatDoesNotMatchPutsTheOrderOnHold($value, $currency, $expected_note)
    {
        $gateway = $this->authorizedGateway();
        $order = $this->unpaidOrder('10.00');
        $this->bunqReports('ACTIVE', array(FakeBunqApi::payment(777, $value, $currency, 456)));

        $this->assertSame('on-hold', $gateway->check_payment_status($order));

        $this->assertSame('on-hold', $order->get_status());
        $this->assertSame('', $order->get_transaction_id(), 'Not marked as paid');
        $this->assertSame(777, $order->get_meta('bunq_payment_id'));
        $this->assertSame(456, $order->get_meta('bunq_monetary_account_id'));
        $this->assertSame(array($expected_note), $order->notes);
        $this->assertLogged('warning', 'bunqme-tab 55: payment 777 of ' . $currency . ' ' . $value . ' does not match order #' . $order->get_order_number() . ' total EUR 10.00');
    }

    public function mismatchingPayments()
    {
        return array(
            'too little' => array('9.99', 'EUR', 'bunq payment 777 of EUR 9.99 received, but the order total is EUR 10.00. Check the payment before processing the order.'),
            'too much' => array('10.01', 'EUR', 'bunq payment 777 of EUR 10.01 received, but the order total is EUR 10.00. Check the payment before processing the order.'),
            'other currency' => array('10.00', 'USD', 'bunq payment 777 of USD 10.00 received, but the order total is EUR 10.00. Check the payment before processing the order.'),
        );
    }

    public function testAMatchingPaymentWinsOverOneThatDoesNotMatch()
    {
        $gateway = $this->authorizedGateway();
        $order = $this->unpaidOrder('10.00');
        $this->bunqReports('ACTIVE', array(FakeBunqApi::payment(776, '5.00'), FakeBunqApi::payment(777, '10.00')));

        $this->assertSame('paid', $gateway->check_payment_status($order));
        $this->assertSame(777, $order->get_meta('bunq_payment_id'));
        $this->assertSame('777', $order->get_transaction_id());
    }

    /**
     * @dataProvider closedStatuses
     */
    public function testARequestBunqClosedCancelsTheOrderWithoutCancellingItAtBunqAgain($status, $expected_note)
    {
        $gateway = $this->authorizedGateway();
        $order = $this->unpaidOrder('10.00');
        $this->bunqReports($status);

        $this->assertSame('cancelled', $gateway->check_payment_status($order));

        $this->assertSame('cancelled', $order->get_status());
        $this->assertSame($status, $order->get_meta('bunq_payment_request_status'));
        $this->assertSame(array($expected_note), $order->notes);
        $this->assertSame(array(), Environment::$asyncActions, 'The cancelled-order hook leaves the closed request alone');
        $this->assertLogged('info', 'bunqme-tab 55 is ' . $status . ', cancelling order #' . $order->get_order_number());
    }

    public function closedStatuses()
    {
        return array(
            'expired' => array('EXPIRED', 'bunq payment request 55 is expired'),
            'cancelled' => array('CANCELLED', 'bunq payment request 55 is cancelled'),
        );
    }

    public function testAnOpenRequestWithoutPaymentsLeavesTheOrderPending()
    {
        $gateway = $this->authorizedGateway();
        $order = $this->unpaidOrder('10.00');
        $this->bunqReports('ACTIVE', array(null));

        $this->assertSame('pending', $gateway->check_payment_status($order));

        $this->assertSame('pending', $order->get_status());
        $this->assertSame(array(), $order->notes);
        $this->assertSame(0, $order->save_count);
        $this->assertFalse(get_transient('wc_bunq_gateway.lock.' . $order->get_id()));
    }

    public function testAFailedLookupLeavesTheOrderAloneAndReleasesTheLock()
    {
        $gateway = $this->authorizedGateway();
        $order = $this->unpaidOrder('10.00');
        $this->bunq->exceptions['get_payment_request'] = new \RuntimeException('bunq is down');

        try {
            $gateway->check_payment_status($order);
            $this->fail('The bunq error should have been rethrown');
        } catch (\RuntimeException $exception) {
            $this->assertSame('bunq is down', $exception->getMessage());
        }

        $this->assertSame('pending', $order->get_status());
        $this->assertFalse(get_transient('wc_bunq_gateway.lock.' . $order->get_id()));
    }

    public function testAScheduledCheckCompletesAPaidOrderAndStopsChecking()
    {
        $gateway = $this->authorizedGateway();
        $this->registerGateway($gateway);
        $order = $this->unpaidOrder('10.00');
        $this->bunqReports('ACTIVE', array(FakeBunqApi::payment(777, '10.00')));

        do_action('wc_bunq_check_payment', $order->get_id(), 2);

        $this->assertSame('processing', $order->get_status());
        $this->assertSame(array(), Environment::$scheduledActions);
        $this->assertLogged('debug', 'Scheduled check 3 for order #' . $order->get_order_number() . ': paid');
    }

    public function testAQueuedCancellationCancelsThePaymentRequestAtBunq()
    {
        $gateway = $this->authorizedGateway();
        $this->registerGateway($gateway);
        $order = $this->unpaidOrder('10.00');

        $order->update_status('cancelled', 'Unpaid order cancelled');
        $this->assertCount(1, Environment::$asyncActions);
        do_action(Environment::$asyncActions[0]['hook'], ...Environment::$asyncActions[0]['args']);

        $this->assertSame(array(array(55, 123)), $this->bunq->callsTo('cancel_payment_request'));
        $this->assertSame('CANCELLED', $order->get_meta('bunq_payment_request_status'));
    }

    // --- Refunds --------------------------------------------------------------------------------------------

    public function testARefundIsCreatedAsADraftPaymentBackToThePayer()
    {
        $gateway = $this->authorizedGateway();
        $order = $this->paidOrder(array('bunq_monetary_account_id' => 456));
        $this->bunq->payments[777] = FakeBunqApi::payment(777, '24.95', 'EUR', 456, 'NL12BUNQ0123456789', 'J. Doe');

        $this->assertTrue($gateway->process_refund($order->get_id(), '12.50', 'Damaged'));

        $this->assertSame(array(array(777, 456)), $this->bunq->callsTo('get_payment'));
        $this->assertSame(array(array(
            12.5,
            'EUR',
            'NL12BUNQ0123456789',
            'J. Doe',
            'Refund order ' . $order->get_order_number() . ' Test Shop: Damaged',
            456,
        )), $this->bunq->callsTo('create_refund'));
        $this->assertSame(array('bunq refund of EUR 12.50 created as draft payment 9001. Approve it in the bunq app to send the money.'), $order->notes);
        $this->assertLogged('info', 'bunq draft payment 9001 of EUR 12.50 created for the refund of order #' . $order->get_order_number());
    }

    public function testARefundWithoutReasonOnlyNamesTheOrder()
    {
        $gateway = $this->authorizedGateway();
        $order = $this->paidOrder();
        $this->bunq->payments[777] = FakeBunqApi::payment(777, '24.95');

        $this->assertTrue($gateway->process_refund($order->get_id(), 5));

        $this->assertSame('Refund order ' . $order->get_order_number() . ' Test Shop', $this->bunq->callsTo('create_refund')[0][4]);
    }

    public function testARefundOfAnOlderOrderUsesTheSelectedBankAccount()
    {
        $gateway = $this->authorizedGateway();
        $order = $this->paidOrder();
        $this->bunq->payments[777] = FakeBunqApi::payment(777, '24.95');

        $gateway->process_refund($order->get_id(), 5);

        $this->assertSame(array(array(777, 123)), $this->bunq->callsTo('get_payment'));
        $this->assertSame(123, $this->bunq->callsTo('create_refund')[0][5]);
    }

    public function testARefundFallsBackToTheBillingNameWhenBunqHasNoName()
    {
        $gateway = $this->authorizedGateway();
        $order = $this->paidOrder();
        $this->bunq->payments[777] = FakeBunqApi::payment(777, '24.95', 'EUR', 123, 'NL12BUNQ0123456789', null);

        $gateway->process_refund($order->get_id(), 5);

        $this->assertSame('Jane Doe', $this->bunq->callsTo('create_refund')[0][3]);
    }

    public function testAPaymentWithoutIbanCannotBeRefundedThroughBunq()
    {
        $gateway = $this->authorizedGateway();
        $order = $this->paidOrder();
        $this->bunq->payments[777] = FakeBunqApi::payment(777, '24.95', 'EUR', 123, null);

        $error = $gateway->process_refund($order->get_id(), 5);

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertSame('bunq_refund', $error->get_error_code());
        $this->assertStringContainsString('has no IBAN to refund to (for example a card payment)', $error->get_error_message());
        $this->assertSame(array(), $this->bunq->callsTo('create_refund'));
        $this->assertSame(array(), $order->notes);
    }

    public function testARefundBunqRejectsIsReportedToTheMerchant()
    {
        $gateway = $this->authorizedGateway();
        $order = $this->paidOrder();
        $this->bunq->payments[777] = FakeBunqApi::payment(777, '24.95');
        $this->bunq->exceptions['create_refund'] = new \RuntimeException('Insufficient balance');

        $error = $gateway->process_refund($order->get_id(), 5);

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertSame('bunq refund failed: Insufficient balance', $error->get_error_message());
        $this->assertLogged('error', 'RuntimeException: Insufficient balance');
        $this->assertSame(array(), $order->notes);
    }

    // --- The API context ------------------------------------------------------------------------------------

    public function testARenewedSessionIsSavedToTheSettingsOfTheActiveMode()
    {
        $this->bunq->renewedContext = '{"renewed":1}';

        $live = $this->authorizedGateway();
        $this->assertTrue($live->load_api_context());
        $this->assertSame('{"renewed":1}', $this->credentials()['api_context']);

        $test = $this->authorizedGateway(array('testmode' => 'yes', 'test_api_context' => '{"test":1}'));
        $this->assertTrue($test->load_api_context());
        $this->assertSame('{"renewed":1}', $this->credentials()['test_api_context']);
        $this->assertSame('{"live":1}', $this->credentials()['api_context']);
    }

    public function testAnUnusableApiContextIsRecreatedFromTheSavedApiKey()
    {
        $gateway = $this->authorizedGateway(array('api_context' => 'unusable-api-context'));
        $order = Environment::addOrder();

        $result = $gateway->process_payment($order->get_id());

        $this->assertSame('success', $result['result']);
        $this->assertSame(array(array('live-api-key', false)), $this->bunq->callsTo('create_api_context'));
        $this->assertSame('{"created":1}', $this->credentials()['api_context']);
        $this->assertSame(array(), $this->bunq->callsTo('create_notification_filters'), 'The callback stays registered');
        $this->assertLogged('warning', 'bunq API context could not be loaded, recreating it from the saved API key');
        $this->assertLogged('info', 'bunq API context created for production');
    }

    public function testWhenBunqRefusesANewSessionTheContextIsRecreatedAndTheCallRepeated()
    {
        $gateway = $this->authorizedGateway();
        $order = $this->unpaidOrder('24.95');
        $this->bunqReports('WAITING_FOR_PAYMENT', array(FakeBunqApi::payment(777, '24.95')));
        $this->bunq->exceptions['get_payment_request'] = array($this->contextRejected());

        $this->assertSame('paid', $gateway->check_payment_status($order));

        $this->assertCount(2, $this->bunq->callsTo('get_payment_request'));
        $this->assertSame(array(array('live-api-key', false)), $this->bunq->callsTo('create_api_context'));
        $this->assertSame('{"created":1}', $this->credentials()['api_context']);
        $this->assertLogged('warning', 'bunq refused a new session (Incorrect API key or IP address.), recreating the API context from the saved API key');
    }

    public function testOtherBunqErrorsDoNotRecreateTheContext()
    {
        $gateway = $this->authorizedGateway();
        // An ended session the API client could not renew is not the same as a context bunq refuses.
        $this->bunq->exceptions['cancel_payment_request'] = new ApiException('Insufficient authentication.', 403);

        try {
            $gateway->cancel_payment_request(55);
            $this->fail('The bunq error should have been rethrown');
        } catch (ApiException $exception) {
            $this->assertSame(403, $exception->getCode());
        }

        $this->assertCount(1, $this->bunq->callsTo('cancel_payment_request'));
        $this->assertSame(array(), $this->bunq->callsTo('create_api_context'));
    }

    public function testCallsAreNotRepeatedOnceOneOfThemWentThrough()
    {
        $gateway = $this->authorizedGateway();
        $this->bunq->payments[777] = FakeBunqApi::payment(777, '24.95');
        $this->bunq->exceptions['cancel_payment_request'] = array($this->contextRejected());

        try {
            $gateway->call_bunq(function () {
                $this->bunq->get_payment(777, 123);
                $this->bunq->cancel_payment_request(55, 123);
            });
            $this->fail('The bunq error should have been rethrown');
        } catch (ApiException $exception) {
            $this->assertTrue($exception->is_context_rejected());
        }

        $this->assertCount(1, $this->bunq->callsTo('get_payment'), 'Not done twice');
        $this->assertSame(array(), $this->bunq->callsTo('create_api_context'));
    }

    public function testAContextBunqKeepsRefusingIsRecreatedOnceAndReported()
    {
        $gateway = $this->authorizedGateway();
        $this->bunq->exceptions['get_payment'] = array($this->contextRejected(), $this->contextRejected(), $this->contextRejected());
        $order = $this->paidOrder();

        $error = $gateway->process_refund($order->get_id(), 5);

        $this->assertSame('bunq refund failed: Incorrect API key or IP address.', $error->get_error_message());
        $this->assertCount(2, $this->bunq->callsTo('get_payment'), 'Recreated once, not in a loop');
        $this->assertCount(1, $this->bunq->callsTo('create_api_context'));
    }

    public function testAfterAFailedRecreationTheNextRequestsDoNotTryAgain()
    {
        $gateway = $this->authorizedGateway();
        $this->bunq->exceptions['get_payment_request'] = $this->contextRejected();
        $this->bunq->exceptions['create_api_context'] = new \RuntimeException('Incorrect API key or IP address');

        foreach (array(1, 2, 3) as $attempt) {
            try {
                $gateway->check_payment_status($this->unpaidOrder('24.95'));
                $this->fail('The bunq error should have been rethrown');
            } catch (\RuntimeException $exception) {
                $this->assertSame($attempt === 1 ? 'Incorrect API key or IP address' : 'Incorrect API key or IP address.', $exception->getMessage());
            }
        }

        $this->assertCount(1, $this->bunq->callsTo('create_api_context'), 'Only until the failure is remembered');
        $this->assertSame(1, get_transient(WC_Bunq_Gateway::CONTEXT_FAILED_TRANSIENT));
    }

    public function testTheSettingsPageRecreatesAContextBunqRefuses()
    {
        Environment::$isAdmin = true;
        $this->bunq->exceptions['get_bank_accounts'] = array($this->contextRejected());

        $fields = $this->authorizedGateway()->get_form_fields();

        $this->assertSame($this->bunq->bankAccounts, $fields['monetary_account_bank_id']['options']);
        $this->assertCount(1, $this->bunq->callsTo('create_api_context'));
    }

    public function testTheSettingsPageSaysWhenTheContextCannotBeRecreated()
    {
        Environment::$isAdmin = true;
        $this->bunq->exceptions['get_bank_accounts'] = $this->contextRejected();
        $this->bunq->exceptions['create_api_context'] = new \RuntimeException('Incorrect API key or IP address');

        $fields = $this->authorizedGateway()->get_form_fields();

        $this->assertSame(array('' => 'bunq rejected the saved connection. Connect again.'), $fields['monetary_account_bank_id']['options']);
    }

    public function testAFailedRecreationIsRememberedAndRethrown()
    {
        $gateway = $this->authorizedGateway(array('api_context' => 'unusable-api-context'));
        $this->bunq->exceptions['create_api_context'] = new \RuntimeException('Incorrect API key or IP address');

        try {
            $gateway->ensure_api_context_loaded();
            $this->fail('The bunq error should have been rethrown');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Incorrect API key or IP address', $exception->getMessage());
        }

        $this->assertSame(1, get_transient(WC_Bunq_Gateway::CONTEXT_FAILED_TRANSIENT));
        $this->assertSame(5 * MINUTE_IN_SECONDS, Environment::$transientTtls[WC_Bunq_Gateway::CONTEXT_FAILED_TRANSIENT]);
        $this->assertSame('unusable-api-context', $this->credentials()['api_context']);
    }

    public function testCreatingTheApiContextRegistersTheCallbackUrlAtBunq()
    {
        $gateway = $this->authorizedGateway(array('testmode' => 'yes'));
        set_transient(WC_Bunq_Gateway::CONTEXT_FAILED_TRANSIENT, 1, 5 * MINUTE_IN_SECONDS);

        $gateway->refresh_api_context();

        $this->assertSame(array(array('test-api-key', true)), $this->bunq->callsTo('create_api_context'));
        $this->assertSame('{"created":1}', $this->credentials()['test_api_context']);
        $this->assertFalse(get_transient(WC_Bunq_Gateway::CONTEXT_FAILED_TRANSIENT), 'A working context clears the remembered failure');
        $this->assertSame(array(array(123)), $this->bunq->callsTo('create_notification_filters'));
        $this->assertLogged('info', 'bunq API context created for sandbox');
        $this->assertLogged('info', 'bunq callback URL registered: https://shop.example/wc-api/wc_bunq_gateway/');
    }

    public function testTheCallbackUrlOfALocalSiteIsNotRegistered()
    {
        $gateway = $this->authorizedGateway();
        WC()->home_url = 'http://shop.test';

        $gateway->refresh_api_context();

        $this->assertSame(array(), $this->bunq->callsTo('create_notification_filters'));
        $this->assertLogged('info', 'Skipped registering the bunq callback URL http://shop.test/wc-api/wc_bunq_gateway/ because bunq cannot reach it.');
    }

    public function testAFailedCallbackRegistrationDoesNotFailTheSetup()
    {
        $gateway = $this->authorizedGateway();
        $this->bunq->exceptions['create_notification_filters'] = new \RuntimeException('Not allowed');

        $gateway->refresh_api_context();

        $this->assertSame('{"created":1}', $this->credentials()['api_context']);
        $this->assertLogged('warning', 'RuntimeException: Not allowed');
    }

    public function testTheBankAccountsAreFetchedWithTheLoadedApiContextAndCachedForAnHour()
    {
        Environment::$isAdmin = true;

        $fields = $this->authorizedGateway()->get_form_fields();

        $this->assertSame(array(array(true)), $this->bunq->callsTo('get_bank_accounts'));
        $this->assertSame($this->bunq->bankAccounts, $fields['monetary_account_bank_id']['options']);
        $this->assertSame($this->bunq->bankAccounts, get_transient(WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT));
        $this->assertSame(HOUR_IN_SECONDS, Environment::$transientTtls[WC_Bunq_Gateway::BANK_ACCOUNTS_TRANSIENT]);
    }

    /**
     * A gateway for a shop that completed the bunq authorization, talking to the fake bunq.
     */
    private function authorizedGateway(array $settings = array())
    {
        update_option('woocommerce_bunq_settings', $this->liveSettings(array_merge(array('api_context' => '{"live":1}'), $settings)));

        return new WC_Bunq_Gateway($this->bunq);
    }

    /**
     * What the API client throws when bunq refuses a new session for the stored context.
     */
    private function contextRejected()
    {
        return new ApiException('Incorrect API key or IP address.', 400, '', true);
    }

    private function unpaidOrder($total)
    {
        return Environment::addOrder(array('total' => $total, 'meta' => array('bunq_payment_request_id' => 55)));
    }

    private function paidOrder(array $meta = array())
    {
        return Environment::addOrder(array(
            'status' => 'processing',
            'total' => '24.95',
            'meta' => array_merge(array('bunq_payment_request_id' => 55, 'bunq_payment_id' => 777), $meta),
        ));
    }

    private function bunqReports($status, array $payments = array())
    {
        $this->bunq->paymentRequests[55] = FakeBunqApi::paymentRequest(55, $status, $payments);
    }
}
