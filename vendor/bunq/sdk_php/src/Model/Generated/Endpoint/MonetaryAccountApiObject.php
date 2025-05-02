<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AmountObject;
use bunq\Model\Generated\Object\BunqIdObject;
use bunq\Model\Generated\Object\CoOwnerObject;
use bunq\Model\Generated\Object\MonetaryAccountSettingObject;
use bunq\Model\Generated\Object\PointerObject;

/**
 * Used to show the MonetaryAccounts that you can access. Currently the only MonetaryAccount type is MonetaryAccountBank.
 * See also: monetary-account-bank.<br/><br/>Notification filters can be set on a monetary account level to receive
 * callbacks. For more information check the <a href="/api/2/page/callbacks">dedicated callbacks page</a>.
 *
 * @generated
 */
class MonetaryAccountApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_READ = 'user/%s/monetary-account/%s';
    const ENDPOINT_URL_LISTING = 'user/%s/monetary-account';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'MonetaryAccount';

    /**
     * The aliases for the MonetaryAccount.
     *
     * @var PointerObject[]
     */
    protected $alias;

    /**
     * The current available balance amount of the MonetaryAccount.
     *
     * @var AmountObject
     */
    protected $balance;

    /**
     * The profiles of the account.
     *
     * @var MonetaryAccountProfileApiObject
     */
    protected $monetaryAccountProfile;

    /**
     * The settings of the MonetaryAccount.
     *
     * @var MonetaryAccountSettingObject
     */
    protected $setting;

    /**
     * The budgets of the MonetaryAccount.
     *
     * @var MonetaryAccountBudgetApiObject[]
     */
    protected $budget;

    /**
     * The reason for voluntarily cancelling (closing) the MonetaryAccount.
     *
     * @var string
     */
    protected $reason;

    /**
     * The optional free-form reason for voluntarily cancelling (closing) the MonetaryAccount. Can be any user provided
     * message.
     *
     * @var string
     */
    protected $reasonDescription;

    /**
     * The ShareInviteBankResponse when the MonetaryAccount is accessed by the User via a share/connect.
     *
     * @var ShareInviteMonetaryAccountResponseApiObject
     */
    protected $share;

    /**
     * The ids of the AutoSave.
     *
     * @var BunqIdObject[]
     */
    protected $allAutoSaveId;

    /**
     * The fulfillments for this MonetaryAccount.
     *
     * @var FulfillmentApiObject[]
     */
    protected $fulfillments;

    /**
     * The RelationUser when the MonetaryAccount is accessed by the User via a share/connect.
     *
     * @var RelationUserApiObject
     */
    protected $relationUser;

    /**
     * The users the account will be joint with.
     *
     * @var CoOwnerObject[]
     */
    protected $allCoOwner;

    /**
     * The CoOwnerInvite when the MonetaryAccount is accessed by the User via a CoOwnerInvite.
     *
     * @var CoOwnerInviteResponseApiObject
     */
    protected $coOwnerInvite;

    /**
     * The open banking account for information about the external account.
     *
     * @var OpenBankingAccountApiObject
     */
    protected $openBankingAccount;

    /**
     * The Birdee investment portfolio.
     *
     * @var BirdeeInvestmentPortfolioApiObject
     */
    protected $birdeeInvestmentPortfolio;

    /**
     * The access of this Monetary Account.
     *
     * @var MonetaryAccountAccessApiObject[]
     */
    protected $allAccess;

    /**
     * @var MonetaryAccountLightApiObject
     */
    protected $monetaryAccountLight;

    /**
     * @var MonetaryAccountBankApiObject
     */
    protected $monetaryAccountBank;

    /**
     * @var MonetaryAccountExternalApiObject
     */
    protected $monetaryAccountExternal;

    /**
     * @var MonetaryAccountInvestmentApiObject
     */
    protected $monetaryAccountInvestment;

    /**
     * @var MonetaryAccountJointApiObject
     */
    protected $monetaryAccountJoint;

    /**
     * @var MonetaryAccountSavingsApiObject
     */
    protected $monetaryAccountSavings;

    /**
     * @var MonetaryAccountSwitchServiceApiObject
     */
    protected $monetaryAccountSwitchService;

    /**
     * @var MonetaryAccountExternalSavingsApiObject
     */
    protected $monetaryAccountExternalSavings;

    /**
     * @var MonetaryAccountCardApiObject
     */
    protected $monetaryAccountCard;

    /**
     * Get a specific MonetaryAccount.
     *
     * @param int $monetaryAccountId
     * @param string[] $customHeaders
     *
     * @return BunqResponseMonetaryAccount
     */
    public static function get(int $monetaryAccountId, array $customHeaders = []): BunqResponseMonetaryAccount
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_READ,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId)]
            ),
            [],
            $customHeaders
        );

        return BunqResponseMonetaryAccount::castFromBunqResponse(
            static::fromJson($responseRaw)
        );
    }

    /**
     * Get a collection of all your MonetaryAccounts.
     *
     * This method is called "listing" because "list" is a restricted PHP word and cannot be used as constants, class names,
     * function or method names.
     *
     * @param string[] $params
     * @param string[] $customHeaders
     *
     * @return BunqResponseMonetaryAccountApiObjectList
     */
    public static function listing( array $params = [], array $customHeaders = []): BunqResponseMonetaryAccountApiObjectList
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_LISTING,
                [static::determineUserId()]
            ),
            $params,
            $customHeaders
        );

        return BunqResponseMonetaryAccountApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw)
        );
    }

    /**
     * The aliases for the MonetaryAccount.
     *
     * @return PointerObject[]
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param PointerObject[] $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * The current available balance amount of the MonetaryAccount.
     *
     * @return AmountObject
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $balance
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;
    }

    /**
     * The profiles of the account.
     *
     * @return MonetaryAccountProfileApiObject
     */
    public function getMonetaryAccountProfile()
    {
        return $this->monetaryAccountProfile;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MonetaryAccountProfileApiObject $monetaryAccountProfile
     */
    public function setMonetaryAccountProfile($monetaryAccountProfile)
    {
        $this->monetaryAccountProfile = $monetaryAccountProfile;
    }

    /**
     * The settings of the MonetaryAccount.
     *
     * @return MonetaryAccountSettingObject
     */
    public function getSetting()
    {
        return $this->setting;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MonetaryAccountSettingObject $setting
     */
    public function setSetting($setting)
    {
        $this->setting = $setting;
    }

    /**
     * The budgets of the MonetaryAccount.
     *
     * @return MonetaryAccountBudgetApiObject[]
     */
    public function getBudget()
    {
        return $this->budget;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MonetaryAccountBudgetApiObject[] $budget
     */
    public function setBudget($budget)
    {
        $this->budget = $budget;
    }

    /**
     * The reason for voluntarily cancelling (closing) the MonetaryAccount.
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $reason
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    /**
     * The optional free-form reason for voluntarily cancelling (closing) the MonetaryAccount. Can be any user provided
     * message.
     *
     * @return string
     */
    public function getReasonDescription()
    {
        return $this->reasonDescription;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $reasonDescription
     */
    public function setReasonDescription($reasonDescription)
    {
        $this->reasonDescription = $reasonDescription;
    }

    /**
     * The ShareInviteBankResponse when the MonetaryAccount is accessed by the User via a share/connect.
     *
     * @return ShareInviteMonetaryAccountResponseApiObject
     */
    public function getShare()
    {
        return $this->share;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param ShareInviteMonetaryAccountResponseApiObject $share
     */
    public function setShare($share)
    {
        $this->share = $share;
    }

    /**
     * The ids of the AutoSave.
     *
     * @return BunqIdObject[]
     */
    public function getAllAutoSaveId()
    {
        return $this->allAutoSaveId;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param BunqIdObject[] $allAutoSaveId
     */
    public function setAllAutoSaveId($allAutoSaveId)
    {
        $this->allAutoSaveId = $allAutoSaveId;
    }

    /**
     * The fulfillments for this MonetaryAccount.
     *
     * @return FulfillmentApiObject[]
     */
    public function getFulfillments()
    {
        return $this->fulfillments;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param FulfillmentApiObject[] $fulfillments
     */
    public function setFulfillments($fulfillments)
    {
        $this->fulfillments = $fulfillments;
    }

    /**
     * The RelationUser when the MonetaryAccount is accessed by the User via a share/connect.
     *
     * @return RelationUserApiObject
     */
    public function getRelationUser()
    {
        return $this->relationUser;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param RelationUserApiObject $relationUser
     */
    public function setRelationUser($relationUser)
    {
        $this->relationUser = $relationUser;
    }

    /**
     * The users the account will be joint with.
     *
     * @return CoOwnerObject[]
     */
    public function getAllCoOwner()
    {
        return $this->allCoOwner;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param CoOwnerObject[] $allCoOwner
     */
    public function setAllCoOwner($allCoOwner)
    {
        $this->allCoOwner = $allCoOwner;
    }

    /**
     * The CoOwnerInvite when the MonetaryAccount is accessed by the User via a CoOwnerInvite.
     *
     * @return CoOwnerInviteResponseApiObject
     */
    public function getCoOwnerInvite()
    {
        return $this->coOwnerInvite;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param CoOwnerInviteResponseApiObject $coOwnerInvite
     */
    public function setCoOwnerInvite($coOwnerInvite)
    {
        $this->coOwnerInvite = $coOwnerInvite;
    }

    /**
     * The open banking account for information about the external account.
     *
     * @return OpenBankingAccountApiObject
     */
    public function getOpenBankingAccount()
    {
        return $this->openBankingAccount;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param OpenBankingAccountApiObject $openBankingAccount
     */
    public function setOpenBankingAccount($openBankingAccount)
    {
        $this->openBankingAccount = $openBankingAccount;
    }

    /**
     * The Birdee investment portfolio.
     *
     * @return BirdeeInvestmentPortfolioApiObject
     */
    public function getBirdeeInvestmentPortfolio()
    {
        return $this->birdeeInvestmentPortfolio;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param BirdeeInvestmentPortfolioApiObject $birdeeInvestmentPortfolio
     */
    public function setBirdeeInvestmentPortfolio($birdeeInvestmentPortfolio)
    {
        $this->birdeeInvestmentPortfolio = $birdeeInvestmentPortfolio;
    }

    /**
     * The access of this Monetary Account.
     *
     * @return MonetaryAccountAccessApiObject[]
     */
    public function getAllAccess()
    {
        return $this->allAccess;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MonetaryAccountAccessApiObject[] $allAccess
     */
    public function setAllAccess($allAccess)
    {
        $this->allAccess = $allAccess;
    }

    /**
     * @return MonetaryAccountLightApiObject
     */
    public function getMonetaryAccountLight()
    {
        return $this->monetaryAccountLight;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MonetaryAccountLightApiObject $monetaryAccountLight
     */
    public function setMonetaryAccountLight($monetaryAccountLight)
    {
        $this->monetaryAccountLight = $monetaryAccountLight;
    }

    /**
     * @return MonetaryAccountBankApiObject
     */
    public function getMonetaryAccountBank()
    {
        return $this->monetaryAccountBank;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MonetaryAccountBankApiObject $monetaryAccountBank
     */
    public function setMonetaryAccountBank($monetaryAccountBank)
    {
        $this->monetaryAccountBank = $monetaryAccountBank;
    }

    /**
     * @return MonetaryAccountExternalApiObject
     */
    public function getMonetaryAccountExternal()
    {
        return $this->monetaryAccountExternal;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MonetaryAccountExternalApiObject $monetaryAccountExternal
     */
    public function setMonetaryAccountExternal($monetaryAccountExternal)
    {
        $this->monetaryAccountExternal = $monetaryAccountExternal;
    }

    /**
     * @return MonetaryAccountInvestmentApiObject
     */
    public function getMonetaryAccountInvestment()
    {
        return $this->monetaryAccountInvestment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MonetaryAccountInvestmentApiObject $monetaryAccountInvestment
     */
    public function setMonetaryAccountInvestment($monetaryAccountInvestment)
    {
        $this->monetaryAccountInvestment = $monetaryAccountInvestment;
    }

    /**
     * @return MonetaryAccountJointApiObject
     */
    public function getMonetaryAccountJoint()
    {
        return $this->monetaryAccountJoint;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MonetaryAccountJointApiObject $monetaryAccountJoint
     */
    public function setMonetaryAccountJoint($monetaryAccountJoint)
    {
        $this->monetaryAccountJoint = $monetaryAccountJoint;
    }

    /**
     * @return MonetaryAccountSavingsApiObject
     */
    public function getMonetaryAccountSavings()
    {
        return $this->monetaryAccountSavings;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MonetaryAccountSavingsApiObject $monetaryAccountSavings
     */
    public function setMonetaryAccountSavings($monetaryAccountSavings)
    {
        $this->monetaryAccountSavings = $monetaryAccountSavings;
    }

    /**
     * @return MonetaryAccountSwitchServiceApiObject
     */
    public function getMonetaryAccountSwitchService()
    {
        return $this->monetaryAccountSwitchService;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MonetaryAccountSwitchServiceApiObject $monetaryAccountSwitchService
     */
    public function setMonetaryAccountSwitchService($monetaryAccountSwitchService)
    {
        $this->monetaryAccountSwitchService = $monetaryAccountSwitchService;
    }

    /**
     * @return MonetaryAccountExternalSavingsApiObject
     */
    public function getMonetaryAccountExternalSavings()
    {
        return $this->monetaryAccountExternalSavings;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MonetaryAccountExternalSavingsApiObject $monetaryAccountExternalSavings
     */
    public function setMonetaryAccountExternalSavings($monetaryAccountExternalSavings)
    {
        $this->monetaryAccountExternalSavings = $monetaryAccountExternalSavings;
    }

    /**
     * @return MonetaryAccountCardApiObject
     */
    public function getMonetaryAccountCard()
    {
        return $this->monetaryAccountCard;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param MonetaryAccountCardApiObject $monetaryAccountCard
     */
    public function setMonetaryAccountCard($monetaryAccountCard)
    {
        $this->monetaryAccountCard = $monetaryAccountCard;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->alias)) {
            return false;
        }

        if (!is_null($this->balance)) {
            return false;
        }

        if (!is_null($this->monetaryAccountProfile)) {
            return false;
        }

        if (!is_null($this->setting)) {
            return false;
        }

        if (!is_null($this->budget)) {
            return false;
        }

        if (!is_null($this->reason)) {
            return false;
        }

        if (!is_null($this->reasonDescription)) {
            return false;
        }

        if (!is_null($this->share)) {
            return false;
        }

        if (!is_null($this->allAutoSaveId)) {
            return false;
        }

        if (!is_null($this->fulfillments)) {
            return false;
        }

        if (!is_null($this->relationUser)) {
            return false;
        }

        if (!is_null($this->allCoOwner)) {
            return false;
        }

        if (!is_null($this->coOwnerInvite)) {
            return false;
        }

        if (!is_null($this->openBankingAccount)) {
            return false;
        }

        if (!is_null($this->birdeeInvestmentPortfolio)) {
            return false;
        }

        if (!is_null($this->allAccess)) {
            return false;
        }

        if (!is_null($this->monetaryAccountLight)) {
            return false;
        }

        if (!is_null($this->monetaryAccountBank)) {
            return false;
        }

        if (!is_null($this->monetaryAccountExternal)) {
            return false;
        }

        if (!is_null($this->monetaryAccountInvestment)) {
            return false;
        }

        if (!is_null($this->monetaryAccountJoint)) {
            return false;
        }

        if (!is_null($this->monetaryAccountSavings)) {
            return false;
        }

        if (!is_null($this->monetaryAccountSwitchService)) {
            return false;
        }

        if (!is_null($this->monetaryAccountExternalSavings)) {
            return false;
        }

        if (!is_null($this->monetaryAccountCard)) {
            return false;
        }

        return true;
    }
}
