<?php
namespace bunq\Model\Generated\Object;

use bunq\Model\Core\BunqModel;

/**
 * @generated
 */
class MonetaryAccountProfileDrainObject extends BunqModel
{
    /**
     * The status of the profile.
     *
     * @var string
     */
    protected $status;

    /**
     * The goal balance.
     *
     * @var AmountObject
     */
    protected $balancePreferred;

    /**
     * The high threshold balance.
     *
     * @var AmountObject
     */
    protected $balanceThresholdHigh;

    /**
     * The savings monetary account.
     *
     * @var LabelMonetaryAccountObject
     */
    protected $savingsAccountAlias;

    /**
     * The status of the profile.
     *
     * @var string
     */
    protected $statusFieldForRequest;

    /**
     * The goal balance.
     *
     * @var AmountObject
     */
    protected $balancePreferredFieldForRequest;

    /**
     * The high threshold balance.
     *
     * @var AmountObject
     */
    protected $balanceThresholdHighFieldForRequest;

    /**
     * The savings monetary account.
     *
     * @var PointerObject
     */
    protected $savingsAccountAliasFieldForRequest;

    /**
     * @param string $status The status of the profile.
     * @param AmountObject $balancePreferred The goal balance.
     * @param AmountObject $balanceThresholdHigh The high threshold balance.
     * @param PointerObject $savingsAccountAlias The savings monetary account.
     */
    public function __construct(string  $status, AmountObject  $balancePreferred, AmountObject  $balanceThresholdHigh, PointerObject  $savingsAccountAlias)
    {
        $this->statusFieldForRequest = $status;
        $this->balancePreferredFieldForRequest = $balancePreferred;
        $this->balanceThresholdHighFieldForRequest = $balanceThresholdHigh;
        $this->savingsAccountAliasFieldForRequest = $savingsAccountAlias;
    }

    /**
     * The status of the profile.
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
     * The goal balance.
     *
     * @return AmountObject
     */
    public function getBalancePreferred()
    {
        return $this->balancePreferred;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $balancePreferred
     */
    public function setBalancePreferred($balancePreferred)
    {
        $this->balancePreferred = $balancePreferred;
    }

    /**
     * The high threshold balance.
     *
     * @return AmountObject
     */
    public function getBalanceThresholdHigh()
    {
        return $this->balanceThresholdHigh;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $balanceThresholdHigh
     */
    public function setBalanceThresholdHigh($balanceThresholdHigh)
    {
        $this->balanceThresholdHigh = $balanceThresholdHigh;
    }

    /**
     * The savings monetary account.
     *
     * @return LabelMonetaryAccountObject
     */
    public function getSavingsAccountAlias()
    {
        return $this->savingsAccountAlias;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param LabelMonetaryAccountObject $savingsAccountAlias
     */
    public function setSavingsAccountAlias($savingsAccountAlias)
    {
        $this->savingsAccountAlias = $savingsAccountAlias;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->balancePreferred)) {
            return false;
        }

        if (!is_null($this->balanceThresholdHigh)) {
            return false;
        }

        if (!is_null($this->savingsAccountAlias)) {
            return false;
        }

        return true;
    }
}
