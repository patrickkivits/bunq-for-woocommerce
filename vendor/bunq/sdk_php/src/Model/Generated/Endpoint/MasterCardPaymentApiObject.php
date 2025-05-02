<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AddressObject;
use bunq\Model\Generated\Object\AmountObject;
use bunq\Model\Generated\Object\AttachmentMonetaryAccountPaymentObject;
use bunq\Model\Generated\Object\GeolocationObject;
use bunq\Model\Generated\Object\LabelMonetaryAccountObject;
use bunq\Model\Generated\Object\PaymentArrivalExpectedObject;
use bunq\Model\Generated\Object\PaymentFeeObject;
use bunq\Model\Generated\Object\RequestInquiryReferenceObject;

/**
 * MasterCard transaction view.
 *
 * @generated
 */
class MasterCardPaymentApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_LISTING = 'user/%s/monetary-account/%s/mastercard-action/%s/payment';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'Payment';

    /**
     * The id of the Payment.
     *
     * @var int
     */
    protected $id;

    /**
     * The timestamp when the Payment was done.
     *
     * @var string
     */
    protected $created;

    /**
     * The timestamp when the Payment was last updated (will be updated when chat messages are received).
     *
     * @var string
     */
    protected $updated;

    /**
     * The id of the MonetaryAccount the Payment was made to or from (depending on whether this is an incoming or outgoing
     * Payment).
     *
     * @var int
     */
    protected $monetaryAccountId;

    /**
     * The Amount transferred by the Payment. Will be negative for outgoing Payments and positive for incoming Payments
     * (relative to the MonetaryAccount indicated by monetary_account_id).
     *
     * @var AmountObject
     */
    protected $amount;

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
     * The description for the Payment. Maximum 140 characters for Payments to external IBANs, 9000 characters for Payments to
     * only other bunq MonetaryAccounts.
     *
     * @var string
     */
    protected $description;

    /**
     * The type of Payment, can be BUNQ, EBA_SCT, EBA_SDD, IDEAL, SWIFT or FIS (card).
     *
     * @var string
     */
    protected $type;

    /**
     * The sub-type of the Payment, can be PAYMENT, WITHDRAWAL, REVERSAL, REQUEST, BILLING, SCT, SDD or NLO.
     *
     * @var string
     */
    protected $subType;

    /**
     * Information about the expected arrival of the payment.
     *
     * @var PaymentArrivalExpectedObject
     */
    protected $paymentArrivalExpected;

    /**
     * The status of the bunq.to payment.
     *
     * @var string
     */
    protected $bunqtoStatus;

    /**
     * The sub status of the bunq.to payment.
     *
     * @var string
     */
    protected $bunqtoSubStatus;

    /**
     * The status of the bunq.to payment.
     *
     * @var string
     */
    protected $bunqtoShareUrl;

    /**
     * When bunq.to payment is about to expire.
     *
     * @var string
     */
    protected $bunqtoExpiry;

    /**
     * The timestamp of when the bunq.to payment was responded to.
     *
     * @var string
     */
    protected $bunqtoTimeResponded;

    /**
     * The Attachments attached to the Payment.
     *
     * @var AttachmentMonetaryAccountPaymentObject[]
     */
    protected $attachment;

    /**
     * Optional data included with the Payment specific to the merchant.
     *
     * @var string
     */
    protected $merchantReference;

    /**
     * The id of the PaymentBatch if this Payment was part of one.
     *
     * @var int
     */
    protected $batchId;

    /**
     * The id of the JobScheduled if the Payment was scheduled.
     *
     * @var int
     */
    protected $scheduledId;

    /**
     * A shipping Address provided with the Payment, currently unused.
     *
     * @var AddressObject
     */
    protected $addressShipping;

    /**
     * A billing Address provided with the Payment, currently unused.
     *
     * @var AddressObject
     */
    protected $addressBilling;

    /**
     * The Geolocation where the Payment was done from.
     *
     * @var GeolocationObject
     */
    protected $geolocation;

    /**
     * The reference to the object used for split the bill. Can be RequestInquiry or RequestInquiryBatch
     *
     * @var RequestInquiryReferenceObject[]
     */
    protected $requestReferenceSplitTheBill;

    /**
     * The new balance of the monetary account after the mutation.
     *
     * @var AmountObject
     */
    protected $balanceAfterMutation;

    /**
     * A reference to the PaymentAutoAllocateInstance if it exists.
     *
     * @var PaymentAutoAllocateInstanceApiObject
     */
    protected $paymentAutoAllocateInstance;

    /**
     * A reference to the PaymentSuspendedOutgoing if it exists.
     *
     * @var PaymentSuspendedOutgoingApiObject
     */
    protected $paymentSuspendedOutgoing;

    /**
     * Incurred fee for the payment.
     *
     * @var PaymentFeeObject
     */
    protected $paymentFee;

    /**
     * This method is called "listing" because "list" is a restricted PHP word and cannot be used as constants, class names,
     * function or method names.
     *
     * @param int $mastercardActionId
     * @param int|null $monetaryAccountId
     * @param string[] $params
     * @param string[] $customHeaders
     *
     * @return BunqResponseMasterCardPaymentApiObjectList
     */
    public static function listing(int $mastercardActionId, int $monetaryAccountId = null, array $params = [], array $customHeaders = []): BunqResponseMasterCardPaymentApiObjectList
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_LISTING,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $mastercardActionId]
            ),
            $params,
            $customHeaders
        );

        return BunqResponseMasterCardPaymentApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * The id of the Payment.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * The timestamp when the Payment was done.
     *
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * The timestamp when the Payment was last updated (will be updated when chat messages are received).
     *
     * @return string
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * The id of the MonetaryAccount the Payment was made to or from (depending on whether this is an incoming or outgoing
     * Payment).
     *
     * @return int
     */
    public function getMonetaryAccountId()
    {
        return $this->monetaryAccountId;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $monetaryAccountId
     */
    public function setMonetaryAccountId($monetaryAccountId)
    {
        $this->monetaryAccountId = $monetaryAccountId;
    }

    /**
     * The Amount transferred by the Payment. Will be negative for outgoing Payments and positive for incoming Payments
     * (relative to the MonetaryAccount indicated by monetary_account_id).
     *
     * @return AmountObject
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
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
     * The description for the Payment. Maximum 140 characters for Payments to external IBANs, 9000 characters for Payments to
     * only other bunq MonetaryAccounts.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * The type of Payment, can be BUNQ, EBA_SCT, EBA_SDD, IDEAL, SWIFT or FIS (card).
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * The sub-type of the Payment, can be PAYMENT, WITHDRAWAL, REVERSAL, REQUEST, BILLING, SCT, SDD or NLO.
     *
     * @return string
     */
    public function getSubType()
    {
        return $this->subType;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $subType
     */
    public function setSubType($subType)
    {
        $this->subType = $subType;
    }

    /**
     * Information about the expected arrival of the payment.
     *
     * @return PaymentArrivalExpectedObject
     */
    public function getPaymentArrivalExpected()
    {
        return $this->paymentArrivalExpected;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param PaymentArrivalExpectedObject $paymentArrivalExpected
     */
    public function setPaymentArrivalExpected($paymentArrivalExpected)
    {
        $this->paymentArrivalExpected = $paymentArrivalExpected;
    }

    /**
     * The status of the bunq.to payment.
     *
     * @return string
     */
    public function getBunqtoStatus()
    {
        return $this->bunqtoStatus;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $bunqtoStatus
     */
    public function setBunqtoStatus($bunqtoStatus)
    {
        $this->bunqtoStatus = $bunqtoStatus;
    }

    /**
     * The sub status of the bunq.to payment.
     *
     * @return string
     */
    public function getBunqtoSubStatus()
    {
        return $this->bunqtoSubStatus;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $bunqtoSubStatus
     */
    public function setBunqtoSubStatus($bunqtoSubStatus)
    {
        $this->bunqtoSubStatus = $bunqtoSubStatus;
    }

    /**
     * The status of the bunq.to payment.
     *
     * @return string
     */
    public function getBunqtoShareUrl()
    {
        return $this->bunqtoShareUrl;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $bunqtoShareUrl
     */
    public function setBunqtoShareUrl($bunqtoShareUrl)
    {
        $this->bunqtoShareUrl = $bunqtoShareUrl;
    }

    /**
     * When bunq.to payment is about to expire.
     *
     * @return string
     */
    public function getBunqtoExpiry()
    {
        return $this->bunqtoExpiry;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $bunqtoExpiry
     */
    public function setBunqtoExpiry($bunqtoExpiry)
    {
        $this->bunqtoExpiry = $bunqtoExpiry;
    }

    /**
     * The timestamp of when the bunq.to payment was responded to.
     *
     * @return string
     */
    public function getBunqtoTimeResponded()
    {
        return $this->bunqtoTimeResponded;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $bunqtoTimeResponded
     */
    public function setBunqtoTimeResponded($bunqtoTimeResponded)
    {
        $this->bunqtoTimeResponded = $bunqtoTimeResponded;
    }

    /**
     * The Attachments attached to the Payment.
     *
     * @return AttachmentMonetaryAccountPaymentObject[]
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AttachmentMonetaryAccountPaymentObject[] $attachment
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * Optional data included with the Payment specific to the merchant.
     *
     * @return string
     */
    public function getMerchantReference()
    {
        return $this->merchantReference;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $merchantReference
     */
    public function setMerchantReference($merchantReference)
    {
        $this->merchantReference = $merchantReference;
    }

    /**
     * The id of the PaymentBatch if this Payment was part of one.
     *
     * @return int
     */
    public function getBatchId()
    {
        return $this->batchId;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $batchId
     */
    public function setBatchId($batchId)
    {
        $this->batchId = $batchId;
    }

    /**
     * The id of the JobScheduled if the Payment was scheduled.
     *
     * @return int
     */
    public function getScheduledId()
    {
        return $this->scheduledId;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $scheduledId
     */
    public function setScheduledId($scheduledId)
    {
        $this->scheduledId = $scheduledId;
    }

    /**
     * A shipping Address provided with the Payment, currently unused.
     *
     * @return AddressObject
     */
    public function getAddressShipping()
    {
        return $this->addressShipping;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AddressObject $addressShipping
     */
    public function setAddressShipping($addressShipping)
    {
        $this->addressShipping = $addressShipping;
    }

    /**
     * A billing Address provided with the Payment, currently unused.
     *
     * @return AddressObject
     */
    public function getAddressBilling()
    {
        return $this->addressBilling;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AddressObject $addressBilling
     */
    public function setAddressBilling($addressBilling)
    {
        $this->addressBilling = $addressBilling;
    }

    /**
     * The Geolocation where the Payment was done from.
     *
     * @return GeolocationObject
     */
    public function getGeolocation()
    {
        return $this->geolocation;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param GeolocationObject $geolocation
     */
    public function setGeolocation($geolocation)
    {
        $this->geolocation = $geolocation;
    }

    /**
     * The reference to the object used for split the bill. Can be RequestInquiry or RequestInquiryBatch
     *
     * @return RequestInquiryReferenceObject[]
     */
    public function getRequestReferenceSplitTheBill()
    {
        return $this->requestReferenceSplitTheBill;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param RequestInquiryReferenceObject[] $requestReferenceSplitTheBill
     */
    public function setRequestReferenceSplitTheBill($requestReferenceSplitTheBill)
    {
        $this->requestReferenceSplitTheBill = $requestReferenceSplitTheBill;
    }

    /**
     * The new balance of the monetary account after the mutation.
     *
     * @return AmountObject
     */
    public function getBalanceAfterMutation()
    {
        return $this->balanceAfterMutation;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $balanceAfterMutation
     */
    public function setBalanceAfterMutation($balanceAfterMutation)
    {
        $this->balanceAfterMutation = $balanceAfterMutation;
    }

    /**
     * A reference to the PaymentAutoAllocateInstance if it exists.
     *
     * @return PaymentAutoAllocateInstanceApiObject
     */
    public function getPaymentAutoAllocateInstance()
    {
        return $this->paymentAutoAllocateInstance;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param PaymentAutoAllocateInstanceApiObject $paymentAutoAllocateInstance
     */
    public function setPaymentAutoAllocateInstance($paymentAutoAllocateInstance)
    {
        $this->paymentAutoAllocateInstance = $paymentAutoAllocateInstance;
    }

    /**
     * A reference to the PaymentSuspendedOutgoing if it exists.
     *
     * @return PaymentSuspendedOutgoingApiObject
     */
    public function getPaymentSuspendedOutgoing()
    {
        return $this->paymentSuspendedOutgoing;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param PaymentSuspendedOutgoingApiObject $paymentSuspendedOutgoing
     */
    public function setPaymentSuspendedOutgoing($paymentSuspendedOutgoing)
    {
        $this->paymentSuspendedOutgoing = $paymentSuspendedOutgoing;
    }

    /**
     * Incurred fee for the payment.
     *
     * @return PaymentFeeObject
     */
    public function getPaymentFee()
    {
        return $this->paymentFee;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param PaymentFeeObject $paymentFee
     */
    public function setPaymentFee($paymentFee)
    {
        $this->paymentFee = $paymentFee;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->id)) {
            return false;
        }

        if (!is_null($this->created)) {
            return false;
        }

        if (!is_null($this->updated)) {
            return false;
        }

        if (!is_null($this->monetaryAccountId)) {
            return false;
        }

        if (!is_null($this->amount)) {
            return false;
        }

        if (!is_null($this->alias)) {
            return false;
        }

        if (!is_null($this->counterpartyAlias)) {
            return false;
        }

        if (!is_null($this->description)) {
            return false;
        }

        if (!is_null($this->type)) {
            return false;
        }

        if (!is_null($this->subType)) {
            return false;
        }

        if (!is_null($this->paymentArrivalExpected)) {
            return false;
        }

        if (!is_null($this->bunqtoStatus)) {
            return false;
        }

        if (!is_null($this->bunqtoSubStatus)) {
            return false;
        }

        if (!is_null($this->bunqtoShareUrl)) {
            return false;
        }

        if (!is_null($this->bunqtoExpiry)) {
            return false;
        }

        if (!is_null($this->bunqtoTimeResponded)) {
            return false;
        }

        if (!is_null($this->attachment)) {
            return false;
        }

        if (!is_null($this->merchantReference)) {
            return false;
        }

        if (!is_null($this->batchId)) {
            return false;
        }

        if (!is_null($this->scheduledId)) {
            return false;
        }

        if (!is_null($this->addressShipping)) {
            return false;
        }

        if (!is_null($this->addressBilling)) {
            return false;
        }

        if (!is_null($this->geolocation)) {
            return false;
        }

        if (!is_null($this->requestReferenceSplitTheBill)) {
            return false;
        }

        if (!is_null($this->balanceAfterMutation)) {
            return false;
        }

        if (!is_null($this->paymentAutoAllocateInstance)) {
            return false;
        }

        if (!is_null($this->paymentSuspendedOutgoing)) {
            return false;
        }

        if (!is_null($this->paymentFee)) {
            return false;
        }

        return true;
    }
}
