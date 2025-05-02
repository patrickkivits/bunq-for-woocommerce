<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AmountObject;

/**
 * Endpoint for interacting with the birdee investment portfolio balance..
 *
 * @generated
 */
class BirdeeInvestmentPortfolioBalanceApiObject extends BunqModel
{
    /**
     * The current valuation of the portfolio, minus any amount pending withdrawal.
     *
     * @var AmountObject
     */
    protected $amountAvailable;

    /**
     * The total amount deposited.
     *
     * @var AmountObject
     */
    protected $amountDepositTotal;

    /**
     * The total amount withdrawn.
     *
     * @var AmountObject
     */
    protected $amountWithdrawalTotal;

    /**
     * The total fee amount.
     *
     * @var AmountObject
     */
    protected $amountFeeTotal;

    /**
     * The difference between the netto deposited amount and the current valuation.
     *
     * @var AmountObject
     */
    protected $amountProfit;

    /**
     * The amount that's sent to Birdee, but pending investment on the portfolio.
     *
     * @var AmountObject
     */
    protected $amountDepositPending;

    /**
     * The amount that's sent to Birdee, but pending withdrawal.
     *
     * @var AmountObject
     */
    protected $amountWithdrawalPending;

    /**
     * The current valuation of the portfolio, minus any amount pending withdrawal.
     *
     * @return AmountObject
     */
    public function getAmountAvailable()
    {
        return $this->amountAvailable;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountAvailable
     */
    public function setAmountAvailable($amountAvailable)
    {
        $this->amountAvailable = $amountAvailable;
    }

    /**
     * The total amount deposited.
     *
     * @return AmountObject
     */
    public function getAmountDepositTotal()
    {
        return $this->amountDepositTotal;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountDepositTotal
     */
    public function setAmountDepositTotal($amountDepositTotal)
    {
        $this->amountDepositTotal = $amountDepositTotal;
    }

    /**
     * The total amount withdrawn.
     *
     * @return AmountObject
     */
    public function getAmountWithdrawalTotal()
    {
        return $this->amountWithdrawalTotal;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountWithdrawalTotal
     */
    public function setAmountWithdrawalTotal($amountWithdrawalTotal)
    {
        $this->amountWithdrawalTotal = $amountWithdrawalTotal;
    }

    /**
     * The total fee amount.
     *
     * @return AmountObject
     */
    public function getAmountFeeTotal()
    {
        return $this->amountFeeTotal;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountFeeTotal
     */
    public function setAmountFeeTotal($amountFeeTotal)
    {
        $this->amountFeeTotal = $amountFeeTotal;
    }

    /**
     * The difference between the netto deposited amount and the current valuation.
     *
     * @return AmountObject
     */
    public function getAmountProfit()
    {
        return $this->amountProfit;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountProfit
     */
    public function setAmountProfit($amountProfit)
    {
        $this->amountProfit = $amountProfit;
    }

    /**
     * The amount that's sent to Birdee, but pending investment on the portfolio.
     *
     * @return AmountObject
     */
    public function getAmountDepositPending()
    {
        return $this->amountDepositPending;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountDepositPending
     */
    public function setAmountDepositPending($amountDepositPending)
    {
        $this->amountDepositPending = $amountDepositPending;
    }

    /**
     * The amount that's sent to Birdee, but pending withdrawal.
     *
     * @return AmountObject
     */
    public function getAmountWithdrawalPending()
    {
        return $this->amountWithdrawalPending;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountWithdrawalPending
     */
    public function setAmountWithdrawalPending($amountWithdrawalPending)
    {
        $this->amountWithdrawalPending = $amountWithdrawalPending;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->amountAvailable)) {
            return false;
        }

        if (!is_null($this->amountDepositTotal)) {
            return false;
        }

        if (!is_null($this->amountWithdrawalTotal)) {
            return false;
        }

        if (!is_null($this->amountFeeTotal)) {
            return false;
        }

        if (!is_null($this->amountProfit)) {
            return false;
        }

        if (!is_null($this->amountDepositPending)) {
            return false;
        }

        if (!is_null($this->amountWithdrawalPending)) {
            return false;
        }

        return true;
    }
}
