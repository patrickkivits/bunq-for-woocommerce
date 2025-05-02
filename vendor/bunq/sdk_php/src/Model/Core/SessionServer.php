<?php
namespace bunq\Model\Core;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Model\Generated\Endpoint\UserApiKeyApiObject;
use bunq\Model\Generated\Endpoint\UserCompanyApiObject;
use bunq\Model\Generated\Endpoint\UserPaymentServiceProviderApiObject;
use bunq\Model\Generated\Endpoint\UserPersonApiObject;
use bunq\Util\ModelUtil;

/**
 */
class SessionServer extends BunqModel
{
    /**
     * Field constants.
     */
    const FIELD_SECRET = 'secret';

    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_POST = 'session-server';

    /**
     * @var Token
     */
    protected $token;

    /**
     * @var Id;
     */
    protected $id;

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
     * @param ApiContext $apiContext
     *
     * @return BunqResponseSessionServer
     */
    public static function create(ApiContext $apiContext): BunqResponseSessionServer
    {
        $apiClient = new ApiClient($apiContext);
        $responseRaw = $apiClient->post(
            self::ENDPOINT_URL_POST,
            [self::FIELD_SECRET => $apiContext->getApiKey()],
            []
        );

        return BunqResponseSessionServer::castFromBunqResponse(
            static::classFromJson($responseRaw)
        );
    }

    /**
     * The Session token is the token the client has to provide in the "X-Bunq-Client-Authentication"
     * header for each API call that requires a Session (only the creation of a Installation and
     * DeviceServer don't require a Session).
     *
     * @return Token
     */
    public function getSessionToken(): Token
    {
        return $this->token;
    }

    /**
     * @return UserCompanyApiObject|null
     */
    public function getUserCompanyOrNull()
    {
        return $this->userCompany;
    }

    /**
     * @return UserPersonApiObject|null
     */
    public function getUserPersonOrNull()
    {
        return $this->userPerson;
    }

    /**
     * @return UserApiKeyApiObject|null
     */
    public function getUserApiKeyOrNull()
    {
        return $this->userApiKey;
    }

    /**
     * @return UserPaymentServiceProviderApiObject|null
     */
    public function getUserPaymentServiceProviderOrNull()
    {
        return $this->userPaymentServiceProvider;
    }

    /**
     * @return UserCompanyApiObject|UserPersonApiObject|UserApiKeyApiObject|UserPaymentServiceProviderApiObject
     */
    public function getUserReference()
    {
        return ModelUtil::getUserReference(
            $this->userPerson,
            $this->userCompany,
            $this->userApiKey,
            $this->userPaymentServiceProvider
        );
    }

    /**
     * @return bool
     */
    protected function isAllFieldNull()
    {
        if (!is_null($this->token)) {
            return false;
        }

        if (!is_null($this->id)) {
            return false;
        }

        if (!is_null($this->userCompany)) {
            return false;
        }

        if (!is_null($this->userPerson)) {
            return false;
        }

        if (!is_null($this->userApiKey)) {
            return false;
        }

        if (!is_null($this->userPaymentServiceProvider)) {
            return false;
        }

        return true;
    }
}
