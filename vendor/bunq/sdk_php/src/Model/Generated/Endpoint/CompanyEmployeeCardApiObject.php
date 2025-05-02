<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AmountObject;
use bunq\Model\Generated\Object\PointerObject;

/**
 * Manage cards for company employees.
 *
 * @generated
 */
class CompanyEmployeeCardApiObject extends BunqModel
{
    /**
     * Field constants.
     */
    const FIELD_POINTER_COUNTER_USER = 'pointer_counter_user';
    const FIELD_POINTER_MONETARY_ACCOUNT = 'pointer_monetary_account';
    const FIELD_TYPE = 'type';
    const FIELD_PRODUCT_TYPE = 'product_type';
    const FIELD_COMPANY_NAME_ON_CARD = 'company_name_on_card';
    const FIELD_EMPLOYEE_NAME_ON_CARD = 'employee_name_on_card';
    const FIELD_EMPLOYEE_PREFERRED_NAME_ON_CARD = 'employee_preferred_name_on_card';
    const FIELD_AMOUNT_LIMIT_MONTHLY = 'amount_limit_monthly';
    const FIELD_STATUS = 'status';

    /**
     * The actual card.
     *
     * @var CardApiObject
     */
    protected $card;

    /**
     * The status of the employee card.
     *
     * @var string
     */
    protected $status;

    /**
     * The name of the company that should be displayed on the card.
     *
     * @var string
     */
    protected $companyNameOnCard;

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
     * The number of transactions that still need a receipt.
     *
     * @var int
     */
    protected $numberOfCompanyEmployeeCardReceiptPending;

    /**
     * The company employee's monthly cumulative card limit.
     *
     * @var CompanyEmployeeCardLimitApiObject
     */
    protected $companyEmployeeCardLimit;

    /**
     * The pointer to the employee for which you want to create a card.
     *
     * @var PointerObject
     */
    protected $pointerCounterUserFieldForRequest;

    /**
     * The pointer to the monetary account that will be connected at first with the card.
     *
     * @var PointerObject
     */
    protected $pointerMonetaryAccountFieldForRequest;

    /**
     * The type of card to order.
     *
     * @var string
     */
    protected $typeFieldForRequest;

    /**
     * The product type of the card to order.
     *
     * @var string
     */
    protected $productTypeFieldForRequest;

    /**
     * The name of the company that should be displayed on the card.
     *
     * @var string
     */
    protected $companyNameOnCardFieldForRequest;

    /**
     * The name of the employee that should be displayed on the card.
     *
     * @var string|null
     */
    protected $employeeNameOnCardFieldForRequest;

    /**
     * The user's preferred name as it will be on the card.
     *
     * @var string|null
     */
    protected $employeePreferredNameOnCardFieldForRequest;

    /**
     * The monthly spending limit for this employee on the card.
     *
     * @var AmountObject|null
     */
    protected $amountLimitMonthlyFieldForRequest;

    /**
     * The status of the employee card.
     *
     * @var string|null
     */
    protected $statusFieldForRequest;

    /**
     * @param PointerObject $pointerCounterUser The pointer to the employee for which you want to create a card.
     * @param PointerObject $pointerMonetaryAccount The pointer to the monetary account that will be connected at first with
     * the card.
     * @param string $type The type of card to order.
     * @param string $productType The product type of the card to order.
     * @param string $companyNameOnCard The name of the company that should be displayed on the card.
     * @param string|null $employeeNameOnCard The name of the employee that should be displayed on the card.
     * @param string|null $employeePreferredNameOnCard The user's preferred name as it will be on the card.
     * @param AmountObject|null $amountLimitMonthly The monthly spending limit for this employee on the card.
     * @param string|null $status The status of the employee card.
     */
    public function __construct(PointerObject  $pointerCounterUser, PointerObject  $pointerMonetaryAccount, string  $type, string  $productType, string  $companyNameOnCard, string  $employeeNameOnCard = null, string  $employeePreferredNameOnCard = null, AmountObject  $amountLimitMonthly = null, string  $status = null)
    {
        $this->pointerCounterUserFieldForRequest = $pointerCounterUser;
        $this->pointerMonetaryAccountFieldForRequest = $pointerMonetaryAccount;
        $this->typeFieldForRequest = $type;
        $this->productTypeFieldForRequest = $productType;
        $this->companyNameOnCardFieldForRequest = $companyNameOnCard;
        $this->employeeNameOnCardFieldForRequest = $employeeNameOnCard;
        $this->employeePreferredNameOnCardFieldForRequest = $employeePreferredNameOnCard;
        $this->amountLimitMonthlyFieldForRequest = $amountLimitMonthly;
        $this->statusFieldForRequest = $status;
    }

    /**
     * The actual card.
     *
     * @return CardApiObject
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param CardApiObject $card
     */
    public function setCard($card)
    {
        $this->card = $card;
    }

    /**
     * The status of the employee card.
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
     * The name of the company that should be displayed on the card.
     *
     * @return string
     */
    public function getCompanyNameOnCard()
    {
        return $this->companyNameOnCard;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $companyNameOnCard
     */
    public function setCompanyNameOnCard($companyNameOnCard)
    {
        $this->companyNameOnCard = $companyNameOnCard;
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
     * The number of transactions that still need a receipt.
     *
     * @return int
     */
    public function getNumberOfCompanyEmployeeCardReceiptPending()
    {
        return $this->numberOfCompanyEmployeeCardReceiptPending;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $numberOfCompanyEmployeeCardReceiptPending
     */
    public function setNumberOfCompanyEmployeeCardReceiptPending($numberOfCompanyEmployeeCardReceiptPending)
    {
        $this->numberOfCompanyEmployeeCardReceiptPending = $numberOfCompanyEmployeeCardReceiptPending;
    }

    /**
     * The company employee's monthly cumulative card limit.
     *
     * @return CompanyEmployeeCardLimitApiObject
     */
    public function getCompanyEmployeeCardLimit()
    {
        return $this->companyEmployeeCardLimit;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param CompanyEmployeeCardLimitApiObject $companyEmployeeCardLimit
     */
    public function setCompanyEmployeeCardLimit($companyEmployeeCardLimit)
    {
        $this->companyEmployeeCardLimit = $companyEmployeeCardLimit;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->card)) {
            return false;
        }

        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->companyNameOnCard)) {
            return false;
        }

        if (!is_null($this->amountLimitMonthly)) {
            return false;
        }

        if (!is_null($this->amountSpentMonthly)) {
            return false;
        }

        if (!is_null($this->numberOfCompanyEmployeeCardReceiptPending)) {
            return false;
        }

        if (!is_null($this->companyEmployeeCardLimit)) {
            return false;
        }

        return true;
    }
}
