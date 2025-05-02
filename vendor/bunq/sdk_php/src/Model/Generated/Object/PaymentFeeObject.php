<?php
namespace bunq\Model\Generated\Object;

use bunq\Model\Core\BunqModel;

/**
 * @generated
 */
class PaymentFeeObject extends BunqModel
{
    /**
     * The amount formatted to two decimal places.
     *
     * @var string
     */
    protected $value;

    /**
     * The currency of the amount. It is an ISO 4217 formatted currency code.
     *
     * @var string
     */
    protected $currency;

    /**
     * The id of the invoice related to possible payment fee.
     *
     * @var int
     */
    protected $invoiceId;

    /**
     * The amount formatted to two decimal places.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * The currency of the amount. It is an ISO 4217 formatted currency code.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * The id of the invoice related to possible payment fee.
     *
     * @return int
     */
    public function getInvoiceId()
    {
        return $this->invoiceId;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $invoiceId
     */
    public function setInvoiceId($invoiceId)
    {
        $this->invoiceId = $invoiceId;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->value)) {
            return false;
        }

        if (!is_null($this->currency)) {
            return false;
        }

        if (!is_null($this->invoiceId)) {
            return false;
        }

        return true;
    }
}
