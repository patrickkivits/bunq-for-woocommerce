<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\DraftPaymentAnchorObjectObject;
use bunq\Model\Generated\Object\DraftPaymentEntryObject;
use bunq\Model\Generated\Object\DraftPaymentResponseObject;
use bunq\Model\Generated\Object\LabelUserObject;
use bunq\Model\Generated\Object\RequestInquiryReferenceObject;

/**
 * A DraftPayment is like a regular Payment, but it needs to be accepted by the sending party before the actual Payment is
 * done.
 *
 * @generated
 */
class DraftPaymentApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_CREATE = 'user/%s/monetary-account/%s/draft-payment';
    const ENDPOINT_URL_UPDATE = 'user/%s/monetary-account/%s/draft-payment/%s';
    const ENDPOINT_URL_LISTING = 'user/%s/monetary-account/%s/draft-payment';
    const ENDPOINT_URL_READ = 'user/%s/monetary-account/%s/draft-payment/%s';

    /**
     * Field constants.
     */
    const FIELD_STATUS = 'status';
    const FIELD_ENTRIES = 'entries';
    const FIELD_PREVIOUS_UPDATED_TIMESTAMP = 'previous_updated_timestamp';
    const FIELD_NUMBER_OF_REQUIRED_ACCEPTS = 'number_of_required_accepts';
    const FIELD_SCHEDULE = 'schedule';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'DraftPayment';

    /**
     * The id of the created DrafPayment.
     *
     * @var int
     */
    protected $id;

    /**
     * The id of the MonetaryAccount the DraftPayment applies to.
     *
     * @var int
     */
    protected $monetaryAccountId;

    /**
     * The label of the User who created the DraftPayment.
     *
     * @var LabelUserObject
     */
    protected $userAliasCreated;

    /**
     * All responses to this draft payment.
     *
     * @var DraftPaymentResponseObject[]
     */
    protected $responses;

    /**
     * The status of the DraftPayment.
     *
     * @var string
     */
    protected $status;

    /**
     * The type of the DraftPayment.
     *
     * @var string
     */
    protected $type;

    /**
     * The entries in the DraftPayment.
     *
     * @var DraftPaymentEntryObject[]
     */
    protected $entries;

    /**
     * The Payment or PaymentBatch. This will only be present after the DraftPayment has been accepted.
     *
     * @var DraftPaymentAnchorObjectObject|null
     */
    protected $object;

    /**
     * The reference to the object used for split the bill. Can be RequestInquiry or RequestInquiryBatch
     *
     * @var RequestInquiryReferenceObject[]
     */
    protected $requestReferenceSplitTheBill;

    /**
     * The schedule details.
     *
     * @var ScheduleApiObject
     */
    protected $schedule;

    /**
     * The status of the DraftPayment.
     *
     * @var string|null
     */
    protected $statusFieldForRequest;

    /**
     * The list of entries in the DraftPayment. Each entry will result in a payment when the DraftPayment is accepted.
     *
     * @var DraftPaymentEntryObject[]
     */
    protected $entriesFieldForRequest;

    /**
     * The last updated_timestamp that you received for this DraftPayment. This needs to be provided to prevent race
     * conditions.
     *
     * @var string|null
     */
    protected $previousUpdatedTimestampFieldForRequest;

    /**
     * The number of accepts that are required for the draft payment to receive status ACCEPTED. Currently only 1 is valid.
     *
     * @var int
     */
    protected $numberOfRequiredAcceptsFieldForRequest;

    /**
     * The schedule details when creating or updating a scheduled payment.
     *
     * @var ScheduleApiObject|null
     */
    protected $scheduleFieldForRequest;

    /**
     * @param DraftPaymentEntryObject[] $entries The list of entries in the DraftPayment. Each entry will result in a payment
     * when the DraftPayment is accepted.
     * @param int $numberOfRequiredAccepts The number of accepts that are required for the draft payment to receive status
     * ACCEPTED. Currently only 1 is valid.
     * @param string|null $status The status of the DraftPayment.
     * @param string|null $previousUpdatedTimestamp The last updated_timestamp that you received for this DraftPayment. This
     * needs to be provided to prevent race conditions.
     * @param ScheduleApiObject|null $schedule The schedule details when creating or updating a scheduled payment.
     */
    public function __construct(array  $entries, int  $numberOfRequiredAccepts, string  $status = null, string  $previousUpdatedTimestamp = null, ScheduleApiObject  $schedule = null)
    {
        $this->statusFieldForRequest = $status;
        $this->entriesFieldForRequest = $entries;
        $this->previousUpdatedTimestampFieldForRequest = $previousUpdatedTimestamp;
        $this->numberOfRequiredAcceptsFieldForRequest = $numberOfRequiredAccepts;
        $this->scheduleFieldForRequest = $schedule;
    }

    /**
     * Create a new DraftPayment.
     *
     * @param DraftPaymentEntryObject[] $entries The list of entries in the DraftPayment. Each entry will result in a payment
     * when the DraftPayment is accepted.
     * @param int $numberOfRequiredAccepts The number of accepts that are required for the draft payment to receive status
     * ACCEPTED. Currently only 1 is valid.
     * @param int|null $monetaryAccountId
     * @param string|null $status The status of the DraftPayment.
     * @param string|null $previousUpdatedTimestamp The last updated_timestamp that you received for this DraftPayment. This
     * needs to be provided to prevent race conditions.
     * @param ScheduleApiObject|null $schedule The schedule details when creating or updating a scheduled payment.
     * @param string[] $customHeaders
     *
     * @return BunqResponseInt
     */
    public static function create(array  $entries, int  $numberOfRequiredAccepts, int $monetaryAccountId = null, string  $status = null, string  $previousUpdatedTimestamp = null, ScheduleApiObject  $schedule = null, array $customHeaders = []): BunqResponseInt
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->post(
            vsprintf(
                self::ENDPOINT_URL_CREATE,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId)]
            ),
            [self::FIELD_STATUS => $status,
self::FIELD_ENTRIES => $entries,
self::FIELD_PREVIOUS_UPDATED_TIMESTAMP => $previousUpdatedTimestamp,
self::FIELD_NUMBER_OF_REQUIRED_ACCEPTS => $numberOfRequiredAccepts,
self::FIELD_SCHEDULE => $schedule],
            $customHeaders
        );

        return BunqResponseInt::castFromBunqResponse(
            static::processForId($responseRaw)
        );
    }

    /**
     * Update a DraftPayment.
     *
     * @param int $draftPaymentId
     * @param int|null $monetaryAccountId
     * @param string|null $status The status of the DraftPayment.
     * @param string|null $previousUpdatedTimestamp The last updated_timestamp that you received for this DraftPayment. This
     * needs to be provided to prevent race conditions.
     * @param ScheduleApiObject|null $schedule The schedule details when creating or updating a scheduled payment.
     * @param string[] $customHeaders
     *
     * @return BunqResponseInt
     */
    public static function update(int $draftPaymentId, int $monetaryAccountId = null, string  $status = null, string  $previousUpdatedTimestamp = null, ScheduleApiObject  $schedule = null, array $customHeaders = []): BunqResponseInt
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->put(
            vsprintf(
                self::ENDPOINT_URL_UPDATE,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $draftPaymentId]
            ),
            [self::FIELD_STATUS => $status,
self::FIELD_PREVIOUS_UPDATED_TIMESTAMP => $previousUpdatedTimestamp,
self::FIELD_SCHEDULE => $schedule],
            $customHeaders
        );

        return BunqResponseInt::castFromBunqResponse(
            static::processForId($responseRaw)
        );
    }

    /**
     * Get a listing of all DraftPayments from a given MonetaryAccount.
     *
     * This method is called "listing" because "list" is a restricted PHP word and cannot be used as constants, class names,
     * function or method names.
     *
     * @param int|null $monetaryAccountId
     * @param string[] $params
     * @param string[] $customHeaders
     *
     * @return BunqResponseDraftPaymentApiObjectList
     */
    public static function listing(int $monetaryAccountId = null, array $params = [], array $customHeaders = []): BunqResponseDraftPaymentApiObjectList
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_LISTING,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId)]
            ),
            $params,
            $customHeaders
        );

        return BunqResponseDraftPaymentApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * Get a specific DraftPayment.
     *
     * @param int $draftPaymentId
     * @param int|null $monetaryAccountId
     * @param string[] $customHeaders
     *
     * @return BunqResponseDraftPayment
     */
    public static function get(int $draftPaymentId, int $monetaryAccountId = null, array $customHeaders = []): BunqResponseDraftPayment
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_READ,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $draftPaymentId]
            ),
            [],
            $customHeaders
        );

        return BunqResponseDraftPayment::castFromBunqResponse(
            static::fromJson($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * The id of the created DrafPayment.
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
     * The id of the MonetaryAccount the DraftPayment applies to.
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
     * The label of the User who created the DraftPayment.
     *
     * @return LabelUserObject
     */
    public function getUserAliasCreated()
    {
        return $this->userAliasCreated;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param LabelUserObject $userAliasCreated
     */
    public function setUserAliasCreated($userAliasCreated)
    {
        $this->userAliasCreated = $userAliasCreated;
    }

    /**
     * All responses to this draft payment.
     *
     * @return DraftPaymentResponseObject[]
     */
    public function getResponses()
    {
        return $this->responses;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param DraftPaymentResponseObject[] $responses
     */
    public function setResponses($responses)
    {
        $this->responses = $responses;
    }

    /**
     * The status of the DraftPayment.
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
     * The type of the DraftPayment.
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
     * The entries in the DraftPayment.
     *
     * @return DraftPaymentEntryObject[]
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param DraftPaymentEntryObject[] $entries
     */
    public function setEntries($entries)
    {
        $this->entries = $entries;
    }

    /**
     * The Payment or PaymentBatch. This will only be present after the DraftPayment has been accepted.
     *
     * @return DraftPaymentAnchorObjectObject
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param DraftPaymentAnchorObjectObject $object
     */
    public function setObject($object)
    {
        $this->object = $object;
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
        if (!is_null($this->id)) {
            return false;
        }

        if (!is_null($this->monetaryAccountId)) {
            return false;
        }

        if (!is_null($this->userAliasCreated)) {
            return false;
        }

        if (!is_null($this->responses)) {
            return false;
        }

        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->type)) {
            return false;
        }

        if (!is_null($this->entries)) {
            return false;
        }

        if (!is_null($this->object)) {
            return false;
        }

        if (!is_null($this->requestReferenceSplitTheBill)) {
            return false;
        }

        if (!is_null($this->schedule)) {
            return false;
        }

        return true;
    }
}
