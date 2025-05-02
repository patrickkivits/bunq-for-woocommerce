<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\SchedulePaymentEntryObject;

/**
 * Endpoint for schedule payment batches.
 *
 * @generated
 */
class SchedulePaymentBatchApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_READ = 'user/%s/monetary-account/%s/schedule-payment-batch/%s';
    const ENDPOINT_URL_CREATE = 'user/%s/monetary-account/%s/schedule-payment-batch';
    const ENDPOINT_URL_UPDATE = 'user/%s/monetary-account/%s/schedule-payment-batch/%s';
    const ENDPOINT_URL_DELETE = 'user/%s/monetary-account/%s/schedule-payment-batch/%s';

    /**
     * Field constants.
     */
    const FIELD_PAYMENTS = 'payments';
    const FIELD_SCHEDULE = 'schedule';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'ScheduledPaymentBatch';

    /**
     * The payment details.
     *
     * @var SchedulePaymentEntryObject[]
     */
    protected $payments;

    /**
     * The schedule details.
     *
     * @var ScheduleApiObject
     */
    protected $schedule;

    /**
     * The payment details.
     *
     * @var SchedulePaymentEntryObject[]
     */
    protected $paymentsFieldForRequest;

    /**
     * The schedule details when creating a scheduled payment.
     *
     * @var ScheduleApiObject
     */
    protected $scheduleFieldForRequest;

    /**
     * @param SchedulePaymentEntryObject[] $payments The payment details.
     * @param ScheduleApiObject $schedule The schedule details when creating a scheduled payment.
     */
    public function __construct(array  $payments, ScheduleApiObject  $schedule)
    {
        $this->paymentsFieldForRequest = $payments;
        $this->scheduleFieldForRequest = $schedule;
    }

    /**
     * @param int $schedulePaymentBatchId
     * @param int|null $monetaryAccountId
     * @param string[] $customHeaders
     *
     * @return BunqResponseSchedulePaymentBatch
     */
    public static function get(int $schedulePaymentBatchId, int $monetaryAccountId = null, array $customHeaders = []): BunqResponseSchedulePaymentBatch
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_READ,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $schedulePaymentBatchId]
            ),
            [],
            $customHeaders
        );

        return BunqResponseSchedulePaymentBatch::castFromBunqResponse(
            static::fromJson($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * @param SchedulePaymentEntryObject[] $payments The payment details.
     * @param ScheduleApiObject $schedule The schedule details when creating a scheduled payment.
     * @param int|null $monetaryAccountId
     * @param string[] $customHeaders
     *
     * @return BunqResponseInt
     */
    public static function create(array  $payments, ScheduleApiObject  $schedule, int $monetaryAccountId = null, array $customHeaders = []): BunqResponseInt
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->post(
            vsprintf(
                self::ENDPOINT_URL_CREATE,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId)]
            ),
            [self::FIELD_PAYMENTS => $payments,
self::FIELD_SCHEDULE => $schedule],
            $customHeaders
        );

        return BunqResponseInt::castFromBunqResponse(
            static::processForId($responseRaw)
        );
    }

    /**
     * @param int $schedulePaymentBatchId
     * @param int|null $monetaryAccountId
     * @param SchedulePaymentEntryObject[]|null $payments The payment details.
     * @param ScheduleApiObject|null $schedule The schedule details when creating a scheduled payment.
     * @param string[] $customHeaders
     *
     * @return BunqResponseInt
     */
    public static function update(int $schedulePaymentBatchId, int $monetaryAccountId = null, array  $payments = null, ScheduleApiObject  $schedule = null, array $customHeaders = []): BunqResponseInt
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->put(
            vsprintf(
                self::ENDPOINT_URL_UPDATE,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $schedulePaymentBatchId]
            ),
            [self::FIELD_PAYMENTS => $payments,
self::FIELD_SCHEDULE => $schedule],
            $customHeaders
        );

        return BunqResponseInt::castFromBunqResponse(
            static::processForId($responseRaw)
        );
    }

    /**
     * @param string[] $customHeaders
     * @param int $schedulePaymentBatchId
     *
     * @return BunqResponseNull
     */
    public static function delete(int $schedulePaymentBatchId, int $monetaryAccountId = null, array $customHeaders = []): BunqResponseNull
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->delete(
            vsprintf(
                self::ENDPOINT_URL_DELETE,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $schedulePaymentBatchId]
            ),
            $customHeaders
        );

        return BunqResponseNull::castFromBunqResponse(
            new BunqResponse(null, $responseRaw->getHeaders())
        );
    }

    /**
     * The payment details.
     *
     * @return SchedulePaymentEntryObject[]
     */
    public function getPayments()
    {
        return $this->payments;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param SchedulePaymentEntryObject[] $payments
     */
    public function setPayments($payments)
    {
        $this->payments = $payments;
    }

    /**
     * The schedule details.
     *
     * @return ScheduleApiObject
     */
    public function getSchedule()
    {
        return $this->schedule;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param ScheduleApiObject $schedule
     */
    public function setSchedule($schedule)
    {
        $this->schedule = $schedule;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->payments)) {
            return false;
        }

        if (!is_null($this->schedule)) {
            return false;
        }

        return true;
    }
}
