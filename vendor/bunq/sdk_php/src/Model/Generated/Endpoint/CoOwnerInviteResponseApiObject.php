<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\CoOwnerObject;
use bunq\Model\Generated\Object\LabelMonetaryAccountObject;
use bunq\Model\Generated\Object\LabelUserObject;

/**
 * Used to accept or reject a monetaryAccountJoint co-ownership.
 *
 * @generated
 */
class CoOwnerInviteResponseApiObject extends BunqModel
{
    /**
     * Field constants.
     */
    const FIELD_STATUS = 'status';

    /**
     * The monetary account and user who get the invite for the joint account.
     *
     * @var LabelMonetaryAccountObject
     */
    protected $alias;

    /**
     * The monetary account and user who created the joint account.
     *
     * @var LabelMonetaryAccountObject
     */
    protected $counterAlias;

    /**
     * The ID of the monetaryAccount
     *
     * @var int
     */
    protected $monetaryAccountId;

    /**
     * The extension type of the monetaryAccount
     *
     * @var string
     */
    protected $monetaryAccountType;

    /**
     * The status of the invite. Can be PENDING, REVOKED (the user deletes the share inquiry before it's accepted) or ACCEPTED
     *
     * @var string
     */
    protected $status;

    /**
     * The freeze_status of the invite.
     *
     * @var string
     */
    protected $freezeStatus;

    /**
     * The label of the user that froze the coowner invite (if frozen).
     *
     * @var LabelUserObject
     */
    protected $labelFreezeUser;

    /**
     * The users the account will be joint with.
     *
     * @var CoOwnerObject[]
     */
    protected $allCoOwner;

    /**
     * The status of the co-owner invite, can be ACCEPTED or REJECTED.
     *
     * @var string|null
     */
    protected $statusFieldForRequest;

    /**
     * @param string|null $status The status of the co-owner invite, can be ACCEPTED or REJECTED.
     */
    public function __construct(string  $status = null)
    {
        $this->statusFieldForRequest = $status;
    }

    /**
     * The monetary account and user who get the invite for the joint account.
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
     * The monetary account and user who created the joint account.
     *
     * @return LabelMonetaryAccountObject
     */
    public function getCounterAlias()
    {
        return $this->counterAlias;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param LabelMonetaryAccountObject $counterAlias
     */
    public function setCounterAlias($counterAlias)
    {
        $this->counterAlias = $counterAlias;
    }

    /**
     * The ID of the monetaryAccount
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
     * The extension type of the monetaryAccount
     *
     * @return string
     */
    public function getMonetaryAccountType()
    {
        return $this->monetaryAccountType;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $monetaryAccountType
     */
    public function setMonetaryAccountType($monetaryAccountType)
    {
        $this->monetaryAccountType = $monetaryAccountType;
    }

    /**
     * The status of the invite. Can be PENDING, REVOKED (the user deletes the share inquiry before it's accepted) or ACCEPTED
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
     * The freeze_status of the invite.
     *
     * @return string
     */
    public function getFreezeStatus()
    {
        return $this->freezeStatus;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $freezeStatus
     */
    public function setFreezeStatus($freezeStatus)
    {
        $this->freezeStatus = $freezeStatus;
    }

    /**
     * The label of the user that froze the coowner invite (if frozen).
     *
     * @return LabelUserObject
     */
    public function getLabelFreezeUser()
    {
        return $this->labelFreezeUser;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param LabelUserObject $labelFreezeUser
     */
    public function setLabelFreezeUser($labelFreezeUser)
    {
        $this->labelFreezeUser = $labelFreezeUser;
    }

    /**
     * The users the account will be joint with.
     *
     * @return CoOwnerObject[]
     */
    public function getAllCoOwner()
    {
        return $this->allCoOwner;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param CoOwnerObject[] $allCoOwner
     */
    public function setAllCoOwner($allCoOwner)
    {
        $this->allCoOwner = $allCoOwner;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->alias)) {
            return false;
        }

        if (!is_null($this->counterAlias)) {
            return false;
        }

        if (!is_null($this->monetaryAccountId)) {
            return false;
        }

        if (!is_null($this->monetaryAccountType)) {
            return false;
        }

        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->freezeStatus)) {
            return false;
        }

        if (!is_null($this->labelFreezeUser)) {
            return false;
        }

        if (!is_null($this->allCoOwner)) {
            return false;
        }

        return true;
    }
}
