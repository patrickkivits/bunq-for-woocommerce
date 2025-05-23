<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\PermittedDeviceObject;

/**
 * Create a credential of a user for server authentication, or delete the credential of a user for server authentication.
 *
 * @generated
 */
class UserCredentialPasswordIpApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_READ = 'user/%s/credential-password-ip/%s';
    const ENDPOINT_URL_LISTING = 'user/%s/credential-password-ip';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'CredentialPasswordIp';

    /**
     * The id of the credential.
     *
     * @var int
     */
    protected $id;

    /**
     * The timestamp of the credential object's creation.
     *
     * @var string
     */
    protected $created;

    /**
     * The timestamp of the credential object's last update.
     *
     * @var string
     */
    protected $updated;

    /**
     * The status of the credential.
     *
     * @var string
     */
    protected $status;

    /**
     * When the status is PENDING_FIRST_USE: when the credential expires.
     *
     * @var string
     */
    protected $expiryTime;

    /**
     * When the status is PENDING_FIRST_USE: the value of the token.
     *
     * @var string
     */
    protected $tokenValue;

    /**
     * When the status is ACTIVE: the details of the device that may use the credential.
     *
     * @var PermittedDeviceObject
     */
    protected $permittedDevice;

    /**
     * @param int $userCredentialPasswordIpId
     * @param string[] $customHeaders
     *
     * @return BunqResponseUserCredentialPasswordIp
     */
    public static function get(int $userCredentialPasswordIpId, array $customHeaders = []): BunqResponseUserCredentialPasswordIp
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_READ,
                [static::determineUserId(), $userCredentialPasswordIpId]
            ),
            [],
            $customHeaders
        );

        return BunqResponseUserCredentialPasswordIp::castFromBunqResponse(
            static::fromJson($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * This method is called "listing" because "list" is a restricted PHP word and cannot be used as constants, class names,
     * function or method names.
     *
     * @param string[] $params
     * @param string[] $customHeaders
     *
     * @return BunqResponseUserCredentialPasswordIpApiObjectList
     */
    public static function listing( array $params = [], array $customHeaders = []): BunqResponseUserCredentialPasswordIpApiObjectList
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

        return BunqResponseUserCredentialPasswordIpApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * The id of the credential.
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
     * The timestamp of the credential object's creation.
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
     * The timestamp of the credential object's last update.
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
     * The status of the credential.
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
     * When the status is PENDING_FIRST_USE: when the credential expires.
     *
     * @return string
     */
    public function getExpiryTime()
    {
        return $this->expiryTime;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $expiryTime
     */
    public function setExpiryTime($expiryTime)
    {
        $this->expiryTime = $expiryTime;
    }

    /**
     * When the status is PENDING_FIRST_USE: the value of the token.
     *
     * @return string
     */
    public function getTokenValue()
    {
        return $this->tokenValue;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $tokenValue
     */
    public function setTokenValue($tokenValue)
    {
        $this->tokenValue = $tokenValue;
    }

    /**
     * When the status is ACTIVE: the details of the device that may use the credential.
     *
     * @return PermittedDeviceObject
     */
    public function getPermittedDevice()
    {
        return $this->permittedDevice;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param PermittedDeviceObject $permittedDevice
     */
    public function setPermittedDevice($permittedDevice)
    {
        $this->permittedDevice = $permittedDevice;
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

        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->expiryTime)) {
            return false;
        }

        if (!is_null($this->tokenValue)) {
            return false;
        }

        if (!is_null($this->permittedDevice)) {
            return false;
        }

        return true;
    }
}
