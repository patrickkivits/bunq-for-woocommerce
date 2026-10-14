<?php

namespace BunqTest;

use PatrickKivits\BunqForWooCommerce\Bunq\ApiClient;

/**
 * What the API client sends to bunq's endpoints and what it makes of bunq's answers.
 */
final class ApiClientRequestsTest extends TestCase
{
    /** @var FakeBunqServer */
    private $server;

    /** @var ApiClient */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->server = new FakeBunqServer();
        $this->client = $this->server->apiClient();
        $this->client->load_api_context_from_json(FakeBunqServer::context());
    }

    // --- Payment requests -----------------------------------------------------------------------------------

    public function testAPaymentRequestIsABunqMeTabWhoseShareUrlTheCustomerIsSentTo()
    {
        $this->server
            ->respond(array(array('Id' => array('id' => 55))))
            ->respond(array(array('BunqMeTab' => array('id' => 55, 'status' => 'WAITING_FOR_PAYMENT', 'bunqme_tab_share_url' => 'https://bunq.me/t/abc', 'result_inquiries' => array()))));

        $payment_request = $this->client->create_payment_request('24.95', 'EUR', '#1001', 'https://shop.example/return', 456);

        $this->assertSame(array('url' => 'https://bunq.me/t/abc', 'id' => 55), $payment_request);
        $this->assertSame(array('POST', 'GET'), array_column($this->server->requests, 'method'));
        $this->assertSame(array('user/7/monetary-account/456/bunqme-tab', 'user/7/monetary-account/456/bunqme-tab/55'), array_column($this->server->requests, 'path'));
        $this->assertSame(array('bunqme_tab_entry' => array(
            'amount_inquired' => array('value' => '24.95', 'currency' => 'EUR'),
            'description' => '#1001',
            'redirect_url' => 'https://shop.example/return',
        )), $this->server->body(0));
    }

    public function testWithoutAnAccountTheFirstActiveBankAccountIsUsedAndRemembered()
    {
        $this->server
            ->respond(array(
                array('MonetaryAccountBank' => array('id' => 11, 'status' => 'CANCELLED')),
                array('MonetaryAccountBank' => array('id' => 12, 'status' => 'ACTIVE')),
                array('MonetaryAccountBank' => array('id' => 13, 'status' => 'ACTIVE')),
            ))
            ->respond(array(array('Payment' => array('id' => 1))))
            ->respond(array(array('Id' => array('id' => 55))));

        $this->client->get_payment(1);
        $this->client->cancel_payment_request(55);

        $this->assertSame(array(
            'user/7/monetary-account-bank?count=200',
            'user/7/monetary-account/12/payment/1',
            'user/7/monetary-account/12/bunqme-tab/55',
        ), array_column($this->server->requests, 'path'));
    }

    public function testThePaymentsOfAPaymentRequestAreUnwrapped()
    {
        $payment = array('id' => 777, 'monetary_account_id' => 123, 'amount' => array('currency' => 'EUR', 'value' => '24.95'));
        $this->server->respond(array(array('BunqMeTab' => array('id' => 55, 'status' => 'WAITING_FOR_PAYMENT', 'result_inquiries' => array(
            array('payment' => array('Payment' => $payment), 'bunq_me_tab_id' => 55),
            array('payment' => $payment, 'bunq_me_tab_id' => 55),
        )))));

        $payment_request = $this->client->get_payment_request(55, 123);

        $this->assertSame('WAITING_FOR_PAYMENT', $payment_request['status']);
        $this->assertSame(array($payment, $payment), array_column($payment_request['result_inquiries'], 'payment'));
    }

    public function testCancellingAPaymentRequestSetsItsStatus()
    {
        $this->server->respond(array(array('Id' => array('id' => 55))));

        $this->client->cancel_payment_request(55, 123);

        $this->assertSame('PUT', $this->server->requests[0]['method']);
        $this->assertSame('user/7/monetary-account/123/bunqme-tab/55', $this->server->requests[0]['path']);
        $this->assertSame(array('status' => 'CANCELLED'), $this->server->body(0));
    }

    // --- Refunds --------------------------------------------------------------------------------------------

    public function testARefundIsADraftPaymentWithAnAmountInCentsAndASepaSafeDescription()
    {
        $this->server->respond(array(array('Id' => array('id' => 9001))));

        $id = $this->client->create_refund(12.5, 'EUR', 'NL12BUNQ0123456789', 'J. Doe', 'Refund order #123 Café ☕ Shop: broken & late', 456);

        $this->assertSame(9001, $id);
        $this->assertSame('user/7/monetary-account/456/draft-payment', $this->server->requests[0]['path']);
        $this->assertSame(array(
            'entries' => array(array(
                'amount' => array('value' => '12.50', 'currency' => 'EUR'),
                'counterparty_alias' => array('type' => 'IBAN', 'value' => 'NL12BUNQ0123456789', 'name' => 'J. Doe'),
                'description' => 'Refund order 123 Café Shop: broken late',
            )),
            'number_of_required_accepts' => 1,
        ), $this->server->body(0));
    }

    public function testARefundDescriptionIsCutToTheLengthBunqAllows()
    {
        $this->server->respond(array(array('Id' => array('id' => 9001))));

        $this->client->create_refund('5', 'EUR', 'NL12BUNQ0123456789', 'J. Doe', 'Refund ' . str_repeat('é', 200), 456);

        $entry = $this->server->body(0)['entries'][0];
        $this->assertSame('5.00', $entry['amount']['value']);
        $this->assertSame(140, mb_strlen($entry['description']), 'Characters, not bytes');
    }

    // --- Bank accounts --------------------------------------------------------------------------------------

    public function testTheBankAccountsAreListedByIbanWithJointAccountsMarked()
    {
        $this->server
            ->respond(array(
                $this->account('MonetaryAccountBank', 11, 'Shop', array(array('EMAIL', 'shop@example.com'), array('IBAN', 'NL11BUNQ0000000011'))),
                $this->account('MonetaryAccountBank', 12, 'Savings', array(array('IBAN', 'NL12BUNQ0000000012'))),
            ))
            ->respond(array(
                $this->account('MonetaryAccountJoint', 13, 'Household', array(array('IBAN', 'NL13BUNQ0000000013'))),
            ));

        $this->assertSame(array(
            '' => 'Select a bank account',
            11 => 'NL11BUNQ0000000011 - Shop',
            12 => 'NL12BUNQ0000000012 - Savings',
            13 => 'NL13BUNQ0000000013 - Household (Joint)',
        ), $this->client->get_bank_accounts(true));
        $this->assertSame(array('user/7/monetary-account-bank?count=200', 'user/7/monetary-account-joint?count=200'), array_column($this->server->requests, 'path'));
    }

    public function testAFailedBankAccountLookupExplainsWhyInTheSelect()
    {
        $this->server->respondWithError(400, 'User credentials are incorrect. Incorrect API key or IP address.');

        $this->assertSame(
            array('' => 'Error: User credentials are incorrect. Incorrect API key or IP address. (HTTP 400, response id response-id)'),
            $this->client->get_bank_accounts(true)
        );
        $this->assertLogged('error', 'ApiException: User credentials are incorrect.');
    }

    public function testBankAccountsWithoutAnApiContextExplainTheSetupIsIncomplete()
    {
        $this->assertSame(array('' => 'Connect your bunq account first'), $this->client->get_bank_accounts(false));
        $this->assertSame(array('' => 'Connect your bunq account first'), $this->client->get_bank_accounts(''));
        $this->assertSame(array(), $this->server->requests);
        $this->assertSame(array(), Environment::$logs);
    }

    // --- The callback URL -----------------------------------------------------------------------------------

    public function testTheCallbackIsAddedToTheNotificationFiltersOfOtherIntegrations()
    {
        // bunq lists one NotificationFilterUrl per filter, with response fields that must not be posted back.
        $this->server
            ->respond(array(
                $this->filter('PAYMENT', 'https://bookkeeping.example/bunq', 99),
                $this->filter('BUNQME_TAB', 'https://shop.example/wc-api/wc_bunq_gateway/', 100),
                $this->filter('BUNQME_TAB', 'https://other-shop.example/wc-api/wc_bunq_gateway/', 101),
            ))
            ->respond(array());

        $this->client->create_notification_filters(123);

        $this->assertSame(array('GET', 'POST'), array_column($this->server->requests, 'method'));
        $this->assertSame('user/7/monetary-account/123/notification-filter-url?count=200', $this->server->requests[0]['path'], 'All filters, not the first page of 10');
        $this->assertSame('user/7/monetary-account/123/notification-filter-url', $this->server->requests[1]['path']);
        $this->assertSame(array('notification_filters' => array(
            array('category' => 'PAYMENT', 'all_monetary_account_id' => array(123), 'notification_target' => 'https://bookkeeping.example/bunq'),
            array('category' => 'BUNQME_TAB', 'all_monetary_account_id' => array(123), 'notification_target' => 'https://other-shop.example/wc-api/wc_bunq_gateway/'),
            array('category' => 'BUNQME_TAB', 'notification_target' => 'https://shop.example/wc-api/wc_bunq_gateway/'),
        )), $this->server->body(1), 'Ours once, at the end');
    }

    public function testTheCallbackIsRegisteredEvenWhenTheExistingFiltersCannotBeRead()
    {
        $this->server->respondWithError(403, 'Forbidden')->respond(array());

        $this->client->create_notification_filters(123);

        $this->assertSame(
            array('notification_filters' => array(array('category' => 'BUNQME_TAB', 'notification_target' => 'https://shop.example/wc-api/wc_bunq_gateway/'))),
            $this->server->body(1)
        );
        $this->assertLogged('warning', 'Could not read the existing bunq notification filters, registering only the WooCommerce callback: Forbidden');
    }

    private function account($type, $id, $description, array $aliases)
    {
        return array($type => array(
            'id' => $id,
            'description' => $description,
            'status' => 'ACTIVE',
            'alias' => array_map(function ($alias) {
                return array('type' => $alias[0], 'value' => $alias[1], 'name' => 'Shop B.V.');
            }, $aliases),
        ));
    }

    private function filter($category, $target, $id)
    {
        return array('NotificationFilterUrl' => array(
            'id' => $id,
            'created' => '2026-10-14 20:53:56.022634',
            'category' => $category,
            'all_user_id' => array(),
            'all_monetary_account_id' => array(123),
            'all_verification_type' => array(),
            'notification_target' => $target,
        ));
    }
}
