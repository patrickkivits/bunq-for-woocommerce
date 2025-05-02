<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AmountObject;

/**
 * Post processor for open banking account to be returned in the monetary account external post processor.
 *
 * @generated
 */
class OpenBankingAccountApiObject extends BunqModel
{
    /**
     * The status of this account.
     *
     * @var string
     */
    protected $status;

    /**
     * The iban of this account.
     *
     * @var string
     */
    protected $iban;

    /**
     * The timestamp of the last time the account was synced with our open banking partner.
     *
     * @var string
     */
    protected $timeSyncedLast;

    /**
     * The bank provider the account comes from.
     *
     * @var OpenBankingProviderBankApiObject
     */
    protected $providerBank;

    /**
     * The booked balance of the account.
     *
     * @var AmountObject
     */
    protected $balanceBooked;

    /**
     * The available balance of the account, if provided by the other bank.
     *
     * @var AmountObject
     */
    protected $balanceAvailable;

    /**
     * The status of this account.
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
     * The iban of this account.
     *
     * @return string
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $iban
     */
    public function setIban($iban)
    {
        $this->iban = $iban;
    }

    /**
     * The timestamp of the last time the account was synced with our open banking partner.
     *
     * @return string
     */
    public function getTimeSyncedLast()
    {
        return $this->timeSyncedLast;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $timeSyncedLast
     */
    public function setTimeSyncedLast($timeSyncedLast)
    {
        $this->timeSyncedLast = $timeSyncedLast;
    }

    /**
     * The bank provider the account comes from.
     *
     * @return OpenBankingProviderBankApiObject
     */
    public function getProviderBank()
    {
        return $this->providerBank;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param OpenBankingProviderBankApiObject $providerBank
     */
    public function setProviderBank($providerBank)
    {
        $this->providerBank = $providerBank;
    }

    /**
     * The booked balance of the account.
     *
     * @return AmountObject
     */
    public function getBalanceBooked()
    {
        return $this->balanceBooked;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $balanceBooked
     */
    public function setBalanceBooked($balanceBooked)
    {
        $this->balanceBooked = $balanceBooked;
    }

    /**
     * The available balance of the account, if provided by the other bank.
     *
     * @return AmountObject
     */
    public function getBalanceAvailable()
    {
        return $this->balanceAvailable;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $balanceAvailable
     */
    public function setBalanceAvailable($balanceAvailable)
    {
        $this->balanceAvailable = $balanceAvailable;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->iban)) {
            return false;
        }

        if (!is_null($this->timeSyncedLast)) {
            return false;
        }

        if (!is_null($this->providerBank)) {
            return false;
        }

        if (!is_null($this->balanceBooked)) {
            return false;
        }

        if (!is_null($this->balanceAvailable)) {
            return false;
        }

        return true;
    }
}
