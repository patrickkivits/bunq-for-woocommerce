<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AmountObject;

/**
 * Cashback payout item details.
 *
 * @generated
 */
class CashbackPayoutItemApiObject extends BunqModel
{
    /**
     * The status of the cashback payout item.
     *
     * @var string
     */
    protected $status;

    /**
     * The amount of cashback earned.
     *
     * @var AmountObject
     */
    protected $amount;

    /**
     * The cashback rate.
     *
     * @var string
     */
    protected $rateApplied;

    /**
     * The transaction category that this cashback is for.
     *
     * @var AdditionalTransactionInformationCategoryApiObject
     */
    protected $transactionCategory;

    /**
     * The partner promotion that this cashback is for.
     *
     * @var UserPartnerPromotionCashbackApiObject
     */
    protected $userPartnerPromotion;

    /**
     * The status of the cashback payout item.
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
     * The amount of cashback earned.
     *
     * @return AmountObject
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * The cashback rate.
     *
     * @return string
     */
    public function getRateApplied()
    {
        return $this->rateApplied;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $rateApplied
     */
    public function setRateApplied($rateApplied)
    {
        $this->rateApplied = $rateApplied;
    }

    /**
     * The transaction category that this cashback is for.
     *
     * @return AdditionalTransactionInformationCategoryApiObject
     */
    public function getTransactionCategory()
    {
        return $this->transactionCategory;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AdditionalTransactionInformationCategoryApiObject $transactionCategory
     */
    public function setTransactionCategory($transactionCategory)
    {
        $this->transactionCategory = $transactionCategory;
    }

    /**
     * The partner promotion that this cashback is for.
     *
     * @return UserPartnerPromotionCashbackApiObject
     */
    public function getUserPartnerPromotion()
    {
        return $this->userPartnerPromotion;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param UserPartnerPromotionCashbackApiObject $userPartnerPromotion
     */
    public function setUserPartnerPromotion($userPartnerPromotion)
    {
        $this->userPartnerPromotion = $userPartnerPromotion;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->amount)) {
            return false;
        }

        if (!is_null($this->rateApplied)) {
            return false;
        }

        if (!is_null($this->transactionCategory)) {
            return false;
        }

        if (!is_null($this->userPartnerPromotion)) {
            return false;
        }

        return true;
    }
}
