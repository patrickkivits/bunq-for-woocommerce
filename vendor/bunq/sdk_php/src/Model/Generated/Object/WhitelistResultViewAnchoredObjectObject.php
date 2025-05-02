<?php
namespace bunq\Model\Generated\Object;

use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Endpoint\DraftPaymentApiObject;
use bunq\Model\Generated\Endpoint\RequestResponseApiObject;

/**
 * @generated
 */
class WhitelistResultViewAnchoredObjectObject extends BunqModel
{
    /**
     * The ID of the whitelist entry.
     *
     * @var int
     */
    protected $id;

    /**
     * The RequestResponse object
     *
     * @var RequestResponseApiObject
     */
    protected $requestResponse;

    /**
     * The DraftPayment object
     *
     * @var DraftPaymentApiObject
     */
    protected $draftPayment;

    /**
     * The ID of the whitelist entry.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * The RequestResponse object
     *
     * @return RequestResponseApiObject
     */
    public function getRequestResponse()
    {
        return $this->requestResponse;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param RequestResponseApiObject $requestResponse
     */
    public function setRequestResponse($requestResponse)
    {
        $this->requestResponse = $requestResponse;
    }

    /**
     * The DraftPayment object
     *
     * @return DraftPaymentApiObject
     */
    public function getDraftPayment()
    {
        return $this->draftPayment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param DraftPaymentApiObject $draftPayment
     */
    public function setDraftPayment($draftPayment)
    {
        $this->draftPayment = $draftPayment;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->id)) {
            return false;
        }

        if (!is_null($this->requestResponse)) {
            return false;
        }

        if (!is_null($this->draftPayment)) {
            return false;
        }

        return true;
    }
}
