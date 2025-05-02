<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AddressObject;
use bunq\Model\Generated\Object\AmountObject;
use bunq\Model\Generated\Object\AttachmentObject;
use bunq\Model\Generated\Object\GeolocationObject;
use bunq\Model\Generated\Object\LabelMonetaryAccountObject;

/**
 * Using this call you create a request for payment from an external token provided with an ideal transaction. Make sure
 * your iDEAL payments are compliant with the iDEAL standards, by following the following manual:
 * https://www.bunq.com/terms-idealstandards. It's very important to keep these points in mind when you are using the
 * endpoint to make iDEAL payments from your application.
 *
 * @generated
 */
class TokenQrRequestIdealApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_CREATE = 'user/%s/token-qr-request-ideal';

    /**
     * Field constants.
     */
    const FIELD_TOKEN = 'token';

    /**
     * Object type.
     */
    const OBJECT_TYPE_POST = 'RequestResponse';

    /**
     * The id of the RequestResponse.
     *
     * @var int
     */
    protected $id;

    /**
     * The timestamp of when the RequestResponse was responded to.
     *
     * @var string
     */
    protected $timeResponded;

    /**
     * The timestamp of when the RequestResponse expired or will expire.
     *
     * @var string
     */
    protected $timeExpiry;

    /**
     * The id of the MonetaryAccount the RequestResponse was received on.
     *
     * @var int
     */
    protected $monetaryAccountId;

    /**
     * The requested Amount.
     *
     * @var AmountObject
     */
    protected $amountInquired;

    /**
     * The Amount the RequestResponse was accepted with.
     *
     * @var AmountObject
     */
    protected $amountResponded;

    /**
     * The LabelMonetaryAccount with the public information of the MonetaryAccount this RequestResponse was received on.
     *
     * @var LabelMonetaryAccountObject
     */
    protected $alias;

    /**
     * The LabelMonetaryAccount with the public information of the MonetaryAccount that is requesting money with this
     * RequestResponse.
     *
     * @var LabelMonetaryAccountObject
     */
    protected $counterpartyAlias;

    /**
     * The description for the RequestResponse provided by the requesting party. Maximum 9000 characters.
     *
     * @var string
     */
    protected $description;

    /**
     * The Attachments attached to the RequestResponse.
     *
     * @var AttachmentObject[]
     */
    protected $attachment;

    /**
     * The status of the created RequestResponse. Can only be PENDING.
     *
     * @var string
     */
    protected $status;

    /**
     * The minimum age the user accepting the RequestResponse must have.
     *
     * @var int
     */
    protected $minimumAge;

    /**
     * Whether or not an address must be provided on accept.
     *
     * @var string
     */
    protected $requireAddress;

    /**
     * The shipping address provided by the accepting user if an address was requested.
     *
     * @var AddressObject
     */
    protected $addressShipping;

    /**
     * The billing address provided by the accepting user if an address was requested.
     *
     * @var AddressObject
     */
    protected $addressBilling;

    /**
     * The Geolocation where the RequestResponse was created.
     *
     * @var GeolocationObject
     */
    protected $geolocation;

    /**
     * The URL which the user is sent to after accepting or rejecting the Request.
     *
     * @var string
     */
    protected $redirectUrl;

    /**
     * The type of the RequestResponse. Can be only be IDEAL.
     *
     * @var string
     */
    protected $type;

    /**
     * The subtype of the RequestResponse. Can be only be NONE.
     *
     * @var string
     */
    protected $subType;

    /**
     * The whitelist id for this action or null.
     *
     * @var int
     */
    protected $eligibleWhitelistId;

    /**
     * The token passed from a site or read from a QR code.
     *
     * @var string
     */
    protected $tokenFieldForRequest;

    /**
     * @param string $token The token passed from a site or read from a QR code.
     */
    public function __construct(string  $token)
    {
        $this->tokenFieldForRequest = $token;
    }

    /**
     * Create a request from an ideal transaction.
     *
     * @param string $token The token passed from a site or read from a QR code.
     * @param string[] $customHeaders
     *
     * @return BunqResponseTokenQrRequestIdeal
     */
    public static function create(string  $token, array $customHeaders = []): BunqResponseTokenQrRequestIdeal
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->post(
            vsprintf(
                self::ENDPOINT_URL_CREATE,
                [static::determineUserId()]
            ),
            [self::FIELD_TOKEN => $token],
            $customHeaders
        );

        return BunqResponseTokenQrRequestIdeal::castFromBunqResponse(
            static::fromJson($responseRaw, self::OBJECT_TYPE_POST)
        );
    }

    /**
     * The id of the RequestResponse.
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
     * The timestamp of when the RequestResponse was responded to.
     *
     * @return string
     */
    public function getTimeResponded()
    {
        return $this->timeResponded;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $timeResponded
     */
    public function setTimeResponded($timeResponded)
    {
        $this->timeResponded = $timeResponded;
    }

    /**
     * The timestamp of when the RequestResponse expired or will expire.
     *
     * @return string
     */
    public function getTimeExpiry()
    {
        return $this->timeExpiry;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $timeExpiry
     */
    public function setTimeExpiry($timeExpiry)
    {
        $this->timeExpiry = $timeExpiry;
    }

    /**
     * The id of the MonetaryAccount the RequestResponse was received on.
     *
     * @return int
     */
    public function getMonetaryAccountId()
    {
        return $this->monetaryAccountId;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $monetaryAccountId
     */
    public function setMonetaryAccountId($monetaryAccountId)
    {
        $this->monetaryAccountId = $monetaryAccountId;
    }

    /**
     * The requested Amount.
     *
     * @return AmountObject
     */
    public function getAmountInquired()
    {
        return $this->amountInquired;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountInquired
     */
    public function setAmountInquired($amountInquired)
    {
        $this->amountInquired = $amountInquired;
    }

    /**
     * The Amount the RequestResponse was accepted with.
     *
     * @return AmountObject
     */
    public function getAmountResponded()
    {
        return $this->amountResponded;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AmountObject $amountResponded
     */
    public function setAmountResponded($amountResponded)
    {
        $this->amountResponded = $amountResponded;
    }

    /**
     * The LabelMonetaryAccount with the public information of the MonetaryAccount this RequestResponse was received on.
     *
     * @return LabelMonetaryAccountObject
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param LabelMonetaryAccountObject $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * The LabelMonetaryAccount with the public information of the MonetaryAccount that is requesting money with this
     * RequestResponse.
     *
     * @return LabelMonetaryAccountObject
     */
    public function getCounterpartyAlias()
    {
        return $this->counterpartyAlias;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param LabelMonetaryAccountObject $counterpartyAlias
     */
    public function setCounterpartyAlias($counterpartyAlias)
    {
        $this->counterpartyAlias = $counterpartyAlias;
    }

    /**
     * The description for the RequestResponse provided by the requesting party. Maximum 9000 characters.
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
     * The Attachments attached to the RequestResponse.
     *
     * @return AttachmentObject[]
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AttachmentObject[] $attachment
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * The status of the created RequestResponse. Can only be PENDING.
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
     * The minimum age the user accepting the RequestResponse must have.
     *
     * @return int
     */
    public function getMinimumAge()
    {
        return $this->minimumAge;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $minimumAge
     */
    public function setMinimumAge($minimumAge)
    {
        $this->minimumAge = $minimumAge;
    }

    /**
     * Whether or not an address must be provided on accept.
     *
     * @return string
     */
    public function getRequireAddress()
    {
        return $this->requireAddress;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $requireAddress
     */
    public function setRequireAddress($requireAddress)
    {
        $this->requireAddress = $requireAddress;
    }

    /**
     * The shipping address provided by the accepting user if an address was requested.
     *
     * @return AddressObject
     */
    public function getAddressShipping()
    {
        return $this->addressShipping;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AddressObject $addressShipping
     */
    public function setAddressShipping($addressShipping)
    {
        $this->addressShipping = $addressShipping;
    }

    /**
     * The billing address provided by the accepting user if an address was requested.
     *
     * @return AddressObject
     */
    public function getAddressBilling()
    {
        return $this->addressBilling;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AddressObject $addressBilling
     */
    public function setAddressBilling($addressBilling)
    {
        $this->addressBilling = $addressBilling;
    }

    /**
     * The Geolocation where the RequestResponse was created.
     *
     * @return GeolocationObject
     */
    public function getGeolocation()
    {
        return $this->geolocation;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param GeolocationObject $geolocation
     */
    public function setGeolocation($geolocation)
    {
        $this->geolocation = $geolocation;
    }

    /**
     * The URL which the user is sent to after accepting or rejecting the Request.
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $redirectUrl
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * The type of the RequestResponse. Can be only be IDEAL.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * The subtype of the RequestResponse. Can be only be NONE.
     *
     * @return string
     */
    public function getSubType()
    {
        return $this->subType;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $subType
     */
    public function setSubType($subType)
    {
        $this->subType = $subType;
    }

    /**
     * The whitelist id for this action or null.
     *
     * @return int
     */
    public function getEligibleWhitelistId()
    {
        return $this->eligibleWhitelistId;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param int $eligibleWhitelistId
     */
    public function setEligibleWhitelistId($eligibleWhitelistId)
    {
        $this->eligibleWhitelistId = $eligibleWhitelistId;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->id)) {
            return false;
        }

        if (!is_null($this->timeResponded)) {
            return false;
        }

        if (!is_null($this->timeExpiry)) {
            return false;
        }

        if (!is_null($this->monetaryAccountId)) {
            return false;
        }

        if (!is_null($this->amountInquired)) {
            return false;
        }

        if (!is_null($this->amountResponded)) {
            return false;
        }

        if (!is_null($this->alias)) {
            return false;
        }

        if (!is_null($this->counterpartyAlias)) {
            return false;
        }

        if (!is_null($this->description)) {
            return false;
        }

        if (!is_null($this->attachment)) {
            return false;
        }

        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->minimumAge)) {
            return false;
        }

        if (!is_null($this->requireAddress)) {
            return false;
        }

        if (!is_null($this->addressShipping)) {
            return false;
        }

        if (!is_null($this->addressBilling)) {
            return false;
        }

        if (!is_null($this->geolocation)) {
            return false;
        }

        if (!is_null($this->redirectUrl)) {
            return false;
        }

        if (!is_null($this->type)) {
            return false;
        }

        if (!is_null($this->subType)) {
            return false;
        }

        if (!is_null($this->eligibleWhitelistId)) {
            return false;
        }

        return true;
    }
}
