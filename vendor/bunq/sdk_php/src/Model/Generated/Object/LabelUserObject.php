<?php
namespace bunq\Model\Generated\Object;

use bunq\Model\Core\BunqModel;

/**
 * @generated
 */
class LabelUserObject extends BunqModel
{
    /**
     * The public UUID of the label-user.
     *
     * @var string
     */
    protected $uuid;

    /**
     * The current avatar of the user.
     *
     * @var AvatarObject
     */
    protected $avatar;

    /**
     * The current nickname of the user.
     *
     * @var string
     */
    protected $publicNickName;

    /**
     * The name to be displayed for this user, as it was given on the request.
     *
     * @var string
     */
    protected $displayName;

    /**
     * The country of the user. 000 stands for "unknown"
     *
     * @var string
     */
    protected $country;

    /**
     * The public UUID of the label-user.
     *
     * @var string
     */
    protected $uuidFieldForRequest;

    /**
     * The name to be displayed for this user, as it was given on the request.
     *
     * @var string
     */
    protected $displayNameFieldForRequest;

    /**
     * The country of the user
     *
     * @var string
     */
    protected $countryFieldForRequest;

    /**
     * @param string $uuid The public UUID of the label-user.
     * @param string $displayName The name to be displayed for this user, as it was given on the request.
     * @param string $country The country of the user
     */
    public function __construct(string  $uuid, string  $displayName, string  $country)
    {
        $this->uuidFieldForRequest = $uuid;
        $this->displayNameFieldForRequest = $displayName;
        $this->countryFieldForRequest = $country;
    }

    /**
     * The public UUID of the label-user.
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
     * The current avatar of the user.
     *
     * @return AvatarObject
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AvatarObject $avatar
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
    }

    /**
     * The current nickname of the user.
     *
     * @return string
     */
    public function getPublicNickName()
    {
        return $this->publicNickName;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $publicNickName
     */
    public function setPublicNickName($publicNickName)
    {
        $this->publicNickName = $publicNickName;
    }

    /**
     * The name to be displayed for this user, as it was given on the request.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $displayName
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }

    /**
     * The country of the user. 000 stands for "unknown"
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->uuid)) {
            return false;
        }

        if (!is_null($this->avatar)) {
            return false;
        }

        if (!is_null($this->publicNickName)) {
            return false;
        }

        if (!is_null($this->displayName)) {
            return false;
        }

        if (!is_null($this->country)) {
            return false;
        }

        return true;
    }
}
