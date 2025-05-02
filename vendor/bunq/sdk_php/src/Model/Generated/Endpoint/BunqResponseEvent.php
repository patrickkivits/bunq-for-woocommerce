<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Http\BunqResponse;

/**
 */
class BunqResponseEvent extends BunqResponse
{
    /**
     * @return EventApiObject
     */
    public function getValue(): EventApiObject
    {
        return parent::getValue();
    }
}
