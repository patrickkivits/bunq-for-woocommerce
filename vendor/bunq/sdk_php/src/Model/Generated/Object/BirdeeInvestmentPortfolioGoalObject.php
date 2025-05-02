<?php
namespace bunq\Model\Generated\Object;

use bunq\Model\Core\BunqModel;

/**
 * @generated
 */
class BirdeeInvestmentPortfolioGoalObject extends BunqModel
{
    /**
     * The investment goal amount.
     *
     * @var AmountObject
     */
    protected $amountTarget;

    /**
     * The investment goal end time.
     *
     * @var string
     */
    protected $timeEnd;

    /**
     * The investment goal amount.
     *
     * @var AmountObject|null
     */
    protected $amountTargetFieldForRequest;

    /**
     * The investment goal end time.
     *
     * @var string|null
     */
    protected $timeEndFieldForRequest;

    /**
     * @param AmountObject|null $amountTarget The investment goal amount.
     * @param string|null $timeEnd The investment goal end time.
     */
    public function __construct(AmountObject  $amountTarget = null, string  $timeEnd = null)
    {
        $this->amountTargetFieldForRequest = $amountTarget;
        $this->timeEndFieldForRequest = $timeEnd;
    }

    /**
     * The investment goal amount.
     *
     * @return AmountObject
     */
    public function getAmountTarget()
    {
        return $this->amountTarget;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountTarget
     */
    public function setAmountTarget($amountTarget)
    {
        $this->amountTarget = $amountTarget;
    }

    /**
     * The investment goal end time.
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
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->amountTarget)) {
            return false;
        }

        if (!is_null($this->timeEnd)) {
            return false;
        }

        return true;
    }
}
