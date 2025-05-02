<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Http\BunqResponse;

/**
 */
class BunqResponseUserPaymentServiceProvider extends BunqResponse
{
    /**
     * @return UserPaymentServiceProviderApiObject
     */
    public function getValue(): UserPaymentServiceProviderApiObject
    {
        return parent::getValue();
    }
}
