<?php

namespace BunqTest;

use bunq\Model\Generated\Endpoint\BunqMeTabApiObject;
use bunq\Model\Generated\Endpoint\BunqMeTabResultInquiryApiObject;
use bunq\Model\Generated\Endpoint\PaymentApiObject;
use bunq\Model\Generated\Object\AmountObject;
use bunq\Model\Generated\Object\LabelMonetaryAccountObject;

/**
 * Stands in for bunq: records every call the plugin makes and answers with the bunq SDK objects a test set up.
 * Shared by the unit and the integration suite.
 */
final class FakeBunqApi extends \PatrickKivits\BunqForWooCommerce\Bunq\ApiClient
{
    /** @var array[] Every call as array(method, argument, ...), oldest first. */
    public $calls = array();

    /** @var string[] Stored API contexts that can no longer be used (expired session, revoked key). */
    public $unusableContexts = array('', 'unusable-api-context');
    /** @var string|null The context JSON after loading, when bunq renewed the session; null keeps it unchanged. */
    public $renewedContext = null;
    /** @var string The context JSON a new API context serialises to. */
    public $createdContext = '{"created":1}';

    /** @var array What create_payment_request() returns. */
    public $createdPaymentRequest = array('id' => 55, 'url' => 'https://bunq.me/shop/24.95/1');
    /** @var BunqMeTabApiObject[] Payment requests by id. */
    public $paymentRequests = array();
    /** @var PaymentApiObject[] Payments by id. */
    public $payments = array();
    /** @var int What create_refund() returns. */
    public $draftPaymentId = 9001;
    /** @var array What get_bank_accounts() returns. */
    public $bankAccounts = array('' => 'Select a bank account', 123 => 'NL00BUNQ0000000123 - Shop');

    /** @var string|null What get_account_name() returns. */
    public $accountName = null;

    /** @var bool What can_decode_responses() returns: false on a server whose OPcache strips doc comments. */
    public $canDecodeResponses = true;

    /** @var \Throwable[] Thrown by the named method (create_payment_request, get_payment, ...) when set. */
    public $exceptions = array();

    public function load_api_context_from_json($json)
    {
        $this->record(__FUNCTION__, func_get_args());

        if (in_array($json, $this->unusableContexts, true)) {
            return false;
        }

        return $this->renewedContext !== null ? $this->renewedContext : $json;
    }

    public function can_decode_responses()
    {
        return $this->canDecodeResponses;
    }

    public function create_api_context($api_key, $testmode)
    {
        $this->record(__FUNCTION__, func_get_args());
        $json = $this->createdContext;

        return new class($json) {
            private $json;

            public function __construct($json)
            {
                $this->json = $json;
            }

            public function toJson()
            {
                return $this->json;
            }
        };
    }

    public function get_account_name($json)
    {
        return $this->accountName;
    }

    public function get_bank_accounts($api_context)
    {
        $this->record(__FUNCTION__, func_get_args());

        return $this->bankAccounts;
    }

    public function create_payment_request($amount, $currency, $description, $return_url, $monetary_account_bank_id = null)
    {
        $this->record(__FUNCTION__, func_get_args());

        return $this->createdPaymentRequest;
    }

    public function get_payment_request($payment_request_id, $monetary_account_bank_id = null)
    {
        $this->record(__FUNCTION__, func_get_args());

        return $this->paymentRequests[$payment_request_id];
    }

    public function cancel_payment_request($payment_request_id, $monetary_account_bank_id = null)
    {
        $this->record(__FUNCTION__, func_get_args());
    }

    public function get_payment($payment_id, $monetary_account_bank_id = null)
    {
        $this->record(__FUNCTION__, func_get_args());

        return $this->payments[$payment_id];
    }

    public function create_refund($amount, $currency, $iban, $name, $description, $monetary_account_bank_id = null)
    {
        $this->record(__FUNCTION__, func_get_args());

        return $this->draftPaymentId;
    }

    public function create_notification_filters($monetary_account_bank_id = null)
    {
        $this->record(__FUNCTION__, func_get_args());
    }

    /**
     * @return array[] The arguments of every call to the method, oldest first.
     */
    public function callsTo($method)
    {
        $calls = array();

        foreach ($this->calls as $call) {
            if ($call[0] === $method) {
                $calls[] = array_slice($call, 1);
            }
        }

        return $calls;
    }

    /**
     * A bunq.me payment request as bunq returns it.
     *
     * @param PaymentApiObject[] $payments Payments made through the request; null for an inquiry without a payment.
     */
    public static function paymentRequest($id, $status, array $payments = array())
    {
        $inquiries = array();

        foreach ($payments as $payment) {
            $inquiry = self::response(BunqMeTabResultInquiryApiObject::class);
            $inquiry->setPayment($payment);
            $inquiries[] = $inquiry;
        }

        $request = self::response(BunqMeTabApiObject::class);
        $request->setId($id);
        $request->setStatus($status);
        $request->setResultInquiries($inquiries);

        return $request;
    }

    /**
     * A payment received on a bunq account.
     *
     * @param string $value The amount as bunq formats it ("24.95").
     */
    public static function payment($id, $value, $currency = 'EUR', $monetary_account_id = 123, $iban = 'NL12BUNQ0123456789', $name = 'J. Doe')
    {
        $counterparty = self::response(LabelMonetaryAccountObject::class);
        $counterparty->setIban($iban);
        $counterparty->setDisplayName($name);

        $payment = self::response(PaymentApiObject::class);
        $payment->setId($id);
        $payment->setMonetaryAccountId($monetary_account_id);
        $amount = self::response(AmountObject::class);
        $amount->setValue($value);
        $amount->setCurrency($currency);
        $payment->setAmount($amount);
        $payment->setCounterpartyAlias($counterparty);

        return $payment;
    }

    /**
     * An SDK model as it comes out of a bunq response: the SDK fills those without calling the constructor, whose
     * parameters are the fields of a request.
     */
    private static function response($class)
    {
        return (new \ReflectionClass($class))->newInstanceWithoutConstructor();
    }

    private function record($method, array $arguments)
    {
        $this->calls[] = array_merge(array($method), $arguments);

        if (isset($this->exceptions[$method])) {
            throw $this->exceptions[$method];
        }
    }
}
