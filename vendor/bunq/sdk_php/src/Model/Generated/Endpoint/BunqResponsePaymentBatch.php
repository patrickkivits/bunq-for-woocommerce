<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Http\BunqResponse;

/**
 */
class BunqResponsePaymentBatch extends BunqResponse
{
    /**
     * @return PaymentBatchApiObject
     */
    public function getValue(): PaymentBatchApiObject
    {
        return parent::getValue();
    }
}
