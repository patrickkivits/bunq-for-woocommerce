<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AttachmentObject;
use bunq\Model\Generated\Object\LabelMonetaryAccountObject;
use bunq\Model\Generated\Object\LabelUserObject;
use bunq\Model\Generated\Object\PointerObject;

/**
 * Endpoint for using the Equens Bank Switch Service.
 *
 * @generated
 */
class BankSwitchServiceNetherlandsIncomingApiObject extends BunqModel
{
    /**
     * Field constants.
     */
    const FIELD_ALIAS = 'alias';
    const FIELD_COUNTERPARTY_ALIAS = 'counterparty_alias';
    const FIELD_STATUS = 'status';

    /**
     * The label of the user creator of this switch service.
     *
     * @var LabelUserObject
     */
    protected $userAlias;

    /**
     * The label of the monetary of this switch service.
     *
     * @var LabelMonetaryAccountObject
     */
    protected $alias;

    /**
     * The IBAN alias that's displayed for this switch service.
     *
     * @var LabelMonetaryAccountObject
     */
    protected $counterpartyAlias;

    /**
     * The status of the switch service.
     *
     * @var string
     */
    protected $status;

    /**
     * The sub status of the switch service.
     *
     * @var string
     */
    protected $subStatus;

    /**
     * The timestamp when the switch service desired to be start.
     *
     * @var string
     */
    protected $timeStartDesired;

    /**
     * The timestamp when the switch service actually starts.
     *
     * @var string
     */
    protected $timeStartActual;

    /**
     * The timestamp when the switch service ends.
     *
     * @var string
     */
    protected $timeEnd;

    /**
     * Reference to the bank transfer form for this switch-service.
     *
     * @var AttachmentObject
     */
    protected $attachment;

    /**
     * Rejection reason enum.
     *
     * @var string
     */
    protected $rejectionReason;

    /**
     * Rejection reason description to be shown to the user.
     *
     * @var string
     */
    protected $rejectionReasonDescription;

    /**
     * Rejection reason description to be shown to the user, translated.
     *
     * @var string
     */
    protected $rejectionReasonDescriptionTranslated;

    /**
     * Rejection reason together URL.
     *
     * @var string
     */
    protected $rejectionReasonTogetherUrl;

    /**
     * The alias of the Monetary Account this switch service is for.
     *
     * @var PointerObject
     */
    protected $aliasFieldForRequest;

    /**
     * The Alias of the party we are switching from. Can only be an Alias of type IBAN (external bank account).
     *
     * @var PointerObject
     */
    protected $counterpartyAliasFieldForRequest;

    /**
     * The status of the switch service. Ignored in POST requests (always set to REQUESTED) can be CANCELLED in PUT requests to
     * cancel the switch service. Admin can set this to ACCEPTED, or REJECTED.
     *
     * @var string|null
     */
    protected $statusFieldForRequest;

    /**
     * @param PointerObject $alias The alias of the Monetary Account this switch service is for.
     * @param PointerObject $counterpartyAlias The Alias of the party we are switching from. Can only be an Alias of type IBAN
     * (external bank account).
     * @param string|null $status The status of the switch service. Ignored in POST requests (always set to REQUESTED) can be
     * CANCELLED in PUT requests to cancel the switch service. Admin can set this to ACCEPTED, or REJECTED.
     */
    public function __construct(PointerObject  $alias, PointerObject  $counterpartyAlias, string  $status = null)
    {
        $this->aliasFieldForRequest = $alias;
        $this->counterpartyAliasFieldForRequest = $counterpartyAlias;
        $this->statusFieldForRequest = $status;
    }

    /**
     * The label of the user creator of this switch service.
     *
     * @return LabelUserObject
     */
    public function getUserAlias()
    {
        return $this->userAlias;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param LabelUserObject $userAlias
     */
    public function setUserAlias($userAlias)
    {
        $this->userAlias = $userAlias;
    }

    /**
     * The label of the monetary of this switch service.
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
     * The IBAN alias that's displayed for this switch service.
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
     * The status of the switch service.
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
     * The sub status of the switch service.
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
     * The timestamp when the switch service desired to be start.
     *
     * @return string
     */
    public function getTimeStartDesired()
    {
        return $this->timeStartDesired;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $timeStartDesired
     */
    public function setTimeStartDesired($timeStartDesired)
    {
        $this->timeStartDesired = $timeStartDesired;
    }

    /**
     * The timestamp when the switch service actually starts.
     *
     * @return string
     */
    public function getTimeStartActual()
    {
        return $this->timeStartActual;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $timeStartActual
     */
    public function setTimeStartActual($timeStartActual)
    {
        $this->timeStartActual = $timeStartActual;
    }

    /**
     * The timestamp when the switch service ends.
     *
     * @return string
     */
    public function getTimeEnd()
    {
        return $this->timeEnd;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $timeEnd
     */
    public function setTimeEnd($timeEnd)
    {
        $this->timeEnd = $timeEnd;
    }

    /**
     * Reference to the bank transfer form for this switch-service.
     *
     * @return AttachmentObject
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AttachmentObject $attachment
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * Rejection reason enum.
     *
     * @return string
     */
    public function getRejectionReason()
    {
        return $this->rejectionReason;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $rejectionReason
     */
    public function setRejectionReason($rejectionReason)
    {
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * Rejection reason description to be shown to the user.
     *
     * @return string
     */
    public function getRejectionReasonDescription()
    {
        return $this->rejectionReasonDescription;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $rejectionReasonDescription
     */
    public function setRejectionReasonDescription($rejectionReasonDescription)
    {
        $this->rejectionReasonDescription = $rejectionReasonDescription;
    }

    /**
     * Rejection reason description to be shown to the user, translated.
     *
     * @return string
     */
    public function getRejectionReasonDescriptionTranslated()
    {
        return $this->rejectionReasonDescriptionTranslated;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $rejectionReasonDescriptionTranslated
     */
    public function setRejectionReasonDescriptionTranslated($rejectionReasonDescriptionTranslated)
    {
        $this->rejectionReasonDescriptionTranslated = $rejectionReasonDescriptionTranslated;
    }

    /**
     * Rejection reason together URL.
     *
     * @return string
     */
    public function getRejectionReasonTogetherUrl()
    {
        return $this->rejectionReasonTogetherUrl;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $rejectionReasonTogetherUrl
     */
    public function setRejectionReasonTogetherUrl($rejectionReasonTogetherUrl)
    {
        $this->rejectionReasonTogetherUrl = $rejectionReasonTogetherUrl;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->userAlias)) {
            return false;
        }

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

        if (!is_null($this->timeStartDesired)) {
            return false;
        }

        if (!is_null($this->timeStartActual)) {
            return false;
        }

        if (!is_null($this->timeEnd)) {
            return false;
        }

        if (!is_null($this->attachment)) {
            return false;
        }

        if (!is_null($this->rejectionReason)) {
            return false;
        }

        if (!is_null($this->rejectionReasonDescription)) {
            return false;
        }

        if (!is_null($this->rejectionReasonDescriptionTranslated)) {
            return false;
        }

        if (!is_null($this->rejectionReasonTogetherUrl)) {
            return false;
        }

        return true;
    }
}
