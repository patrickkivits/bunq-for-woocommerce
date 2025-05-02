<?php
namespace bunq\Model\Generated\Object;

use bunq\Exception\BunqException;
use bunq\Model\Core\AnchorObjectInterface;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Endpoint\PaymentApiObject;
use bunq\Model\Generated\Endpoint\PaymentBatchApiObject;

/**
 * @generated
 */
class ScheduleInstanceAnchorObjectObject extends BunqModel implements AnchorObjectInterface
{
    /**
     * Error constants.
     */
    const ERROR_NULL_FIELDS = 'All fields of an extended model or object are null.';

    /**
     * @var PaymentApiObject|null
     */
    protected $payment;

    /**
     * @var PaymentBatchApiObject|null
     */
    protected $paymentBatch;

    /**
     * @return PaymentApiObject
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param PaymentApiObject $payment
     */
    public function setPayment($payment)
    {
        $this->payment = $payment;
    }

    /**
     * @return PaymentBatchApiObject
     */
    public function getPaymentBatch()
    {
        return $this->paymentBatch;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param PaymentBatchApiObject $paymentBatch
     */
    public function setPaymentBatch($paymentBatch)
    {
        $this->paymentBatch = $paymentBatch;
    }

    /**
     * @return BunqModel
     * @throws BunqException
     */
    public function getReferencedObject()
    {
        if (!is_null($this->payment)) {
            return $this->payment;
        }

        if (!is_null($this->paymentBatch)) {
            return $this->paymentBatch;
        }

        throw new BunqException(self::ERROR_NULL_FIELDS);
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->payment)) {
            return false;
        }

        if (!is_null($this->paymentBatch)) {
            return false;
        }

        return true;
    }
}
