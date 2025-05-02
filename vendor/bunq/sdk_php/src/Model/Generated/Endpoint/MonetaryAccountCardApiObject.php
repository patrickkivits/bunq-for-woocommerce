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
 * @generated
 */
class MonetaryAccountCardApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_READ = 'user/%s/monetary-account-card/%s';
    const ENDPOINT_URL_UPDATE = 'user/%s/monetary-account-card/%s';
    const ENDPOINT_URL_LISTING = 'user/%s/monetary-account-card';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'MonetaryAccountCard';

    /**
     * The id of the MonetaryAccountCard.
     *
     * @var int
     */
    protected $id;

    /**
     * The timestamp of the MonetaryAccountCard's creation.
     *
     * @var string
     */
    protected $created;

    /**
     * The timestamp of the MonetaryAccountCard's last update.
     *
     * @var string
     */
    protected $updated;

    /**
     * The currency of the MonetaryAccountCard as an ISO 4217 formatted currency code.
     *
     * @var string
     */
    protected $currency;

    /**
     * The description of the MonetaryAccountCard. Defaults to 'prepaid credit card'.
     *
     * @var string
     */
    protected $description;

    /**
     * The daily spending limit Amount of the MonetaryAccountCard.
     *
     * @var AmountObject
     */
    protected $dailyLimit;

    /**
     * The maximum Amount the MonetaryAccountCard can be 'in the red'.
     *
     * @var AmountObject
     */
    protected $overdraftLimit;

    /**
     * The current available balance amount of the MonetaryAccount.
     *
     * @var AmountObject
     */
    protected $balance;

    /**
     * The current real balance Amount of the MonetaryAccountCard.
     *
     * @var AmountObject
     */
    protected $balanceReal;

    /**
     * The aliases for the MonetaryAccount.
     *
     * @var PointerObject[]
     */
    protected $alias;

    /**
     * The MonetaryAccountCard's public UUID.
     *
     * @var string
     */
    protected $publicUuid;

    /**
     * The status of the MonetaryAccountCard.
     *
     * @var string
     */
    protected $status;

    /**
     * The sub-status of the MonetaryAccountCard providing extra information regarding the status.
     *
     * @var string
     */
    protected $subStatus;

    /**
     * The id of the User who owns the MonetaryAccountCard.
     *
     * @var int
     */
    protected $userId;

    /**
     * The RelationUser when the MonetaryAccount is accessed by the User via a share/connect.
     *
     * @var RelationUserApiObject
     */
    protected $relationUser;

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
     * Get a specific MonetaryAccountCard.
     *
     * @param int $monetaryAccountCardId
     * @param string[] $customHeaders
     *
     * @return BunqResponseMonetaryAccountCard
     */
    public static function get(int $monetaryAccountCardId, array $customHeaders = []): BunqResponseMonetaryAccountCard
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_READ,
                [static::determineUserId(), $monetaryAccountCardId]
            ),
            [],
            $customHeaders
        );

        return BunqResponseMonetaryAccountCard::castFromBunqResponse(
            static::fromJson($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * Update a specific existing MonetaryAccountCard.
     *
     * @param int $monetaryAccountCardId
     * @param string[] $customHeaders
     *
     * @return BunqResponseInt
     */
    public static function update(int $monetaryAccountCardId, array $customHeaders = []): BunqResponseInt
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->put(
            vsprintf(
                self::ENDPOINT_URL_UPDATE,
                [static::determineUserId(), $monetaryAccountCardId]
            ),
            [],
            $customHeaders
        );

        return BunqResponseInt::castFromBunqResponse(
            static::processForId($responseRaw)
        );
    }

    /**
     * Gets a listing of all MonetaryAccountCard of a given user.
     *
     * This method is called "listing" because "list" is a restricted PHP word and cannot be used as constants, class names,
     * function or method names.
     *
     * @param string[] $params
     * @param string[] $customHeaders
     *
     * @return BunqResponseMonetaryAccountCardApiObjectList
     */
    public static function listing( array $params = [], array $customHeaders = []): BunqResponseMonetaryAccountCardApiObjectList
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

        return BunqResponseMonetaryAccountCardApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * The id of the MonetaryAccountCard.
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
     * The timestamp of the MonetaryAccountCard's creation.
     *
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * The timestamp of the MonetaryAccountCard's last update.
     *
     * @return string
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * The currency of the MonetaryAccountCard as an ISO 4217 formatted currency code.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * The description of the MonetaryAccountCard. Defaults to 'prepaid credit card'.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * The daily spending limit Amount of the MonetaryAccountCard.
     *
     * @return AmountObject
     */
    public function getDailyLimit()
    {
        return $this->dailyLimit;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $dailyLimit
     */
    public function setDailyLimit($dailyLimit)
    {
        $this->dailyLimit = $dailyLimit;
    }

    /**
     * The maximum Amount the MonetaryAccountCard can be 'in the red'.
     *
     * @return AmountObject
     */
    public function getOverdraftLimit()
    {
        return $this->overdraftLimit;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $overdraftLimit
     */
    public function setOverdraftLimit($overdraftLimit)
    {
        $this->overdraftLimit = $overdraftLimit;
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
     * The current real balance Amount of the MonetaryAccountCard.
     *
     * @return AmountObject
     */
    public function getBalanceReal()
    {
        return $this->balanceReal;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $balanceReal
     */
    public function setBalanceReal($balanceReal)
    {
        $this->balanceReal = $balanceReal;
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
     * The MonetaryAccountCard's public UUID.
     *
     * @return string
     */
    public function getPublicUuid()
    {
        return $this->publicUuid;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $publicUuid
     */
    public function setPublicUuid($publicUuid)
    {
        $this->publicUuid = $publicUuid;
    }

    /**
     * The status of the MonetaryAccountCard.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * The sub-status of the MonetaryAccountCard providing extra information regarding the status.
     *
     * @return string
     */
    public function getSubStatus()
    {
        return $this->subStatus;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $subStatus
     */
    public function setSubStatus($subStatus)
    {
        $this->subStatus = $subStatus;
    }

    /**
     * The id of the User who owns the MonetaryAccountCard.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
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
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->id)) {
            return false;
        }

        if (!is_null($this->created)) {
            return false;
        }

        if (!is_null($this->updated)) {
            return false;
        }

        if (!is_null($this->currency)) {
            return false;
        }

        if (!is_null($this->description)) {
            return false;
        }

        if (!is_null($this->dailyLimit)) {
            return false;
        }

        if (!is_null($this->overdraftLimit)) {
            return false;
        }

        if (!is_null($this->balance)) {
            return false;
        }

        if (!is_null($this->balanceReal)) {
            return false;
        }

        if (!is_null($this->alias)) {
            return false;
        }

        if (!is_null($this->publicUuid)) {
            return false;
        }

        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->subStatus)) {
            return false;
        }

        if (!is_null($this->userId)) {
            return false;
        }

        if (!is_null($this->relationUser)) {
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

        return true;
    }
}
