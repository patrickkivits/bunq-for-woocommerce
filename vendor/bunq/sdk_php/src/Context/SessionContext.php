<?php
namespace bunq\Context;

use bunq\Model\Core\SessionServer;
use bunq\Model\Core\Token;
use bunq\Model\Generated\Endpoint\UserApiKeyApiObject;
use bunq\Model\Generated\Endpoint\UserCompanyApiObject;
use bunq\Model\Generated\Endpoint\UserPaymentServiceProviderApiObject;
use bunq\Model\Generated\Endpoint\UserPersonApiObject;
use bunq\Util\ModelUtil;
use DateTime;
use JsonSerializable;

/**
 */
class SessionContext implements JsonSerializable
{
    /**
     * Field constants.
     */
    const FIELD_TOKEN = 'token';
    const FIELD_EXPIRY_TIME = 'expiry_time';
    const FIELD_USER_ID = 'user_id';
    const FIELD_USER_PERSON = 'userPerson';
    const FIELD_USER_COMPANY = 'userCompany';
    const FIELD_USER_API_KEY = 'userApiKey';
    const FIELD_USER_PAYMENT_SERVICE_PROVIDER = 'userPaymentServiceProvider';

    /**
     * Constants for manipulating expiry timestamp.
     */
    const FORMAT_MICRO_TIME_PARTIAL = 'Y-m-d H:i:s.';
    const FORMAT_MICRO_TIME = 'Y-m-d H:i:s.u';
    const MICROSECONDS_IN_SECOND = 1000000;
    const FORMAT_MICROSECONDS = '%06d';

    /**
     * @var Token
     */
    protected $sessionToken;

    /**
     * @var DateTime
     */
    protected $expiryTime;

    /**
     * @var int
     */
    protected $userId;

    /**
     * @var UserPersonApiObject
     */
    protected $userPerson;

    /**
     * @var UserCompanyApiObject
     */
    protected $userCompany;

    /**
     * @var UserApiKeyApiObject
     */
    protected $userApiKey;

    /**
     * @var UserPaymentServiceProviderApiObject
     */
    protected $userPaymentServiceProvider;

    /**
     */
    private function __construct()
    {
    }

    /**
     * @param SessionServer $sessionServer
     *
     * @return static
     */
    public static function create(SessionServer $sessionServer): SessionContext
    {
        $sessionContext = new static();
        $sessionContext->sessionToken = $sessionServer->getSessionToken();
        $sessionContext->expiryTime = static::calculateExpiryTime($sessionServer);
        $sessionContext->userId = $sessionServer->getUserReference()->getId();
        $sessionContext->userCompany = $sessionServer->getUserCompanyOrNull();
        $sessionContext->userPerson = $sessionServer->getUserPersonOrNull();
        $sessionContext->userApiKey = $sessionServer->getUserApiKeyOrNull();
        $sessionContext->userPaymentServiceProvider = $sessionServer->getUserPaymentServiceProviderOrNull();

        return $sessionContext;
    }

    /**
     * @param SessionServer $sessionServer
     *
     * @return DateTime
     */
    private static function calculateExpiryTime(SessionServer $sessionServer): DateTime
    {
        $expiryTime = microtime(true) + static::getSessionTimeout($sessionServer);

        return static::microTimeToDateTime($expiryTime);
    }

    /**
     * @param SessionServer $sessionServer
     *
     * @return int
     */
    private static function getSessionTimeout(SessionServer $sessionServer): int
    {
        $user = $sessionServer->getUserReference();

        if ($user instanceof UserApiKeyApiObject) {
            /** @phpstan-ignore-next-line */
            return $user->getRequestedByUser()->getReferencedObject()->getSessionTimeout();
        } else {
            return $user->getSessionTimeout();
        }
    }

    /**
     * @param float $microtime
     *
     * @return DateTime
     */
    private static function microTimeToDateTime(float $microtime): DateTime
    {
        $seconds = floor($microtime);
        $microseconds = ($microtime - $seconds) * self::MICROSECONDS_IN_SECOND;
        $microsecondsFormatted = sprintf(self::FORMAT_MICROSECONDS, $microseconds);
        $dateFormatted = date(self::FORMAT_MICRO_TIME_PARTIAL . $microsecondsFormatted, $seconds);

        return new DateTime($dateFormatted);
    }

    /**
     * @param string[] $sessionContextBody
     *
     * @return static
     */
    public static function restore(array $sessionContextBody): SessionContext
    {
        $sessionContext = new static();
        $sessionContext->sessionToken = new Token($sessionContextBody[self::FIELD_TOKEN]);
        $sessionContext->expiryTime = Datetime::createFromFormat(
            self::FORMAT_MICRO_TIME,
            $sessionContextBody[self::FIELD_EXPIRY_TIME]
        );
        $sessionContext->userId = $sessionContextBody[self::FIELD_USER_ID];
        $sessionContext->userPerson = static::getUserPersonFromSessionOrNull($sessionContextBody);
        $sessionContext->userCompany = static::getUserCompanyFromSessionOrNull($sessionContextBody);
        $sessionContext->userApiKey = static::getUserApiKeyFromSessionOrNull($sessionContextBody);
        $sessionContext->userPaymentServiceProvider =
            static::getUserPaymentServiceProviderFromSessionOrNull($sessionContextBody);

        return $sessionContext;
    }

    /**
     * @param array $sessionContextBody
     *
     * @return UserPersonApiObject|null
     */
    private static function getUserPersonFromSessionOrNull(array $sessionContextBody)
    {
        if (isset($sessionContextBody[self::FIELD_USER_PERSON])) {
            return UserPersonApiObject::createFromJsonString(json_encode($sessionContextBody[self::FIELD_USER_PERSON]));
        } else {
            return null;
        }
    }

    /**
     * @param array $sessionContextBody
     *
     * @return UserCompanyApiObject|null
     */
    private static function getUserCompanyFromSessionOrNull(array $sessionContextBody)
    {
        if (isset($sessionContextBody[self::FIELD_USER_COMPANY])) {
            return UserCompanyApiObject::createFromJsonString(json_encode($sessionContextBody[self::FIELD_USER_COMPANY]));
        } else {
            return null;
        }
    }

    /**
     * @param array $sessionContextBody
     *
     * @return UserApiKeyApiObject|null
     */
    private static function getUserApiKeyFromSessionOrNull(array $sessionContextBody)
    {
        if (isset($sessionContextBody[self::FIELD_USER_API_KEY])) {
            return UserApiKeyApiObject::createFromJsonString(json_encode($sessionContextBody[self::FIELD_USER_API_KEY]));
        } else {
            return null;
        }
    }

    /**
     * @param array $sessionContextBody
     *
     * @return UserPaymentServiceProviderApiObject|null
     */
    private static function getUserPaymentServiceProviderFromSessionOrNull(array $sessionContextBody)
    {
        if (isset($sessionContextBody[self::FIELD_USER_PAYMENT_SERVICE_PROVIDER])) {
            return UserPaymentServiceProviderApiObject::createFromJsonString(
                json_encode($sessionContextBody[self::FIELD_USER_PAYMENT_SERVICE_PROVIDER])
            );
        } else {
            return null;
        }
    }

    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return [
            self::FIELD_TOKEN => $this->getSessionToken()->getToken(),
            self::FIELD_EXPIRY_TIME => $this->getExpiryTime()->format(self::FORMAT_MICRO_TIME),
            self::FIELD_USER_ID => $this->getUserId(),
            self::FIELD_USER_COMPANY => $this->getUserCompanyOrNull(),
            self::FIELD_USER_PERSON => $this->getUserPersonOrNull(),
            self::FIELD_USER_API_KEY => $this->getUserApiKeyOrNull(),
            self::FIELD_USER_PAYMENT_SERVICE_PROVIDER => $this->getUserPaymentServiceProviderOrNull(),
        ];
    }

    /**
     * @return Token
     */
    public function getSessionToken(): Token
    {
        return $this->sessionToken;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return UserPersonApiObject|null
     */
    public function getUserPersonOrNull()
    {
        return $this->userPerson;
    }

    /**
     * @return UserCompanyApiObject|null
     */
    public function getUserCompanyOrNull()
    {
        return $this->userCompany;
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
     * @return DateTime
     */
    public function getExpiryTime(): DateTime
    {
        return $this->expiryTime;
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
}
