<?php
namespace bunq\Model\Generated\Object;

use bunq\Model\Core\BunqModel;

/**
 * @generated
 */
class MonetaryAccountProfileFillObject extends BunqModel
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
     * The low threshold balance.
     *
     * @var AmountObject
     */
    protected $balanceThresholdLow;

    /**
     * The bank the fill is supposed to happen from, with BIC and bank name.
     *
     * @var IssuerObject
     */
    protected $issuer;

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
     * The low threshold balance.
     *
     * @var AmountObject
     */
    protected $balanceThresholdLowFieldForRequest;

    /**
     * The bank the fill is supposed to happen from, with BIC and bank name.
     *
     * @var IssuerObject|null
     */
    protected $issuerFieldForRequest;

    /**
     * @param string $status The status of the profile.
     * @param AmountObject $balancePreferred The goal balance.
     * @param AmountObject $balanceThresholdLow The low threshold balance.
     * @param IssuerObject|null $issuer The bank the fill is supposed to happen from, with BIC and bank name.
     */
    public function __construct(string  $status, AmountObject  $balancePreferred, AmountObject  $balanceThresholdLow, IssuerObject  $issuer = null)
    {
        $this->statusFieldForRequest = $status;
        $this->balancePreferredFieldForRequest = $balancePreferred;
        $this->balanceThresholdLowFieldForRequest = $balanceThresholdLow;
        $this->issuerFieldForRequest = $issuer;
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
     * The low threshold balance.
     *
     * @return AmountObject
     */
    public function getBalanceThresholdLow()
    {
        return $this->balanceThresholdLow;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $balanceThresholdLow
     */
    public function setBalanceThresholdLow($balanceThresholdLow)
    {
        $this->balanceThresholdLow = $balanceThresholdLow;
    }

    /**
     * The bank the fill is supposed to happen from, with BIC and bank name.
     *
     * @return IssuerObject
     */
    public function getIssuer()
    {
        return $this->issuer;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param IssuerObject $issuer
     */
    public function setIssuer($issuer)
    {
        $this->issuer = $issuer;
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

        if (!is_null($this->balanceThresholdLow)) {
            return false;
        }

        if (!is_null($this->issuer)) {
            return false;
        }

        return true;
    }
}
