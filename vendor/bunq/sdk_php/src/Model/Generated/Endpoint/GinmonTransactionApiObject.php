<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AmountObject;
use bunq\Model\Generated\Object\LabelMonetaryAccountObject;
use bunq\Model\Generated\Object\LabelUserObject;

/**
 * Endpoint for reading Ginmon transactions.
 *
 * @generated
 */
class GinmonTransactionApiObject extends BunqModel
{
    /**
     * The status of the transaction.
     *
     * @var string
     */
    protected $status;

    /**
     * The status of the transaction.
     *
     * @var string
     */
    protected $statusDescription;

    /**
     * The translated status of the transaction.
     *
     * @var string
     */
    protected $statusDescriptionTranslated;

    /**
     * The final amount the user will pay or receive.
     *
     * @var AmountObject
     */
    protected $amountBilling;

    /**
     * The estimated amount the user will pay or receive.
     *
     * @var AmountObject
     */
    protected $amountBillingOriginal;

    /**
     * The ISIN of the security.
     *
     * @var string
     */
    protected $isin;

    /**
     * External identifier of this order at Ginmon.
     *
     * @var string
     */
    protected $externalIdentifier;

    /**
     * The label of the user.
     *
     * @var LabelUserObject
     */
    protected $labelUser;

    /**
     * The label of the monetary account.
     *
     * @var LabelMonetaryAccountObject
     */
    protected $labelMonetaryAccount;

    /**
     * The label of the counter monetary account.
     *
     * @var LabelMonetaryAccountObject
     */
    protected $counterLabelMonetaryAccount;

    /**
     * The id of the event of transaction.
     *
     * @var int
     */
    protected $eventId;

    /**
     * The status of the transaction.
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
     * The status of the transaction.
     *
     * @return string
     */
    public function getStatusDescription()
    {
        return $this->statusDescription;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $statusDescription
     */
    public function setStatusDescription($statusDescription)
    {
        $this->statusDescription = $statusDescription;
    }

    /**
     * The translated status of the transaction.
     *
     * @return string
     */
    public function getStatusDescriptionTranslated()
    {
        return $this->statusDescriptionTranslated;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $statusDescriptionTranslated
     */
    public function setStatusDescriptionTranslated($statusDescriptionTranslated)
    {
        $this->statusDescriptionTranslated = $statusDescriptionTranslated;
    }

    /**
     * The final amount the user will pay or receive.
     *
     * @return AmountObject
     */
    public function getAmountBilling()
    {
        return $this->amountBilling;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountBilling
     */
    public function setAmountBilling($amountBilling)
    {
        $this->amountBilling = $amountBilling;
    }

    /**
     * The estimated amount the user will pay or receive.
     *
     * @return AmountObject
     */
    public function getAmountBillingOriginal()
    {
        return $this->amountBillingOriginal;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountBillingOriginal
     */
    public function setAmountBillingOriginal($amountBillingOriginal)
    {
        $this->amountBillingOriginal = $amountBillingOriginal;
    }

    /**
     * The ISIN of the security.
     *
     * @return string
     */
    public function getIsin()
    {
        return $this->isin;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $isin
     */
    public function setIsin($isin)
    {
        $this->isin = $isin;
    }

    /**
     * External identifier of this order at Ginmon.
     *
     * @return string
     */
    public function getExternalIdentifier()
    {
        return $this->externalIdentifier;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $externalIdentifier
     */
    public function setExternalIdentifier($externalIdentifier)
    {
        $this->externalIdentifier = $externalIdentifier;
    }

    /**
     * The label of the user.
     *
     * @return LabelUserObject
     */
    public function getLabelUser()
    {
        return $this->labelUser;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param LabelUserObject $labelUser
     */
    public function setLabelUser($labelUser)
    {
        $this->labelUser = $labelUser;
    }

    /**
     * The label of the monetary account.
     *
     * @return LabelMonetaryAccountObject
     */
    public function getLabelMonetaryAccount()
    {
        return $this->labelMonetaryAccount;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param LabelMonetaryAccountObject $labelMonetaryAccount
     */
    public function setLabelMonetaryAccount($labelMonetaryAccount)
    {
        $this->labelMonetaryAccount = $labelMonetaryAccount;
    }

    /**
     * The label of the counter monetary account.
     *
     * @return LabelMonetaryAccountObject
     */
    public function getCounterLabelMonetaryAccount()
    {
        return $this->counterLabelMonetaryAccount;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param LabelMonetaryAccountObject $counterLabelMonetaryAccount
     */
    public function setCounterLabelMonetaryAccount($counterLabelMonetaryAccount)
    {
        $this->counterLabelMonetaryAccount = $counterLabelMonetaryAccount;
    }

    /**
     * The id of the event of transaction.
     *
     * @return int
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $eventId
     */
    public function setEventId($eventId)
    {
        $this->eventId = $eventId;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->statusDescription)) {
            return false;
        }

        if (!is_null($this->statusDescriptionTranslated)) {
            return false;
        }

        if (!is_null($this->amountBilling)) {
            return false;
        }

        if (!is_null($this->amountBillingOriginal)) {
            return false;
        }

        if (!is_null($this->isin)) {
            return false;
        }

        if (!is_null($this->externalIdentifier)) {
            return false;
        }

        if (!is_null($this->labelUser)) {
            return false;
        }

        if (!is_null($this->labelMonetaryAccount)) {
            return false;
        }

        if (!is_null($this->counterLabelMonetaryAccount)) {
            return false;
        }

        if (!is_null($this->eventId)) {
            return false;
        }

        return true;
    }
}
