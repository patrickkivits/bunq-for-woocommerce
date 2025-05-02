<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Http\BunqResponse;

/**
 */
class BunqResponseSofortMerchantTransaction extends BunqResponse
{
    /**
     * @return SofortMerchantTransactionApiObject
     */
    public function getValue(): SofortMerchantTransactionApiObject
    {
        return parent::getValue();
    }
}
