<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AmountObject;
use bunq\Model\Generated\Object\BunqMeMerchantAvailableObject;
use bunq\Model\Generated\Object\LabelMonetaryAccountObject;

/**
 * bunq.me tabs allows you to create a payment request and share the link through e-mail, chat, etc. Multiple persons are
 * able to respond to the payment request and pay through bunq, iDeal or SOFORT.
 *
 * @generated
 */
class BunqMeTabEntryApiObject extends BunqModel
{
    /**
     * Field constants.
     */
    const FIELD_AMOUNT_INQUIRED = 'amount_inquired';
    const FIELD_DESCRIPTION = 'description';
    const FIELD_REDIRECT_URL = 'redirect_url';

    /**
     * The uuid of the bunq.me.
     *
     * @var string
     */
    protected $uuid;

    /**
     * The requested Amount.
     *
     * @var AmountObject
     */
    protected $amountInquired;

    /**
     * The LabelMonetaryAccount with the public information of the User and the MonetaryAccount that created the bunq.me link.
     *
     * @var LabelMonetaryAccountObject
     */
    protected $alias;

    /**
     * The description for the bunq.me. Maximum 9000 characters.
     *
     * @var string
     */
    protected $description;

    /**
     * The status of the bunq.me. Can be WAITING_FOR_PAYMENT, CANCELLED or EXPIRED.
     *
     * @var string
     */
    protected $status;

    /**
     * The URL which the user is sent to when a payment is completed.
     *
     * @var string
     */
    protected $redirectUrl;

    /**
     * List of available merchants.
     *
     * @var BunqMeMerchantAvailableObject[]
     */
    protected $merchantAvailable;

    /**
     * Provided if the user has enabled their invite link.
     *
     * @var string
     */
    protected $inviteProfileName;

    /**
     * The Amount requested to be paid. Can be optional.
     *
     * @var AmountObject
     */
    protected $amountInquiredFieldForRequest;

    /**
     * The description for the bunq.me. Maximum 9000 characters. Field is required but can be an empty string.
     *
     * @var string
     */
    protected $descriptionFieldForRequest;

    /**
     * The URL which the user is sent to after making a payment.
     *
     * @var string|null
     */
    protected $redirectUrlFieldForRequest;

    /**
     * @param AmountObject $amountInquired The Amount requested to be paid. Can be optional.
     * @param string $description The description for the bunq.me. Maximum 9000 characters. Field is required but can be an
     * empty string.
     * @param string|null $redirectUrl The URL which the user is sent to after making a payment.
     */
    public function __construct(AmountObject  $amountInquired, string  $description, string  $redirectUrl = null)
    {
        $this->amountInquiredFieldForRequest = $amountInquired;
        $this->descriptionFieldForRequest = $description;
        $this->redirectUrlFieldForRequest = $redirectUrl;
    }

    /**
     * The uuid of the bunq.me.
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $uuid
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * The requested Amount.
     *
     * @return AmountObject
     */
    public function getAmountInquired()
    {
        return $this->amountInquired;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountInquired
     */
    public function setAmountInquired($amountInquired)
    {
        $this->amountInquired = $amountInquired;
    }

    /**
     * The LabelMonetaryAccount with the public information of the User and the MonetaryAccount that created the bunq.me link.
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
     * The description for the bunq.me. Maximum 9000 characters.
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
     * The status of the bunq.me. Can be WAITING_FOR_PAYMENT, CANCELLED or EXPIRED.
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
     * The URL which the user is sent to when a payment is completed.
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $redirectUrl
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * List of available merchants.
     *
     * @return BunqMeMerchantAvailableObject[]
     */
    public function getMerchantAvailable()
    {
        return $this->merchantAvailable;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param BunqMeMerchantAvailableObject[] $merchantAvailable
     */
    public function setMerchantAvailable($merchantAvailable)
    {
        $this->merchantAvailable = $merchantAvailable;
    }

    /**
     * Provided if the user has enabled their invite link.
     *
     * @return string
     */
    public function getInviteProfileName()
    {
        return $this->inviteProfileName;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $inviteProfileName
     */
    public function setInviteProfileName($inviteProfileName)
    {
        $this->inviteProfileName = $inviteProfileName;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->uuid)) {
            return false;
        }

        if (!is_null($this->amountInquired)) {
            return false;
        }

        if (!is_null($this->alias)) {
            return false;
        }

        if (!is_null($this->description)) {
            return false;
        }

        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->redirectUrl)) {
            return false;
        }

        if (!is_null($this->merchantAvailable)) {
            return false;
        }

        if (!is_null($this->inviteProfileName)) {
            return false;
        }

        return true;
    }
}
