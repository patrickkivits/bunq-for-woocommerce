<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AmountObject;
use bunq\Model\Generated\Object\BunqIdObject;
use bunq\Model\Generated\Object\CoOwnerObject;
use bunq\Model\Generated\Object\MonetaryAccountSettingObject;
use bunq\Model\Generated\Object\PointerObject;

/**
 * View for MonetaryAccountSwitchService
 *
 * @generated
 */
class MonetaryAccountSwitchServiceApiObject extends BunqModel
{
    /**
     * The id of the monetary account.
     *
     * @var int
     */
    protected $id;

    /**
     * The timestamp of the monetary account's creation.
     *
     * @var string
     */
    protected $created;

    /**
     * The description for the bank account.
     *
     * @var string
     */
    protected $description;

    /**
     * The status of the account.
     *
     * @var string
     */
    protected $status;

    /**
     * The sub-status of the account.
     *
     * @var string
     */
    protected $subStatus;

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
     * The id of the monetary account.
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
     * The timestamp of the monetary account's creation.
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
     * The description for the bank account.
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
     * The status of the account.
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
     * The sub-status of the account.
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

        if (!is_null($this->description)) {
            return false;
        }

        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->subStatus)) {
            return false;
        }

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

        return true;
    }
}
