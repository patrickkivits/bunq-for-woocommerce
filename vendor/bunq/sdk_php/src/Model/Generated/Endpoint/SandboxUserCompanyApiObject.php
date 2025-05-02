<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;

/**
 * Used to create a sandbox userCompany.
 *
 * @generated
 */
class SandboxUserCompanyApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_CREATE = 'sandbox-user-company';

    /**
     * Object type.
     */
    const OBJECT_TYPE_POST = 'ApiKey';

    /**
     * The API key of the newly created sandbox userCompany.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * The user which was created.
     *
     * @var UserApiObject
     */
    protected $user;

    /**
     * The login code which the developer can use to log into their sandbox user.
     *
     * @var string
     */
    protected $loginCode;

    /**
     * @param string[] $customHeaders
     *
     * @return BunqResponseSandboxUserCompany
     */
    public static function create( array $customHeaders = []): BunqResponseSandboxUserCompany
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->post(
            vsprintf(
                self::ENDPOINT_URL_CREATE,
                []
            ),
            [],
            $customHeaders
        );

        return BunqResponseSandboxUserCompany::castFromBunqResponse(
            static::fromJson($responseRaw, self::OBJECT_TYPE_POST)
        );
    }

    /**
     * The API key of the newly created sandbox userCompany.
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * The user which was created.
     *
     * @return UserApiObject
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param UserApiObject $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * The login code which the developer can use to log into their sandbox user.
     *
     * @return string
     */
    public function getLoginCode()
    {
        return $this->loginCode;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $loginCode
     */
    public function setLoginCode($loginCode)
    {
        $this->loginCode = $loginCode;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->apiKey)) {
            return false;
        }

        if (!is_null($this->user)) {
            return false;
        }

        if (!is_null($this->loginCode)) {
            return false;
        }

        return true;
    }
}
