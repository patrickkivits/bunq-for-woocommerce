<?php
namespace bunq\Context;

use bunq\Exception\BunqException;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Endpoint\MonetaryAccountBankApiObject;
use bunq\Model\Generated\Endpoint\UserApiKeyApiObject;
use bunq\Model\Generated\Endpoint\UserApiObject;
use bunq\Model\Generated\Endpoint\UserCompanyApiObject;
use bunq\Model\Generated\Endpoint\UserPaymentServiceProviderApiObject;
use bunq\Model\Generated\Endpoint\UserPersonApiObject;

/**
 */
class UserContext
{
    /**
     * Error constants.
     */
    const ERROR_NO_ACTIVE_MONETARY_ACCOUNT_FOUND = 'No active monetary account found.';
    const ERROR_PRIMARY_MONETARY_ACCOUNT_HAS_NOT_BEEN_SET = 'Primary monetaryAccount is not set.';
    const ERROR_UNEXPECTED_USER_INSTANCE = '"%s" is unexpected user instance.';

    /**
     * MonetaryAccount status constants.
     */
    const MONETARY_ACCOUNT_STATUS_ACTIVE = 'ACTIVE';

    /**
     * The index of the first item in an array.
     */
    const INDEX_FIRST = 0;

    /**
     * @var UserCompanyApiObject
     */
    protected $userCompany;

    /**
     * @var UserPersonApiObject
     */
    protected $userPerson;

    /**
     * @var UserApiKeyApiObject
     */
    protected $userApiKey;

    /**
     * @var UserPaymentServiceProviderApiObject
     */
    protected $userPaymentServiceProvider;

    /**
     * @var MonetaryAccountBankApiObject
     */
    protected $primaryMonetaryAccount;

    /**
     * @var int
     */
    protected $userId;

    /**
     * @param int $userId
     * @param $user UserPersonApiObject|UserCompanyApiObject|UserApiKeyApiObject|UserPaymentServiceProviderApiObject
     */
    public function __construct(int $userId, $user)
    {
        $this->userId = $userId;
        $this->setUser($user);
    }

    /**
     * @return BunqModel
     */
    private function getUserObject(): BunqModel
    {
        return UserApiObject::listing()->getValue()[self::INDEX_FIRST]->getReferencedObject();
    }

    /**
     * @param $user
     *
     * @throws BunqException
     */
    private function setUser($user)
    {
        if ($user instanceof UserPersonApiObject) {
            $this->userPerson = $user;
        } elseif ($user instanceof UserCompanyApiObject) {
            $this->userCompany = $user;
        } elseif ($user instanceof UserApiKeyApiObject) {
            $this->userApiKey = $user;
        } elseif ($user instanceof UserPaymentServiceProviderApiObject) {
            $this->userPaymentServiceProvider = $user;
        } else {
            throw new BunqException(vsprintf(self::ERROR_UNEXPECTED_USER_INSTANCE, [get_class($user)]));
        }
    }

    /**
     * @throws BunqException
     */
    public function initMainMonetaryAccount()
    {
        if (!is_null($this->userPaymentServiceProvider)) {
            return;
        }

        $allMonetaryAccount = MonetaryAccountBankApiObject::listing()->getValue();

        foreach ($allMonetaryAccount as $account) {
            if ($account->getStatus() === self::MONETARY_ACCOUNT_STATUS_ACTIVE) {
                $this->primaryMonetaryAccount = $account;

                return;
            }
        }

        throw new BunqException(self::ERROR_NO_ACTIVE_MONETARY_ACCOUNT_FOUND);
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return bool
     */
    public function isOnlyUserPersonSet(): bool
    {
        return is_null($this->userCompany) && is_null($this->userApiKey) && !(is_null($this->userPerson));
    }

    /**
     * @return bool
     */
    public function isOnlyUserCompanySet(): bool
    {
        return is_null($this->userPerson) && is_null($this->userApiKey) && !is_null($this->userCompany);
    }

    /**
     * @return bool
     */
    public function isOnlyUserApiKeySet(): bool
    {
        return is_null($this->userApiKey) && is_null($this->userPerson) && !is_null($this->userCompany);
    }

    /**
     * @return bool
     */
    public function areAllUserSet(): bool
    {
        return !is_null($this->userCompany) && !is_null($this->userPerson) && !is_null($this->userApiKey);
    }

    /**
     * @return int
     * @throws BunqException
     */
    public function getMainMonetaryAccountId(): int
    {
        if (is_null($this->primaryMonetaryAccount)) {
            throw new BunqException(self::ERROR_PRIMARY_MONETARY_ACCOUNT_HAS_NOT_BEEN_SET);
        } else {
            return $this->primaryMonetaryAccount->getId();
        }
    }

    /**
     * @return UserCompanyApiObject
     */
    public function getUserCompany(): UserCompanyApiObject
    {
        return $this->userCompany;
    }

    /**
     * @return UserPersonApiObject
     */
    public function getUserPerson(): UserPersonApiObject
    {
        return $this->userPerson;
    }

    /**
     * @return UserApiKeyApiObject
     */
    public function getUserApiKey(): UserApiKeyApiObject
    {
        return $this->userApiKey;
    }

    /**
     * @return MonetaryAccountBankApiObject
     */
    public function getPrimaryMonetaryAccount(): MonetaryAccountBankApiObject
    {
        return $this->primaryMonetaryAccount;
    }

    /**
     */
    public function refreshUserContext()
    {
        $this->setUser($this->getUserObject());
        $this->initMainMonetaryAccount();
    }
}
