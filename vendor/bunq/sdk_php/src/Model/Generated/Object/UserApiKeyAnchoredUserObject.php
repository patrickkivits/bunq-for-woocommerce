<?php
namespace bunq\Model\Generated\Object;

use bunq\Exception\BunqException;
use bunq\Model\Core\AnchorObjectInterface;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Endpoint\UserCompanyApiObject;
use bunq\Model\Generated\Endpoint\UserPaymentServiceProviderApiObject;
use bunq\Model\Generated\Endpoint\UserPersonApiObject;

/**
 * @generated
 */
class UserApiKeyAnchoredUserObject extends BunqModel implements AnchorObjectInterface
{
    /**
     * Error constants.
     */
    const ERROR_NULL_FIELDS = 'All fields of an extended model or object are null.';

    /**
     * @var UserPersonApiObject
     */
    protected $userPerson;

    /**
     * @var UserCompanyApiObject
     */
    protected $userCompany;

    /**
     * @var UserPaymentServiceProviderApiObject
     */
    protected $userPaymentServiceProvider;

    /**
     * @return UserPersonApiObject
     */
    public function getUserPerson()
    {
        return $this->userPerson;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param UserPersonApiObject $userPerson
     */
    public function setUserPerson($userPerson)
    {
        $this->userPerson = $userPerson;
    }

    /**
     * @return UserCompanyApiObject
     */
    public function getUserCompany()
    {
        return $this->userCompany;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param UserCompanyApiObject $userCompany
     */
    public function setUserCompany($userCompany)
    {
        $this->userCompany = $userCompany;
    }

    /**
     * @return UserPaymentServiceProviderApiObject
     */
    public function getUserPaymentServiceProvider()
    {
        return $this->userPaymentServiceProvider;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param UserPaymentServiceProviderApiObject $userPaymentServiceProvider
     */
    public function setUserPaymentServiceProvider($userPaymentServiceProvider)
    {
        $this->userPaymentServiceProvider = $userPaymentServiceProvider;
    }

    /**
     * @return BunqModel
     * @throws BunqException
     */
    public function getReferencedObject()
    {
        if (!is_null($this->userPerson)) {
            return $this->userPerson;
        }

        if (!is_null($this->userCompany)) {
            return $this->userCompany;
        }

        if (!is_null($this->userPaymentServiceProvider)) {
            return $this->userPaymentServiceProvider;
        }

        throw new BunqException(self::ERROR_NULL_FIELDS);
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->userPerson)) {
            return false;
        }

        if (!is_null($this->userCompany)) {
            return false;
        }

        if (!is_null($this->userPaymentServiceProvider)) {
            return false;
        }

        return true;
    }
}
