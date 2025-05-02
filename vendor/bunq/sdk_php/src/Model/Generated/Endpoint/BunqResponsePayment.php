<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Http\BunqResponse;

/**
 */
class BunqResponsePayment extends BunqResponse
{
    /**
     * @return PaymentApiObject
     */
    public function getValue(): PaymentApiObject
    {
        return parent::getValue();
    }
}
