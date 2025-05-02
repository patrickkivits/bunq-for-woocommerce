<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AmountObject;
use bunq\Model\Generated\Object\AvatarObject;
use bunq\Model\Generated\Object\BunqIdObject;
use bunq\Model\Generated\Object\CoOwnerObject;
use bunq\Model\Generated\Object\MonetaryAccountSettingObject;
use bunq\Model\Generated\Object\PointerObject;

/**
 * With MonetaryAccountBank you can create a new bank account, retrieve information regarding your existing
 * MonetaryAccountBanks and update specific fields of an existing MonetaryAccountBank. Examples of fields that can be
 * updated are the description, the daily limit and the avatar of the account.<br/><br/>Notification filters can be set on
 * a monetary account level to receive callbacks. For more information check the <a href="/api/1/page/callbacks">dedicated
 * callbacks page</a>.
 *
 * @generated
 */
class MonetaryAccountBankApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_CREATE = 'user/%s/monetary-account-bank';
    const ENDPOINT_URL_READ = 'user/%s/monetary-account-bank/%s';
    const ENDPOINT_URL_UPDATE = 'user/%s/monetary-account-bank/%s';
    const ENDPOINT_URL_LISTING = 'user/%s/monetary-account-bank';

    /**
     * Field constants.
     */
    const FIELD_CURRENCY = 'currency';
    const FIELD_DESCRIPTION = 'description';
    const FIELD_DAILY_LIMIT = 'daily_limit';
    const FIELD_AVATAR_UUID = 'avatar_uuid';
    const FIELD_STATUS = 'status';
    const FIELD_SUB_STATUS = 'sub_status';
    const FIELD_REASON = 'reason';
    const FIELD_REASON_DESCRIPTION = 'reason_description';
    const FIELD_DISPLAY_NAME = 'display_name';
    const FIELD_SETTING = 'setting';
    const FIELD_COUNTRY_IBAN = 'country_iban';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'MonetaryAccountBank';

    /**
     * The id of the MonetaryAccountBank.
     *
     * @var int
     */
    protected $id;

    /**
     * The timestamp of the MonetaryAccountBank's creation.
     *
     * @var string
     */
    protected $created;

    /**
     * The timestamp of the MonetaryAccountBank's last update.
     *
     * @var string
     */
    protected $updated;

    /**
     * The Avatar of the MonetaryAccountBank.
     *
     * @var AvatarObject
     */
    protected $avatar;

    /**
     * The currency of the MonetaryAccountBank as an ISO 4217 formatted currency code.
     *
     * @var string
     */
    protected $currency;

    /**
     * The description of the MonetaryAccountBank. Defaults to 'bunq account'.
     *
     * @var string
     */
    protected $description;

    /**
     * The daily spending limit Amount of the MonetaryAccountBank. Defaults to 1000 EUR. Currency must match the
     * MonetaryAccountBank's currency. Limited to 10000 EUR.
     *
     * @var AmountObject
     */
    protected $dailyLimit;

    /**
     * The maximum Amount the MonetaryAccountBank can be 'in the red'.
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
     * The aliases for the MonetaryAccount.
     *
     * @var PointerObject[]
     */
    protected $alias;

    /**
     * The MonetaryAccountBank's public UUID.
     *
     * @var string
     */
    protected $publicUuid;

    /**
     * The status of the MonetaryAccountBank. Can be: ACTIVE, BLOCKED, CANCELLED or PENDING_REOPEN
     *
     * @var string
     */
    protected $status;

    /**
     * The sub-status of the MonetaryAccountBank providing extra information regarding the status. Will be NONE for ACTIVE or
     * PENDING_REOPEN, COMPLETELY or ONLY_ACCEPTING_INCOMING for BLOCKED and REDEMPTION_INVOLUNTARY, REDEMPTION_VOLUNTARY or
     * PERMANENT for CANCELLED.
     *
     * @var string
     */
    protected $subStatus;

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
     * The RelationUser when the MonetaryAccount is accessed by the User via a share/connect.
     *
     * @var RelationUserApiObject
     */
    protected $relationUser;

    /**
     * The id of the User who owns the MonetaryAccountBank.
     *
     * @var int
     */
    protected $userId;

    /**
     * The profiles of the account.
     *
     * @var MonetaryAccountProfileApiObject
     */
    protected $monetaryAccountProfile;

    /**
     * The legal name of the user / company using this monetary account.
     *
     * @var string
     */
    protected $displayName;

    /**
     * The settings of the MonetaryAccount.
     *
     * @var MonetaryAccountSettingObject
     */
    protected $setting;

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
     * The budgets of the MonetaryAccount.
     *
     * @var MonetaryAccountBudgetApiObject[]
     */
    protected $budget;

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
     * The currency of the MonetaryAccountBank as an ISO 4217 formatted currency code.
     *
     * @var string
     */
    protected $currencyFieldForRequest;

    /**
     * The description of the MonetaryAccountBank. Defaults to 'bunq account'.
     *
     * @var string|null
     */
    protected $descriptionFieldForRequest;

    /**
     * The daily spending limit Amount of the MonetaryAccountBank. Defaults to 1000 EUR. Currency must match the
     * MonetaryAccountBank's currency. Limited to 10000 EUR.
     *
     * @var AmountObject|null
     */
    protected $dailyLimitFieldForRequest;

    /**
     * The UUID of the Avatar of the MonetaryAccountBank.
     *
     * @var string|null
     */
    protected $avatarUuidFieldForRequest;

    /**
     * The status of the MonetaryAccountBank. Ignored in POST requests (always set to ACTIVE) can be CANCELLED or
     * PENDING_REOPEN in PUT requests to cancel (close) or reopen the MonetaryAccountBank. When updating the status and/or
     * sub_status no other fields can be updated in the same request (and vice versa).
     *
     * @var string|null
     */
    protected $statusFieldForRequest;

    /**
     * The sub-status of the MonetaryAccountBank providing extra information regarding the status. Should be ignored for POST
     * requests. In case of PUT requests with status CANCELLED it can only be REDEMPTION_VOLUNTARY, while with status
     * PENDING_REOPEN it can only be NONE. When updating the status and/or sub_status no other fields can be updated in the
     * same request (and vice versa).
     *
     * @var string|null
     */
    protected $subStatusFieldForRequest;

    /**
     * The reason for voluntarily cancelling (closing) the MonetaryAccountBank, can only be OTHER. Should only be specified if
     * updating the status to CANCELLED.
     *
     * @var string|null
     */
    protected $reasonFieldForRequest;

    /**
     * The optional free-form reason for voluntarily cancelling (closing) the MonetaryAccountBank. Can be any user provided
     * message. Should only be specified if updating the status to CANCELLED.
     *
     * @var string|null
     */
    protected $reasonDescriptionFieldForRequest;

    /**
     * The legal name of the user / company using this monetary account.
     *
     * @var string|null
     */
    protected $displayNameFieldForRequest;

    /**
     * The settings of the MonetaryAccountBank.
     *
     * @var MonetaryAccountSettingObject|null
     */
    protected $settingFieldForRequest;

    /**
     * The country of the monetary account IBAN.
     *
     * @var string|null
     */
    protected $countryIbanFieldForRequest;

    /**
     * @param string $currency The currency of the MonetaryAccountBank as an ISO 4217 formatted currency code.
     * @param string|null $description The description of the MonetaryAccountBank. Defaults to 'bunq account'.
     * @param AmountObject|null $dailyLimit The daily spending limit Amount of the MonetaryAccountBank. Defaults to 1000 EUR.
     * Currency must match the MonetaryAccountBank's currency. Limited to 10000 EUR.
     * @param string|null $avatarUuid The UUID of the Avatar of the MonetaryAccountBank.
     * @param string|null $status The status of the MonetaryAccountBank. Ignored in POST requests (always set to ACTIVE) can be
     * CANCELLED or PENDING_REOPEN in PUT requests to cancel (close) or reopen the MonetaryAccountBank. When updating the
     * status and/or sub_status no other fields can be updated in the same request (and vice versa).
     * @param string|null $subStatus The sub-status of the MonetaryAccountBank providing extra information regarding the
     * status. Should be ignored for POST requests. In case of PUT requests with status CANCELLED it can only be
     * REDEMPTION_VOLUNTARY, while with status PENDING_REOPEN it can only be NONE. When updating the status and/or sub_status
     * no other fields can be updated in the same request (and vice versa).
     * @param string|null $reason The reason for voluntarily cancelling (closing) the MonetaryAccountBank, can only be OTHER.
     * Should only be specified if updating the status to CANCELLED.
     * @param string|null $reasonDescription The optional free-form reason for voluntarily cancelling (closing) the
     * MonetaryAccountBank. Can be any user provided message. Should only be specified if updating the status to CANCELLED.
     * @param string|null $displayName The legal name of the user / company using this monetary account.
     * @param MonetaryAccountSettingObject|null $setting The settings of the MonetaryAccountBank.
     * @param string|null $countryIban The country of the monetary account IBAN.
     */
    public function __construct(string  $currency, string  $description = null, AmountObject  $dailyLimit = null, string  $avatarUuid = null, string  $status = null, string  $subStatus = null, string  $reason = null, string  $reasonDescription = null, string  $displayName = null, MonetaryAccountSettingObject  $setting = null, string  $countryIban = null)
    {
        $this->currencyFieldForRequest = $currency;
        $this->descriptionFieldForRequest = $description;
        $this->dailyLimitFieldForRequest = $dailyLimit;
        $this->avatarUuidFieldForRequest = $avatarUuid;
        $this->statusFieldForRequest = $status;
        $this->subStatusFieldForRequest = $subStatus;
        $this->reasonFieldForRequest = $reason;
        $this->reasonDescriptionFieldForRequest = $reasonDescription;
        $this->displayNameFieldForRequest = $displayName;
        $this->settingFieldForRequest = $setting;
        $this->countryIbanFieldForRequest = $countryIban;
    }

    /**
     * Create new MonetaryAccountBank.
     *
     * @param string $currency The currency of the MonetaryAccountBank as an ISO 4217 formatted currency code.
     * @param string|null $description The description of the MonetaryAccountBank. Defaults to 'bunq account'.
     * @param AmountObject|null $dailyLimit The daily spending limit Amount of the MonetaryAccountBank. Defaults to 1000 EUR.
     * Currency must match the MonetaryAccountBank's currency. Limited to 10000 EUR.
     * @param string|null $avatarUuid The UUID of the Avatar of the MonetaryAccountBank.
     * @param string|null $status The status of the MonetaryAccountBank. Ignored in POST requests (always set to ACTIVE) can be
     * CANCELLED or PENDING_REOPEN in PUT requests to cancel (close) or reopen the MonetaryAccountBank. When updating the
     * status and/or sub_status no other fields can be updated in the same request (and vice versa).
     * @param string|null $subStatus The sub-status of the MonetaryAccountBank providing extra information regarding the
     * status. Should be ignored for POST requests. In case of PUT requests with status CANCELLED it can only be
     * REDEMPTION_VOLUNTARY, while with status PENDING_REOPEN it can only be NONE. When updating the status and/or sub_status
     * no other fields can be updated in the same request (and vice versa).
     * @param string|null $reason The reason for voluntarily cancelling (closing) the MonetaryAccountBank, can only be OTHER.
     * Should only be specified if updating the status to CANCELLED.
     * @param string|null $reasonDescription The optional free-form reason for voluntarily cancelling (closing) the
     * MonetaryAccountBank. Can be any user provided message. Should only be specified if updating the status to CANCELLED.
     * @param string|null $displayName The legal name of the user / company using this monetary account.
     * @param MonetaryAccountSettingObject|null $setting The settings of the MonetaryAccountBank.
     * @param string|null $countryIban The country of the monetary account IBAN.
     * @param string[] $customHeaders
     *
     * @return BunqResponseInt
     */
    public static function create(string  $currency, string  $description = null, AmountObject  $dailyLimit = null, string  $avatarUuid = null, string  $status = null, string  $subStatus = null, string  $reason = null, string  $reasonDescription = null, string  $displayName = null, MonetaryAccountSettingObject  $setting = null, string  $countryIban = null, array $customHeaders = []): BunqResponseInt
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->post(
            vsprintf(
                self::ENDPOINT_URL_CREATE,
                [static::determineUserId()]
            ),
            [self::FIELD_CURRENCY => $currency,
self::FIELD_DESCRIPTION => $description,
self::FIELD_DAILY_LIMIT => $dailyLimit,
self::FIELD_AVATAR_UUID => $avatarUuid,
self::FIELD_STATUS => $status,
self::FIELD_SUB_STATUS => $subStatus,
self::FIELD_REASON => $reason,
self::FIELD_REASON_DESCRIPTION => $reasonDescription,
self::FIELD_DISPLAY_NAME => $displayName,
self::FIELD_SETTING => $setting,
self::FIELD_COUNTRY_IBAN => $countryIban],
            $customHeaders
        );

        return BunqResponseInt::castFromBunqResponse(
            static::processForId($responseRaw)
        );
    }

    /**
     * Get a specific MonetaryAccountBank.
     *
     * @param int $monetaryAccountBankId
     * @param string[] $customHeaders
     *
     * @return BunqResponseMonetaryAccountBank
     */
    public static function get(int $monetaryAccountBankId, array $customHeaders = []): BunqResponseMonetaryAccountBank
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_READ,
                [static::determineUserId(), $monetaryAccountBankId]
            ),
            [],
            $customHeaders
        );

        return BunqResponseMonetaryAccountBank::castFromBunqResponse(
            static::fromJson($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * Update a specific existing MonetaryAccountBank.
     *
     * @param int $monetaryAccountBankId
     * @param string|null $description The description of the MonetaryAccountBank. Defaults to 'bunq account'.
     * @param AmountObject|null $dailyLimit The daily spending limit Amount of the MonetaryAccountBank. Defaults to 1000 EUR.
     * Currency must match the MonetaryAccountBank's currency. Limited to 10000 EUR.
     * @param string|null $avatarUuid The UUID of the Avatar of the MonetaryAccountBank.
     * @param string|null $status The status of the MonetaryAccountBank. Ignored in POST requests (always set to ACTIVE) can be
     * CANCELLED or PENDING_REOPEN in PUT requests to cancel (close) or reopen the MonetaryAccountBank. When updating the
     * status and/or sub_status no other fields can be updated in the same request (and vice versa).
     * @param string|null $subStatus The sub-status of the MonetaryAccountBank providing extra information regarding the
     * status. Should be ignored for POST requests. In case of PUT requests with status CANCELLED it can only be
     * REDEMPTION_VOLUNTARY, while with status PENDING_REOPEN it can only be NONE. When updating the status and/or sub_status
     * no other fields can be updated in the same request (and vice versa).
     * @param string|null $reason The reason for voluntarily cancelling (closing) the MonetaryAccountBank, can only be OTHER.
     * Should only be specified if updating the status to CANCELLED.
     * @param string|null $reasonDescription The optional free-form reason for voluntarily cancelling (closing) the
     * MonetaryAccountBank. Can be any user provided message. Should only be specified if updating the status to CANCELLED.
     * @param string|null $displayName The legal name of the user / company using this monetary account.
     * @param MonetaryAccountSettingObject|null $setting The settings of the MonetaryAccountBank.
     * @param string[] $customHeaders
     *
     * @return BunqResponseInt
     */
    public static function update(int $monetaryAccountBankId, string  $description = null, AmountObject  $dailyLimit = null, string  $avatarUuid = null, string  $status = null, string  $subStatus = null, string  $reason = null, string  $reasonDescription = null, string  $displayName = null, MonetaryAccountSettingObject  $setting = null, array $customHeaders = []): BunqResponseInt
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->put(
            vsprintf(
                self::ENDPOINT_URL_UPDATE,
                [static::determineUserId(), $monetaryAccountBankId]
            ),
            [self::FIELD_DESCRIPTION => $description,
self::FIELD_DAILY_LIMIT => $dailyLimit,
self::FIELD_AVATAR_UUID => $avatarUuid,
self::FIELD_STATUS => $status,
self::FIELD_SUB_STATUS => $subStatus,
self::FIELD_REASON => $reason,
self::FIELD_REASON_DESCRIPTION => $reasonDescription,
self::FIELD_DISPLAY_NAME => $displayName,
self::FIELD_SETTING => $setting],
            $customHeaders
        );

        return BunqResponseInt::castFromBunqResponse(
            static::processForId($responseRaw)
        );
    }

    /**
     * Gets a listing of all MonetaryAccountBanks of a given user.
     *
     * This method is called "listing" because "list" is a restricted PHP word and cannot be used as constants, class names,
     * function or method names.
     *
     * @param string[] $params
     * @param string[] $customHeaders
     *
     * @return BunqResponseMonetaryAccountBankApiObjectList
     */
    public static function listing( array $params = [], array $customHeaders = []): BunqResponseMonetaryAccountBankApiObjectList
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

        return BunqResponseMonetaryAccountBankApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * The id of the MonetaryAccountBank.
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
     * The timestamp of the MonetaryAccountBank's creation.
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
     * The timestamp of the MonetaryAccountBank's last update.
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
     * The Avatar of the MonetaryAccountBank.
     *
     * @return AvatarObject
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AvatarObject $avatar
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
    }

    /**
     * The currency of the MonetaryAccountBank as an ISO 4217 formatted currency code.
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
     * The description of the MonetaryAccountBank. Defaults to 'bunq account'.
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
     * The daily spending limit Amount of the MonetaryAccountBank. Defaults to 1000 EUR. Currency must match the
     * MonetaryAccountBank's currency. Limited to 10000 EUR.
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
     * The maximum Amount the MonetaryAccountBank can be 'in the red'.
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
     * The MonetaryAccountBank's public UUID.
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
     * The status of the MonetaryAccountBank. Can be: ACTIVE, BLOCKED, CANCELLED or PENDING_REOPEN
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
     * The sub-status of the MonetaryAccountBank providing extra information regarding the status. Will be NONE for ACTIVE or
     * PENDING_REOPEN, COMPLETELY or ONLY_ACCEPTING_INCOMING for BLOCKED and REDEMPTION_INVOLUNTARY, REDEMPTION_VOLUNTARY or
     * PERMANENT for CANCELLED.
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
     * The id of the User who owns the MonetaryAccountBank.
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
     * The legal name of the user / company using this monetary account.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $displayName
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
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

        if (!is_null($this->avatar)) {
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

        if (!is_null($this->reason)) {
            return false;
        }

        if (!is_null($this->reasonDescription)) {
            return false;
        }

        if (!is_null($this->share)) {
            return false;
        }

        if (!is_null($this->relationUser)) {
            return false;
        }

        if (!is_null($this->userId)) {
            return false;
        }

        if (!is_null($this->monetaryAccountProfile)) {
            return false;
        }

        if (!is_null($this->displayName)) {
            return false;
        }

        if (!is_null($this->setting)) {
            return false;
        }

        if (!is_null($this->allAutoSaveId)) {
            return false;
        }

        if (!is_null($this->fulfillments)) {
            return false;
        }

        if (!is_null($this->budget)) {
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
