<?php

namespace BunqTest;

/**
 * Stands in for bunq: records every call the plugin makes and answers with the bunq objects a test set up.
 * Shared by the unit and the integration suite.
 */
final class FakeBunqApi extends \PatrickKivits\BunqForWooCommerce\Bunq\ApiClient
{
    /** @var array[] Every call as array(method, argument, ...), oldest first. */
    public $calls = array();

    /** @var string[] Stored API contexts that can no longer be used (expired session, revoked key). */
    public $unusableContexts = array('', 'unusable-api-context');
    /** @var int The calls to bunq that succeeded (see ApiClient::get_call_count()). */
    public $callCount = 0;
    /** @var string|null The context JSON after loading, when bunq renewed the session; null keeps it unchanged. */
    public $renewedContext = null;
    /** @var string The context JSON a new API context serialises to. */
    public $createdContext = '{"created":1}';

    /** @var array What create_payment_request() returns. */
    public $createdPaymentRequest = array('id' => 55, 'url' => 'https://bunq.me/shop/24.95/1');
    /** @var array[] Payment requests by id. */
    public $paymentRequests = array();
    /** @var array[] Payments by id. */
    public $payments = array();
    /** @var int What create_refund() returns. */
    public $draftPaymentId = 9001;
    /** @var array What get_bank_accounts() returns. */
    public $bankAccounts = array('' => 'Select a bank account', 123 => 'NL00BUNQ0000000123 - Shop');

    /** @var string|null What get_account_name() returns. */
    public $accountName = null;

    /** @var \Throwable[]|array[] Thrown by the named method (create_payment_request, get_payment, ...) when set; a list is
     *       thrown one per call, a null in it lets that call through. */
    public $exceptions = array();

    public function load_api_context_from_json($json, ?callable $save = null)
    {
        $this->record(__FUNCTION__, array($json));

        if (in_array($json, $this->unusableContexts, true)) {
            return false;
        }

        if ($this->renewedContext !== null && $save) {
            $save($this->renewedContext);
        }

        return true;
    }

    public function get_call_count()
    {
        return $this->callCount;
    }

    public function create_api_context($api_key, $testmode)
    {
        $this->record(__FUNCTION__, func_get_args());

        return $this->createdContext;
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
     * A bunq.me payment request as ApiClient::get_payment_request() returns it.
     *
     * @param array[] $payments Payments made through the request (see payment()).
     */
    public static function paymentRequest($id, $status, array $payments = array())
    {
        return array(
            'id' => $id,
            'status' => $status,
            'result_inquiries' => array_map(function ($payment) {
                return array('payment' => $payment, 'bunq_me_tab_id' => 1);
            }, $payments),
        );
    }

    /**
     * A payment received on a bunq account, as bunq returns it.
     *
     * @param string $value The amount as bunq formats it ("24.95").
     */
    public static function payment($id, $value, $currency = 'EUR', $monetary_account_id = 123, $iban = 'NL12BUNQ0123456789', $name = 'J. Doe')
    {
        return array(
            'id' => $id,
            'monetary_account_id' => $monetary_account_id,
            'amount' => array('currency' => $currency, 'value' => $value),
            'counterparty_alias' => array('iban' => $iban, 'display_name' => $name),
        );
    }

    private function record($method, array $arguments)
    {
        $this->calls[] = array_merge(array($method), $arguments);

        $exception = isset($this->exceptions[$method]) ? $this->exceptions[$method] : null;

        if (is_array($exception)) {
            $exception = array_shift($this->exceptions[$method]);
        }

        if ($exception) {
            throw $exception;
        }

        $this->callCount++;
    }
}
