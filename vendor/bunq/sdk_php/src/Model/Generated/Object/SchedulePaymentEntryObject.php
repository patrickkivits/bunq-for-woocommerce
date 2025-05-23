<?php
namespace bunq\Model\Generated\Object;

use bunq\Model\Core\BunqModel;

/**
 * @generated
 */
class SchedulePaymentEntryObject extends BunqModel
{
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
     * The Amount to transfer with the Payment. Must be bigger 0 and smaller than the MonetaryAccount's balance.
     *
     * @var AmountObject
     */
    protected $amountFieldForRequest;

    /**
     * The Alias of the party we are transferring the money to. Can be an Alias of type EMAIL or PHONE (for bunq
     * MonetaryAccounts) or IBAN (for external bank account).
     *
     * @var PointerObject
     */
    protected $counterpartyAliasFieldForRequest;

    /**
     * The description for the Payment. Maximum 140 characters for Payments to external IBANs, 9000 characters for Payments to
     * only other bunq MonetaryAccounts. Field is required but can be an empty string.
     *
     * @var string
     */
    protected $descriptionFieldForRequest;

    /**
     * The Attachments to attach to the Payment.
     *
     * @var BunqIdObject[]|null
     */
    protected $attachmentFieldForRequest;

    /**
     * Optional data to be included with the Payment specific to the merchant.
     *
     * @var string|null
     */
    protected $merchantReferenceFieldForRequest;

    /**
     * Whether or not sending a bunq.to payment is allowed.
     *
     * @var bool|null
     */
    protected $allowBunqtoFieldForRequest;

    /**
     * @param AmountObject $amount The Amount to transfer with the Payment. Must be bigger 0 and smaller than the
     * MonetaryAccount's balance.
     * @param PointerObject $counterpartyAlias The Alias of the party we are transferring the money to. Can be an Alias of type
     * EMAIL or PHONE (for bunq MonetaryAccounts) or IBAN (for external bank account).
     * @param string $description The description for the Payment. Maximum 140 characters for Payments to external IBANs, 9000
     * characters for Payments to only other bunq MonetaryAccounts. Field is required but can be an empty string.
     * @param BunqIdObject[]|null $attachment The Attachments to attach to the Payment.
     * @param string|null $merchantReference Optional data to be included with the Payment specific to the merchant.
     * @param bool|null $allowBunqto Whether or not sending a bunq.to payment is allowed.
     */
    public function __construct(AmountObject  $amount, PointerObject  $counterpartyAlias, string  $description, array  $attachment = null, string  $merchantReference = null, bool  $allowBunqto = null)
    {
        $this->amountFieldForRequest = $amount;
        $this->counterpartyAliasFieldForRequest = $counterpartyAlias;
        $this->descriptionFieldForRequest = $description;
        $this->attachmentFieldForRequest = $attachment;
        $this->merchantReferenceFieldForRequest = $merchantReference;
        $this->allowBunqtoFieldForRequest = $allowBunqto;
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
     * @return bool
     */
    public function isAllFieldNull()
    {
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

        if (!is_null($this->attachment)) {
            return false;
        }

        if (!is_null($this->merchantReference)) {
            return false;
        }

        return true;
    }
}
