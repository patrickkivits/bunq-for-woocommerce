<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Http\BunqResponse;

/**
 */
class BunqResponseCard extends BunqResponse
{
    /**
     * @return CardApiObject
     */
    public function getValue(): CardApiObject
    {
        return parent::getValue();
    }
}
