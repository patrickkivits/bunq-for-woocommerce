<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Model\Core\BunqModel;

/**
 * You can use MonetaryAccountAccess to retrieve all MonetaryAccountAccessModels for the given MonetaryAccount
 *
 * @generated
 */
class MonetaryAccountAccessApiObject extends BunqModel
{
    /**
     * Field constants.
     */
    const FIELD_ACCESS_TYPE = 'access_type';

    /**
     * The access type of the monetary account access.
     *
     * @var string
     */
    protected $accessType;

    /**
     * The access type of the monetary account access.
     *
     * @var string
     */
    protected $accessTypeFieldForRequest;

    /**
     * @param string $accessType The access type of the monetary account access.
     */
    public function __construct(string  $accessType)
    {
        $this->accessTypeFieldForRequest = $accessType;
    }

    /**
     * The access type of the monetary account access.
     *
     * @return string
     */
    public function getAccessType()
    {
        return $this->accessType;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $accessType
     */
    public function setAccessType($accessType)
    {
        $this->accessType = $accessType;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->accessType)) {
            return false;
        }

        return true;
    }
}
