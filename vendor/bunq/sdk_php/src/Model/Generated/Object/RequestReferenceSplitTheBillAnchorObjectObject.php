<?php
namespace bunq\Model\Generated\Object;

use bunq\Exception\BunqException;
use bunq\Model\Core\AnchorObjectInterface;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Endpoint\CurrencyConversionApiObject;
use bunq\Model\Generated\Endpoint\DraftPaymentApiObject;
use bunq\Model\Generated\Endpoint\InvoiceApiObject;
use bunq\Model\Generated\Endpoint\MasterCardActionApiObject;
use bunq\Model\Generated\Endpoint\PaymentApiObject;
use bunq\Model\Generated\Endpoint\PaymentBatchApiObject;
use bunq\Model\Generated\Endpoint\RequestResponseApiObject;
use bunq\Model\Generated\Endpoint\ScheduleInstanceApiObject;
use bunq\Model\Generated\Endpoint\TransferwiseTransferApiObject;
use bunq\Model\Generated\Endpoint\WhitelistResultApiObject;

/**
 * @generated
 */
class RequestReferenceSplitTheBillAnchorObjectObject extends BunqModel implements AnchorObjectInterface
{
    /**
     * Error constants.
     */
    const ERROR_NULL_FIELDS = 'All fields of an extended model or object are null.';

    /**
     * @var InvoiceApiObject|null
     */
    protected $billingInvoice;

    /**
     * @var DraftPaymentApiObject|null
     */
    protected $draftPayment;

    /**
     * @var MasterCardActionApiObject|null
     */
    protected $masterCardAction;

    /**
     * @var PaymentApiObject|null
     */
    protected $payment;

    /**
     * @var PaymentBatchApiObject|null
     */
    protected $paymentBatch;

    /**
     * @var RequestResponseApiObject|null
     */
    protected $requestResponse;

    /**
     * @var ScheduleInstanceApiObject|null
     */
    protected $scheduleInstance;

    /**
     * @var WhitelistResultApiObject|null
     */
    protected $whitelistResult;

    /**
     * @var TransferwiseTransferApiObject|null
     */
    protected $transferwisePayment;

    /**
     * @var CurrencyConversionApiObject|null
     */
    protected $currencyConversion;

    /**
     * @return InvoiceApiObject
     */
    public function getBillingInvoice()
    {
        return $this->billingInvoice;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param InvoiceApiObject $billingInvoice
     */
    public function setBillingInvoice($billingInvoice)
    {
        $this->billingInvoice = $billingInvoice;
    }

    /**
     * @return DraftPaymentApiObject
     */
    public function getDraftPayment()
    {
        return $this->draftPayment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param DraftPaymentApiObject $draftPayment
     */
    public function setDraftPayment($draftPayment)
    {
        $this->draftPayment = $draftPayment;
    }

    /**
     * @return MasterCardActionApiObject
     */
    public function getMasterCardAction()
    {
        return $this->masterCardAction;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MasterCardActionApiObject $masterCardAction
     */
    public function setMasterCardAction($masterCardAction)
    {
        $this->masterCardAction = $masterCardAction;
    }

    /**
     * @return PaymentApiObject
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param PaymentApiObject $payment
     */
    public function setPayment($payment)
    {
        $this->payment = $payment;
    }

    /**
     * @return PaymentBatchApiObject
     */
    public function getPaymentBatch()
    {
        return $this->paymentBatch;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param PaymentBatchApiObject $paymentBatch
     */
    public function setPaymentBatch($paymentBatch)
    {
        $this->paymentBatch = $paymentBatch;
    }

    /**
     * @return RequestResponseApiObject
     */
    public function getRequestResponse()
    {
        return $this->requestResponse;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param RequestResponseApiObject $requestResponse
     */
    public function setRequestResponse($requestResponse)
    {
        $this->requestResponse = $requestResponse;
    }

    /**
     * @return ScheduleInstanceApiObject
     */
    public function getScheduleInstance()
    {
        return $this->scheduleInstance;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param ScheduleInstanceApiObject $scheduleInstance
     */
    public function setScheduleInstance($scheduleInstance)
    {
        $this->scheduleInstance = $scheduleInstance;
    }

    /**
     * @return WhitelistResultApiObject
     */
    public function getWhitelistResult()
    {
        return $this->whitelistResult;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param WhitelistResultApiObject $whitelistResult
     */
    public function setWhitelistResult($whitelistResult)
    {
        $this->whitelistResult = $whitelistResult;
    }

    /**
     * @return TransferwiseTransferApiObject
     */
    public function getTransferwisePayment()
    {
        return $this->transferwisePayment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param TransferwiseTransferApiObject $transferwisePayment
     */
    public function setTransferwisePayment($transferwisePayment)
    {
        $this->transferwisePayment = $transferwisePayment;
    }

    /**
     * @return CurrencyConversionApiObject
     */
    public function getCurrencyConversion()
    {
        return $this->currencyConversion;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param CurrencyConversionApiObject $currencyConversion
     */
    public function setCurrencyConversion($currencyConversion)
    {
        $this->currencyConversion = $currencyConversion;
    }

    /**
     * @return BunqModel
     * @throws BunqException
     */
    public function getReferencedObject()
    {
        if (!is_null($this->billingInvoice)) {
            return $this->billingInvoice;
        }

        if (!is_null($this->draftPayment)) {
            return $this->draftPayment;
        }

        if (!is_null($this->masterCardAction)) {
            return $this->masterCardAction;
        }

        if (!is_null($this->payment)) {
            return $this->payment;
        }

        if (!is_null($this->paymentBatch)) {
            return $this->paymentBatch;
        }

        if (!is_null($this->requestResponse)) {
            return $this->requestResponse;
        }

        if (!is_null($this->scheduleInstance)) {
            return $this->scheduleInstance;
        }

        if (!is_null($this->whitelistResult)) {
            return $this->whitelistResult;
        }

        if (!is_null($this->transferwisePayment)) {
            return $this->transferwisePayment;
        }

        if (!is_null($this->currencyConversion)) {
            return $this->currencyConversion;
        }

        throw new BunqException(self::ERROR_NULL_FIELDS);
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->billingInvoice)) {
            return false;
        }

        if (!is_null($this->draftPayment)) {
            return false;
        }

        if (!is_null($this->masterCardAction)) {
            return false;
        }

        if (!is_null($this->payment)) {
            return false;
        }

        if (!is_null($this->paymentBatch)) {
            return false;
        }

        if (!is_null($this->requestResponse)) {
            return false;
        }

        if (!is_null($this->scheduleInstance)) {
            return false;
        }

        if (!is_null($this->whitelistResult)) {
            return false;
        }

        if (!is_null($this->transferwisePayment)) {
            return false;
        }

        if (!is_null($this->currencyConversion)) {
            return false;
        }

        return true;
    }
}
