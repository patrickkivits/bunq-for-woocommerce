<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AmountObject;
use bunq\Model\Generated\Object\LabelMonetaryAccountObject;

/**
 * Used to create Transferwise payments.
 *
 * @generated
 */
class TransferwiseTransferApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_CREATE = 'user/%s/transferwise-quote/%s/transferwise-transfer';
    const ENDPOINT_URL_READ = 'user/%s/transferwise-quote/%s/transferwise-transfer/%s';
    const ENDPOINT_URL_LISTING = 'user/%s/transferwise-quote/%s/transferwise-transfer';

    /**
     * Field constants.
     */
    const FIELD_MONETARY_ACCOUNT_ID = 'monetary_account_id';
    const FIELD_RECIPIENT_ID = 'recipient_id';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'TransferwisePayment';

    /**
     * The LabelMonetaryAccount containing the public information of 'this' (party) side of the Payment.
     *
     * @var LabelMonetaryAccountObject
     */
    protected $alias;

    /**
     * The LabelMonetaryAccount containing the public information of the other (counterparty) side of the Payment.
     *
     * @var LabelMonetaryAccountObject
     */
    protected $counterpartyAlias;

    /**
     * The status.
     *
     * @var string
     */
    protected $status;

    /**
     * The subStatus.
     *
     * @var string
     */
    protected $subStatus;

    /**
     * The status as Transferwise reports it.
     *
     * @var string
     */
    protected $statusTransferwise;

    /**
     * A status to indicatie if Transferwise has an issue with this payment and requires more information.
     *
     * @var string
     */
    protected $statusTransferwiseIssue;

    /**
     * The source amount.
     *
     * @var AmountObject
     */
    protected $amountSource;

    /**
     * The target amount.
     *
     * @var AmountObject
     */
    protected $amountTarget;

    /**
     * The rate of the payment.
     *
     * @var string
     */
    protected $rate;

    /**
     * The reference of the payment.
     *
     * @var string
     */
    protected $reference;

    /**
     * The Pay-In reference of the payment.
     *
     * @var string
     */
    protected $payInReference;

    /**
     * The estimated delivery time.
     *
     * @var string
     */
    protected $timeDeliveryEstimate;

    /**
     * The quote details used to created the payment.
     *
     * @var TransferwiseQuoteApiObject
     */
    protected $quote;

    /**
     * The id of the monetary account the payment should be made from.
     *
     * @var string
     */
    protected $monetaryAccountIdFieldForRequest;

    /**
     * The id of the target account.
     *
     * @var string
     */
    protected $recipientIdFieldForRequest;

    /**
     * @param string $monetaryAccountId The id of the monetary account the payment should be made from.
     * @param string $recipientId The id of the target account.
     */
    public function __construct(string  $monetaryAccountId, string  $recipientId)
    {
        $this->monetaryAccountIdFieldForRequest = $monetaryAccountId;
        $this->recipientIdFieldForRequest = $recipientId;
    }

    /**
     * @param int $transferwiseQuoteId
     * @param string $monetaryAccountId The id of the monetary account the payment should be made from.
     * @param string $recipientId The id of the target account.
     * @param string[] $customHeaders
     *
     * @return BunqResponseInt
     */
    public static function create(int $transferwiseQuoteId, string  $monetaryAccountId, string  $recipientId, array $customHeaders = []): BunqResponseInt
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->post(
            vsprintf(
                self::ENDPOINT_URL_CREATE,
                [static::determineUserId(), $transferwiseQuoteId]
            ),
            [self::FIELD_MONETARY_ACCOUNT_ID => $monetaryAccountId,
self::FIELD_RECIPIENT_ID => $recipientId],
            $customHeaders
        );

        return BunqResponseInt::castFromBunqResponse(
            static::processForId($responseRaw)
        );
    }

    /**
     * @param int $transferwiseQuoteId
     * @param int $transferwiseTransferId
     * @param string[] $customHeaders
     *
     * @return BunqResponseTransferwiseTransfer
     */
    public static function get(int $transferwiseQuoteId, int $transferwiseTransferId, array $customHeaders = []): BunqResponseTransferwiseTransfer
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_READ,
                [static::determineUserId(), $transferwiseQuoteId, $transferwiseTransferId]
            ),
            [],
            $customHeaders
        );

        return BunqResponseTransferwiseTransfer::castFromBunqResponse(
            static::fromJson($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * This method is called "listing" because "list" is a restricted PHP word and cannot be used as constants, class names,
     * function or method names.
     *
     * @param int $transferwiseQuoteId
     * @param string[] $params
     * @param string[] $customHeaders
     *
     * @return BunqResponseTransferwiseTransferApiObjectList
     */
    public static function listing(int $transferwiseQuoteId, array $params = [], array $customHeaders = []): BunqResponseTransferwiseTransferApiObjectList
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_LISTING,
                [static::determineUserId(), $transferwiseQuoteId]
            ),
            $params,
            $customHeaders
        );

        return BunqResponseTransferwiseTransferApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * The LabelMonetaryAccount containing the public information of 'this' (party) side of the Payment.
     *
     * @return LabelMonetaryAccountObject
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param LabelMonetaryAccountObject $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * The LabelMonetaryAccount containing the public information of the other (counterparty) side of the Payment.
     *
     * @return LabelMonetaryAccountObject
     */
    public function getCounterpartyAlias()
    {
        return $this->counterpartyAlias;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param LabelMonetaryAccountObject $counterpartyAlias
     */
    public function setCounterpartyAlias($counterpartyAlias)
    {
        $this->counterpartyAlias = $counterpartyAlias;
    }

    /**
     * The status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * The subStatus.
     *
     * @return string
     */
    public function getSubStatus()
    {
        return $this->subStatus;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $subStatus
     */
    public function setSubStatus($subStatus)
    {
        $this->subStatus = $subStatus;
    }

    /**
     * The status as Transferwise reports it.
     *
     * @return string
     */
    public function getStatusTransferwise()
    {
        return $this->statusTransferwise;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $statusTransferwise
     */
    public function setStatusTransferwise($statusTransferwise)
    {
        $this->statusTransferwise = $statusTransferwise;
    }

    /**
     * A status to indicatie if Transferwise has an issue with this payment and requires more information.
     *
     * @return string
     */
    public function getStatusTransferwiseIssue()
    {
        return $this->statusTransferwiseIssue;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $statusTransferwiseIssue
     */
    public function setStatusTransferwiseIssue($statusTransferwiseIssue)
    {
        $this->statusTransferwiseIssue = $statusTransferwiseIssue;
    }

    /**
     * The source amount.
     *
     * @return AmountObject
     */
    public function getAmountSource()
    {
        return $this->amountSource;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountSource
     */
    public function setAmountSource($amountSource)
    {
        $this->amountSource = $amountSource;
    }

    /**
     * The target amount.
     *
     * @return AmountObject
     */
    public function getAmountTarget()
    {
        return $this->amountTarget;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountTarget
     */
    public function setAmountTarget($amountTarget)
    {
        $this->amountTarget = $amountTarget;
    }

    /**
     * The rate of the payment.
     *
     * @return string
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $rate
     */
    public function setRate($rate)
    {
        $this->rate = $rate;
    }

    /**
     * The reference of the payment.
     *
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $reference
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    /**
     * The Pay-In reference of the payment.
     *
     * @return string
     */
    public function getPayInReference()
    {
        return $this->payInReference;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $payInReference
     */
    public function setPayInReference($payInReference)
    {
        $this->payInReference = $payInReference;
    }

    /**
     * The estimated delivery time.
     *
     * @return string
     */
    public function getTimeDeliveryEstimate()
    {
        return $this->timeDeliveryEstimate;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $timeDeliveryEstimate
     */
    public function setTimeDeliveryEstimate($timeDeliveryEstimate)
    {
        $this->timeDeliveryEstimate = $timeDeliveryEstimate;
    }

    /**
     * The quote details used to created the payment.
     *
     * @return TransferwiseQuoteApiObject
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param TransferwiseQuoteApiObject $quote
     */
    public function setQuote($quote)
    {
        $this->quote = $quote;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->alias)) {
            return false;
        }

        if (!is_null($this->counterpartyAlias)) {
            return false;
        }

        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->subStatus)) {
            return false;
        }

        if (!is_null($this->statusTransferwise)) {
            return false;
        }

        if (!is_null($this->statusTransferwiseIssue)) {
            return false;
        }

        if (!is_null($this->amountSource)) {
            return false;
        }

        if (!is_null($this->amountTarget)) {
            return false;
        }

        if (!is_null($this->rate)) {
            return false;
        }

        if (!is_null($this->reference)) {
            return false;
        }

        if (!is_null($this->payInReference)) {
            return false;
        }

        if (!is_null($this->timeDeliveryEstimate)) {
            return false;
        }

        if (!is_null($this->quote)) {
            return false;
        }

        return true;
    }
}
