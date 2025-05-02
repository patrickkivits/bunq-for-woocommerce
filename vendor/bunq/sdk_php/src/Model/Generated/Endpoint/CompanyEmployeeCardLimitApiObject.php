<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AmountObject;

/**
 * Manage the card limit for company employees.
 *
 * @generated
 */
class CompanyEmployeeCardLimitApiObject extends BunqModel
{
    /**
     * Field constants.
     */
    const FIELD_AMOUNT_LIMIT_MONTHLY = 'amount_limit_monthly';

    /**
     * Company item id.
     *
     * @var int
     */
    protected $userCompanyId;

    /**
     * Company employee item id.
     *
     * @var int
     */
    protected $userEmployeeId;

    /**
     * The monthly spending limit for this employee on the card.
     *
     * @var AmountObject
     */
    protected $amountLimitMonthly;

    /**
     * The monthly spend for this employee on the card.
     *
     * @var AmountObject
     */
    protected $amountSpentMonthly;

    /**
     * The monthly spending limit for this employee on the card.
     *
     * @var AmountObject|null
     */
    protected $amountLimitMonthlyFieldForRequest;

    /**
     * @param AmountObject|null $amountLimitMonthly The monthly spending limit for this employee on the card.
     */
    public function __construct(AmountObject  $amountLimitMonthly = null)
    {
        $this->amountLimitMonthlyFieldForRequest = $amountLimitMonthly;
    }

    /**
     * Company item id.
     *
     * @return int
     */
    public function getUserCompanyId()
    {
        return $this->userCompanyId;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $userCompanyId
     */
    public function setUserCompanyId($userCompanyId)
    {
        $this->userCompanyId = $userCompanyId;
    }

    /**
     * Company employee item id.
     *
     * @return int
     */
    public function getUserEmployeeId()
    {
        return $this->userEmployeeId;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $userEmployeeId
     */
    public function setUserEmployeeId($userEmployeeId)
    {
        $this->userEmployeeId = $userEmployeeId;
    }

    /**
     * The monthly spending limit for this employee on the card.
     *
     * @return AmountObject
     */
    public function getAmountLimitMonthly()
    {
        return $this->amountLimitMonthly;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountLimitMonthly
     */
    public function setAmountLimitMonthly($amountLimitMonthly)
    {
        $this->amountLimitMonthly = $amountLimitMonthly;
    }

    /**
     * The monthly spend for this employee on the card.
     *
     * @return AmountObject
     */
    public function getAmountSpentMonthly()
    {
        return $this->amountSpentMonthly;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountSpentMonthly
     */
    public function setAmountSpentMonthly($amountSpentMonthly)
    {
        $this->amountSpentMonthly = $amountSpentMonthly;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->userCompanyId)) {
            return false;
        }

        if (!is_null($this->userEmployeeId)) {
            return false;
        }

        if (!is_null($this->amountLimitMonthly)) {
            return false;
        }

        if (!is_null($this->amountSpentMonthly)) {
            return false;
        }

        return true;
    }
}
