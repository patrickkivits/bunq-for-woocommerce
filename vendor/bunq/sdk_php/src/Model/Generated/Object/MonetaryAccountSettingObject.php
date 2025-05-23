<?php
namespace bunq\Model\Generated\Object;

use bunq\Model\Core\BunqModel;

/**
 * @generated
 */
class MonetaryAccountSettingObject extends BunqModel
{
    /**
     * The color chosen for the MonetaryAccount.
     *
     * @var string
     */
    protected $color;

    /**
     * The icon chosen for the MonetaryAccount.
     *
     * @var string
     */
    protected $icon;

    /**
     * The status of the avatar. Can be either AVATAR_DEFAULT, AVATAR_CUSTOM or AVATAR_UNDETERMINED.
     *
     * @var string
     */
    protected $defaultAvatarStatus;

    /**
     * The chat restriction. Possible values are ALLOW_INCOMING or BLOCK_INCOMING
     *
     * @var string
     */
    protected $restrictionChat;

    /**
     * The preference for this monetary account on whether to automatically accept or reject expiring SDDs.
     *
     * @var string
     */
    protected $sddExpirationAction;

    /**
     * The color chosen for the MonetaryAccount in hexadecimal format.
     *
     * @var string|null
     */
    protected $colorFieldForRequest;

    /**
     * The icon chosen for the MonetaryAccount.
     *
     * @var string|null
     */
    protected $iconFieldForRequest;

    /**
     * The status of the avatar. Cannot be updated directly.
     *
     * @var string|null
     */
    protected $defaultAvatarStatusFieldForRequest;

    /**
     * The chat restriction. Possible values are ALLOW_INCOMING or BLOCK_INCOMING
     *
     * @var string|null
     */
    protected $restrictionChatFieldForRequest;

    /**
     * The preference for this monetary account on whether to automatically accept or reject expiring SDDs.
     *
     * @var string|null
     */
    protected $sddExpirationActionFieldForRequest;

    /**
     * @param string|null $color The color chosen for the MonetaryAccount in hexadecimal format.
     * @param string|null $icon The icon chosen for the MonetaryAccount.
     * @param string|null $defaultAvatarStatus The status of the avatar. Cannot be updated directly.
     * @param string|null $restrictionChat The chat restriction. Possible values are ALLOW_INCOMING or BLOCK_INCOMING
     * @param string|null $sddExpirationAction The preference for this monetary account on whether to automatically accept or
     * reject expiring SDDs.
     */
    public function __construct(string  $color = null, string  $icon = null, string  $defaultAvatarStatus = null, string  $restrictionChat = null, string  $sddExpirationAction = null)
    {
        $this->colorFieldForRequest = $color;
        $this->iconFieldForRequest = $icon;
        $this->defaultAvatarStatusFieldForRequest = $defaultAvatarStatus;
        $this->restrictionChatFieldForRequest = $restrictionChat;
        $this->sddExpirationActionFieldForRequest = $sddExpirationAction;
    }

    /**
     * The color chosen for the MonetaryAccount.
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
     * The icon chosen for the MonetaryAccount.
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * The status of the avatar. Can be either AVATAR_DEFAULT, AVATAR_CUSTOM or AVATAR_UNDETERMINED.
     *
     * @return string
     */
    public function getDefaultAvatarStatus()
    {
        return $this->defaultAvatarStatus;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $defaultAvatarStatus
     */
    public function setDefaultAvatarStatus($defaultAvatarStatus)
    {
        $this->defaultAvatarStatus = $defaultAvatarStatus;
    }

    /**
     * The chat restriction. Possible values are ALLOW_INCOMING or BLOCK_INCOMING
     *
     * @return string
     */
    public function getRestrictionChat()
    {
        return $this->restrictionChat;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $restrictionChat
     */
    public function setRestrictionChat($restrictionChat)
    {
        $this->restrictionChat = $restrictionChat;
    }

    /**
     * The preference for this monetary account on whether to automatically accept or reject expiring SDDs.
     *
     * @return string
     */
    public function getSddExpirationAction()
    {
        return $this->sddExpirationAction;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $sddExpirationAction
     */
    public function setSddExpirationAction($sddExpirationAction)
    {
        $this->sddExpirationAction = $sddExpirationAction;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->color)) {
            return false;
        }

        if (!is_null($this->icon)) {
            return false;
        }

        if (!is_null($this->defaultAvatarStatus)) {
            return false;
        }

        if (!is_null($this->restrictionChat)) {
            return false;
        }

        if (!is_null($this->sddExpirationAction)) {
            return false;
        }

        return true;
    }
}
