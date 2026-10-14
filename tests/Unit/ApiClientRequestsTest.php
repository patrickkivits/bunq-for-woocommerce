<?php

namespace BunqTest;

use bunq\Model\Generated\Endpoint\MonetaryAccountBankApiObject;
use bunq\Model\Generated\Endpoint\MonetaryAccountJointApiObject;
use bunq\Model\Generated\Endpoint\NotificationFilterUrlMonetaryAccountApiObject;
use bunq\Model\Generated\Object\DraftPaymentEntryObject;
use bunq\Model\Generated\Object\NotificationFilterUrlObject;
use bunq\Model\Generated\Object\PointerObject;
use PatrickKivits\BunqForWooCommerce\Bunq\ApiClient;

/**
 * API client whose lowest-level bunq calls are replaced, so what it builds from bunq's answers and what it would
 * post to bunq can be checked.
 */
final class RecordingApiClient extends ApiClient
{
    public $bankAccounts = array();
    public $jointAccounts = array();
    public $notificationFilters = array();
    public $exception = null;
    public $notificationFiltersException = null;
    public $canDecodeResponses = true;

    /** @var array[] The posted draft payments as array(entry, monetary account id). */
    public $draftPayments = array();
    /** @var array[] The posted notification filter lists as array(monetary account id, filters). */
    public $savedNotificationFilters = array();

    public function can_decode_responses()
    {
        return $this->canDecodeResponses;
    }

    public function get_notification_filters($monetary_account_bank_id = null)
    {
        if ($this->notificationFiltersException) {
            throw $this->notificationFiltersException;
        }

        return $this->notificationFilters;
    }

    protected function list_bank_monetary_accounts()
    {
        if ($this->exception) {
            throw $this->exception;
        }

        return $this->bankAccounts;
    }

    protected function list_joint_monetary_accounts()
    {
        return $this->jointAccounts;
    }

    protected function create_draft_payment(DraftPaymentEntryObject $entry, $monetary_account_bank_id)
    {
        $this->draftPayments[] = array($entry, $monetary_account_bank_id);

        return 9001;
    }

    protected function save_notification_filters($monetary_account_bank_id, array $notification_filters)
    {
        $this->savedNotificationFilters[] = array($monetary_account_bank_id, $notification_filters);
    }
}

/**
 * What the API client builds from bunq's answers and what it sends to bunq.
 */
final class ApiClientRequestsTest extends TestCase
{
    /** @var RecordingApiClient */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new RecordingApiClient();
    }

    public function testTheBankAccountsAreListedByIbanWithJointAccountsMarked()
    {
        $this->client->bankAccounts = array(
            $this->account(MonetaryAccountBankApiObject::class, 11, 'Shop', array(array('EMAIL', 'shop@example.com'), array('IBAN', 'NL11BUNQ0000000011'))),
            $this->account(MonetaryAccountBankApiObject::class, 12, 'Savings', array(array('IBAN', 'NL12BUNQ0000000012'))),
        );
        $this->client->jointAccounts = array(
            $this->account(MonetaryAccountJointApiObject::class, 13, 'Household', array(array('IBAN', 'NL13BUNQ0000000013'))),
        );

        $this->assertSame(array(
            '' => 'Select a bank account',
            11 => 'NL11BUNQ0000000011 - Shop',
            12 => 'NL12BUNQ0000000012 - Savings',
            13 => 'NL13BUNQ0000000013 - Household (Joint)',
        ), $this->client->get_bank_accounts(true));
    }

    public function testOnAServerWhoseOpcacheStripsDocCommentsTheSelectExplainsTheHostSetting()
    {
        // The accounts would fail with "Call to a member function getType() on array".
        $this->client->canDecodeResponses = false;
        $this->client->exception = new \RuntimeException('Must not be listed');

        $options = $this->client->get_bank_accounts(true);

        $this->assertSame(array(''), array_keys($options));
        $this->assertStringContainsString('Ask your hosting provider to set opcache.save_comments=1', $options['']);
    }

    public function testAFailedBankAccountLookupExplainsWhyInTheSelect()
    {
        $this->client->exception = new \RuntimeException('Incorrect API key or IP address');

        $this->assertSame(array('' => 'Error: Incorrect API key or IP address'), $this->client->get_bank_accounts(true));
        $this->assertLogged('error', 'RuntimeException: Incorrect API key or IP address');
    }

    public function testARefundIsADraftPaymentWithAnAmountInCentsAndASepaSafeDescription()
    {
        $id = $this->client->create_refund(12.5, 'EUR', 'NL12BUNQ0123456789', 'J. Doe', 'Refund order #123 Café ☕ Shop: broken & late', 456);

        $this->assertSame(9001, $id);
        $this->assertCount(1, $this->client->draftPayments);
        list($entry, $account) = $this->client->draftPayments[0];
        $this->assertSame(456, $account);
        $this->assertSame(array(
            'amount' => array('value' => '12.50', 'currency' => 'EUR'),
            'counterparty_alias' => array('type' => 'IBAN', 'value' => 'NL12BUNQ0123456789', 'name' => 'J. Doe'),
            'description' => 'Refund order 123 Café Shop: broken late',
        ), json_decode(json_encode($entry), true));
    }

    public function testARefundDescriptionIsCutToTheLengthBunqAllows()
    {
        $this->client->create_refund('5', 'EUR', 'NL12BUNQ0123456789', 'J. Doe', 'Refund ' . str_repeat('é', 200));

        $posted = json_decode(json_encode($this->client->draftPayments[0][0]), true);
        $this->assertSame('5.00', $posted['amount']['value']);
        $this->assertSame(140, mb_strlen($posted['description']), 'Characters, not bytes');
        $this->assertNull($this->client->draftPayments[0][1], 'The default account');
    }

    public function testTheCallbackIsAddedToTheNotificationFiltersOfOtherIntegrations()
    {
        $this->client->notificationFilters = array($this->filterList(array(
            $this->filter('PAYMENT', 'https://bookkeeping.example/bunq', 99),
            $this->filter('BUNQME_TAB', 'https://shop.example/wc-api/wc_bunq_gateway/', 100),
            $this->filter('BUNQME_TAB', 'https://other-shop.example/wc-api/wc_bunq_gateway/', 101),
        )));

        $this->client->create_notification_filters(123);

        $this->assertCount(1, $this->client->savedNotificationFilters);
        list($account, $filters) = $this->client->savedNotificationFilters[0];
        $this->assertSame(123, $account);
        $this->assertSame(array(
            array('category' => 'PAYMENT', 'notification_target' => 'https://bookkeeping.example/bunq'),
            array('category' => 'BUNQME_TAB', 'notification_target' => 'https://other-shop.example/wc-api/wc_bunq_gateway/'),
            array('category' => 'BUNQME_TAB', 'notification_target' => 'https://shop.example/wc-api/wc_bunq_gateway/'),
        ), json_decode(json_encode($filters), true), 'Ours once, at the end; the ids bunq returned are not posted back');
    }

    public function testTheCallbackIsRegisteredEvenWhenTheExistingFiltersCannotBeRead()
    {
        $this->client->notificationFiltersException = new \RuntimeException('Forbidden');

        $this->client->create_notification_filters();

        $this->assertSame(
            array(array('category' => 'BUNQME_TAB', 'notification_target' => 'https://shop.example/wc-api/wc_bunq_gateway/')),
            json_decode(json_encode($this->client->savedNotificationFilters[0][1]), true)
        );
        $this->assertLogged('warning', 'Could not read the existing bunq notification filters, registering only the WooCommerce callback: Forbidden');
    }

    private function account($class, $id, $description, array $aliases)
    {
        $account = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        $account->setId($id);
        $account->setDescription($description);
        $account->setAlias(array_map(function ($alias) {
            $pointer = (new \ReflectionClass(PointerObject::class))->newInstanceWithoutConstructor();
            $pointer->setType($alias[0]);
            $pointer->setValue($alias[1]);

            return $pointer;
        }, $aliases));

        return $account;
    }

    private function filter($category, $target, $id)
    {
        $filter = (new \ReflectionClass(NotificationFilterUrlObject::class))->newInstanceWithoutConstructor();
        $filter->setId($id);
        $filter->setCategory($category);
        $filter->setNotificationTarget($target);

        return $filter;
    }

    private function filterList(array $filters)
    {
        $list = (new \ReflectionClass(NotificationFilterUrlMonetaryAccountApiObject::class))->newInstanceWithoutConstructor();
        $list->setNotificationFilters($filters);

        return $list;
    }
}
