<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Model\Core\BunqModel;

/**
 * The receipt the company employee has provided for a transaction.
 *
 * @generated
 */
class CompanyEmployeeCardReceiptApiObject extends BunqModel
{
    /**
     * Field constants.
     */
    const FIELD_STATUS = 'status';

    /**
     * The status of the receipt.
     *
     * @var string
     */
    protected $status;

    /**
     * Update the status of this receipt.
     *
     * @var string
     */
    protected $statusFieldForRequest;

    /**
     * @param string $status Update the status of this receipt.
     */
    public function __construct(string  $status)
    {
        $this->statusFieldForRequest = $status;
    }

    /**
     * The status of the receipt.
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
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->status)) {
            return false;
        }

        return true;
    }
}
