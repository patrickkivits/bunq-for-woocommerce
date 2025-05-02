<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Http\BunqResponse;

/**
 */
class BunqResponseInvoice extends BunqResponse
{
    /**
     * @return InvoiceApiObject
     */
    public function getValue(): InvoiceApiObject
    {
        return parent::getValue();
    }
}
