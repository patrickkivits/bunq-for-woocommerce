<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AttachmentPublicObject;
use bunq\Model\Generated\Object\BunqMeMerchantAvailableObject;
use bunq\Model\Generated\Object\LabelMonetaryAccountObject;
use bunq\Model\Generated\Object\PointerObject;

/**
 * bunq.me public profile of the user.
 *
 * @generated
 */
class BunqMeFundraiserProfileApiObject extends BunqModel
{
    /**
     * Field constants.
     */
    const FIELD_POINTER = 'pointer';

    /**
     * The color chosen for the bunq.me fundraiser profile in hexadecimal format.
     *
     * @var string
     */
    protected $color;

    /**
     * The LabelMonetaryAccount with the public information of the User and the MonetaryAccount that created the bunq.me
     * fundraiser profile.
     *
     * @var LabelMonetaryAccountObject
     */
    protected $alias;

    /**
     * The currency of the MonetaryAccount that created the bunq.me fundraiser profile.
     *
     * @var string
     */
    protected $currency;

    /**
     * The description of the bunq.me fundraiser profile.
     *
     * @var string
     */
    protected $description;

    /**
     * The attachment attached to the fundraiser profile.
     *
     * @var AttachmentPublicObject
     */
    protected $attachment;

    /**
     * The pointer (url) which will be used to access the bunq.me fundraiser profile.
     *
     * @var PointerObject
     */
    protected $pointer;

    /**
     * The status of the bunq.me fundraiser profile, can be ACTIVE or DEACTIVATED.
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
     * Provided if the user has enabled their invite link.
     *
     * @var string
     */
    protected $inviteProfileName;

    /**
     * List of available merchants.
     *
     * @var BunqMeMerchantAvailableObject[]
     */
    protected $merchantAvailable;

    /**
     * The pointer (url) which will be used to access the bunq.me fundraiser profile.
     *
     * @var PointerObject
     */
    protected $pointerFieldForRequest;

    /**
     * @param PointerObject $pointer The pointer (url) which will be used to access the bunq.me fundraiser profile.
     */
    public function __construct(PointerObject  $pointer)
    {
        $this->pointerFieldForRequest = $pointer;
    }

    /**
     * The color chosen for the bunq.me fundraiser profile in hexadecimal format.
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $color
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * The LabelMonetaryAccount with the public information of the User and the MonetaryAccount that created the bunq.me
     * fundraiser profile.
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
     * The currency of the MonetaryAccount that created the bunq.me fundraiser profile.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * The description of the bunq.me fundraiser profile.
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
     * The attachment attached to the fundraiser profile.
     *
     * @return AttachmentPublicObject
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AttachmentPublicObject $attachment
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * The pointer (url) which will be used to access the bunq.me fundraiser profile.
     *
     * @return PointerObject
     */
    public function getPointer()
    {
        return $this->pointer;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param PointerObject $pointer
     */
    public function setPointer($pointer)
    {
        $this->pointer = $pointer;
    }

    /**
     * The status of the bunq.me fundraiser profile, can be ACTIVE or DEACTIVATED.
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
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->color)) {
            return false;
        }

        if (!is_null($this->alias)) {
            return false;
        }

        if (!is_null($this->currency)) {
            return false;
        }

        if (!is_null($this->description)) {
            return false;
        }

        if (!is_null($this->attachment)) {
            return false;
        }

        if (!is_null($this->pointer)) {
            return false;
        }

        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->redirectUrl)) {
            return false;
        }

        if (!is_null($this->inviteProfileName)) {
            return false;
        }

        if (!is_null($this->merchantAvailable)) {
            return false;
        }

        return true;
    }
}
