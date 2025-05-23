<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\ErrorObject;
use bunq\Model\Generated\Object\RequestInquiryReferenceObject;
use bunq\Model\Generated\Object\ScheduleAnchorObjectObject;
use bunq\Model\Generated\Object\ScheduleInstanceAnchorObjectObject;

/**
 * view for reading, updating and listing the scheduled instance.
 *
 * @generated
 */
class ScheduleInstanceApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_READ = 'user/%s/monetary-account/%s/schedule/%s/schedule-instance/%s';
    const ENDPOINT_URL_UPDATE = 'user/%s/monetary-account/%s/schedule/%s/schedule-instance/%s';
    const ENDPOINT_URL_LISTING = 'user/%s/monetary-account/%s/schedule/%s/schedule-instance';

    /**
     * Field constants.
     */
    const FIELD_STATE = 'state';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'ScheduledInstance';

    /**
     * The state of the scheduleInstance. (FINISHED_SUCCESSFULLY, RETRY, FAILED_USER_ERROR)
     *
     * @var string
     */
    protected $state;

    /**
     * The schedule start time (UTC).
     *
     * @var string
     */
    protected $timeStart;

    /**
     * The schedule end time (UTC).
     *
     * @var string
     */
    protected $timeEnd;

    /**
     * The message when the scheduled instance has run and failed due to user error.
     *
     * @var ErrorObject[]
     */
    protected $errorMessage;

    /**
     * The scheduled object. (Payment, PaymentBatch)
     *
     * @var ScheduleAnchorObjectObject
     */
    protected $scheduledObject;

    /**
     * The result object of this schedule instance. (Payment, PaymentBatch)
     *
     * @var ScheduleInstanceAnchorObjectObject
     */
    protected $resultObject;

    /**
     * The reference to the object used for split the bill. Can be RequestInquiry or RequestInquiryBatch
     *
     * @var RequestInquiryReferenceObject[]
     */
    protected $requestReferenceSplitTheBill;

    /**
     * Change the state of the scheduleInstance from FAILED_USER_ERROR to RETRY.
     *
     * @var string
     */
    protected $stateFieldForRequest;

    /**
     * @param string $state Change the state of the scheduleInstance from FAILED_USER_ERROR to RETRY.
     */
    public function __construct(string  $state)
    {
        $this->stateFieldForRequest = $state;
    }

    /**
     * @param int $scheduleId
     * @param int $scheduleInstanceId
     * @param int|null $monetaryAccountId
     * @param string[] $customHeaders
     *
     * @return BunqResponseScheduleInstance
     */
    public static function get(int $scheduleId, int $scheduleInstanceId, int $monetaryAccountId = null, array $customHeaders = []): BunqResponseScheduleInstance
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_READ,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $scheduleId, $scheduleInstanceId]
            ),
            [],
            $customHeaders
        );

        return BunqResponseScheduleInstance::castFromBunqResponse(
            static::fromJson($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * @param int $scheduleId
     * @param int $scheduleInstanceId
     * @param int|null $monetaryAccountId
     * @param string|null $state Change the state of the scheduleInstance from FAILED_USER_ERROR to RETRY.
     * @param string[] $customHeaders
     *
     * @return BunqResponseInt
     */
    public static function update(int $scheduleId, int $scheduleInstanceId, int $monetaryAccountId = null, string  $state = null, array $customHeaders = []): BunqResponseInt
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->put(
            vsprintf(
                self::ENDPOINT_URL_UPDATE,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $scheduleId, $scheduleInstanceId]
            ),
            [self::FIELD_STATE => $state],
            $customHeaders
        );

        return BunqResponseInt::castFromBunqResponse(
            static::processForId($responseRaw)
        );
    }

    /**
     * This method is called "listing" because "list" is a restricted PHP word and cannot be used as constants, class names,
     * function or method names.
     *
     * @param int $scheduleId
     * @param int|null $monetaryAccountId
     * @param string[] $params
     * @param string[] $customHeaders
     *
     * @return BunqResponseScheduleInstanceApiObjectList
     */
    public static function listing(int $scheduleId, int $monetaryAccountId = null, array $params = [], array $customHeaders = []): BunqResponseScheduleInstanceApiObjectList
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_LISTING,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $scheduleId]
            ),
            $params,
            $customHeaders
        );

        return BunqResponseScheduleInstanceApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * The state of the scheduleInstance. (FINISHED_SUCCESSFULLY, RETRY, FAILED_USER_ERROR)
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * The schedule start time (UTC).
     *
     * @return string
     */
    public function getTimeStart()
    {
        return $this->timeStart;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $timeStart
     */
    public function setTimeStart($timeStart)
    {
        $this->timeStart = $timeStart;
    }

    /**
     * The schedule end time (UTC).
     *
     * @return string
     */
    public function getTimeEnd()
    {
        return $this->timeEnd;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $timeEnd
     */
    public function setTimeEnd($timeEnd)
    {
        $this->timeEnd = $timeEnd;
    }

    /**
     * The message when the scheduled instance has run and failed due to user error.
     *
     * @return ErrorObject[]
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param ErrorObject[] $errorMessage
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }

    /**
     * The scheduled object. (Payment, PaymentBatch)
     *
     * @return ScheduleAnchorObjectObject
     */
    public function getScheduledObject()
    {
        return $this->scheduledObject;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param ScheduleAnchorObjectObject $scheduledObject
     */
    public function setScheduledObject($scheduledObject)
    {
        $this->scheduledObject = $scheduledObject;
    }

    /**
     * The result object of this schedule instance. (Payment, PaymentBatch)
     *
     * @return ScheduleInstanceAnchorObjectObject
     */
    public function getResultObject()
    {
        return $this->resultObject;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param ScheduleInstanceAnchorObjectObject $resultObject
     */
    public function setResultObject($resultObject)
    {
        $this->resultObject = $resultObject;
    }

    /**
     * The reference to the object used for split the bill. Can be RequestInquiry or RequestInquiryBatch
     *
     * @return RequestInquiryReferenceObject[]
     */
    public function getRequestReferenceSplitTheBill()
    {
        return $this->requestReferenceSplitTheBill;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param RequestInquiryReferenceObject[] $requestReferenceSplitTheBill
     */
    public function setRequestReferenceSplitTheBill($requestReferenceSplitTheBill)
    {
        $this->requestReferenceSplitTheBill = $requestReferenceSplitTheBill;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->state)) {
            return false;
        }

        if (!is_null($this->timeStart)) {
            return false;
        }

        if (!is_null($this->timeEnd)) {
            return false;
        }

        if (!is_null($this->errorMessage)) {
            return false;
        }

        if (!is_null($this->scheduledObject)) {
            return false;
        }

        if (!is_null($this->resultObject)) {
            return false;
        }

        if (!is_null($this->requestReferenceSplitTheBill)) {
            return false;
        }

        return true;
    }
}
