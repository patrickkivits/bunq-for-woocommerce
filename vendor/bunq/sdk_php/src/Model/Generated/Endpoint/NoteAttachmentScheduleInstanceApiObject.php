<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\AttachmentMonetaryAccountPaymentObject;
use bunq\Model\Generated\Object\LabelUserObject;

/**
 * Used to manage attachment notes.
 *
 * @generated
 */
class NoteAttachmentScheduleInstanceApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_CREATE = 'user/%s/monetary-account/%s/schedule/%s/schedule-instance/%s/note-attachment';
    const ENDPOINT_URL_UPDATE = 'user/%s/monetary-account/%s/schedule/%s/schedule-instance/%s/note-attachment/%s';
    const ENDPOINT_URL_DELETE = 'user/%s/monetary-account/%s/schedule/%s/schedule-instance/%s/note-attachment/%s';
    const ENDPOINT_URL_LISTING = 'user/%s/monetary-account/%s/schedule/%s/schedule-instance/%s/note-attachment';
    const ENDPOINT_URL_READ = 'user/%s/monetary-account/%s/schedule/%s/schedule-instance/%s/note-attachment/%s';

    /**
     * Field constants.
     */
    const FIELD_DESCRIPTION = 'description';
    const FIELD_ATTACHMENT_ID = 'attachment_id';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'NoteAttachment';

    /**
     * The id of the note.
     *
     * @var int
     */
    protected $id;

    /**
     * The timestamp of the note's creation.
     *
     * @var string
     */
    protected $created;

    /**
     * The timestamp of the note's last update.
     *
     * @var string
     */
    protected $updated;

    /**
     * The label of the user who created this note.
     *
     * @var LabelUserObject
     */
    protected $labelUserCreator;

    /**
     * Optional description of the attachment.
     *
     * @var string
     */
    protected $description;

    /**
     * The attachment attached to the note.
     *
     * @var AttachmentMonetaryAccountPaymentObject[]
     */
    protected $attachment;

    /**
     * Optional description of the attachment.
     *
     * @var string|null
     */
    protected $descriptionFieldForRequest;

    /**
     * The reference to the uploaded file to attach to this note.
     *
     * @var int
     */
    protected $attachmentIdFieldForRequest;

    /**
     * @param int $attachmentId The reference to the uploaded file to attach to this note.
     * @param string|null $description Optional description of the attachment.
     */
    public function __construct(int  $attachmentId, string  $description = null)
    {
        $this->descriptionFieldForRequest = $description;
        $this->attachmentIdFieldForRequest = $attachmentId;
    }

    /**
     * @param int $scheduleId
     * @param int $scheduleInstanceId
     * @param int $attachmentId The reference to the uploaded file to attach to this note.
     * @param int|null $monetaryAccountId
     * @param string|null $description Optional description of the attachment.
     * @param string[] $customHeaders
     *
     * @return BunqResponseInt
     */
    public static function create(int $scheduleId, int $scheduleInstanceId, int  $attachmentId, int $monetaryAccountId = null, string  $description = null, array $customHeaders = []): BunqResponseInt
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->post(
            vsprintf(
                self::ENDPOINT_URL_CREATE,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $scheduleId, $scheduleInstanceId]
            ),
            [self::FIELD_DESCRIPTION => $description,
self::FIELD_ATTACHMENT_ID => $attachmentId],
            $customHeaders
        );

        return BunqResponseInt::castFromBunqResponse(
            static::processForId($responseRaw)
        );
    }

    /**
     * @param int $scheduleId
     * @param int $scheduleInstanceId
     * @param int $noteAttachmentScheduleInstanceId
     * @param int|null $monetaryAccountId
     * @param string|null $description Optional description of the attachment.
     * @param string[] $customHeaders
     *
     * @return BunqResponseInt
     */
    public static function update(int $scheduleId, int $scheduleInstanceId, int $noteAttachmentScheduleInstanceId, int $monetaryAccountId = null, string  $description = null, array $customHeaders = []): BunqResponseInt
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->put(
            vsprintf(
                self::ENDPOINT_URL_UPDATE,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $scheduleId, $scheduleInstanceId, $noteAttachmentScheduleInstanceId]
            ),
            [self::FIELD_DESCRIPTION => $description],
            $customHeaders
        );

        return BunqResponseInt::castFromBunqResponse(
            static::processForId($responseRaw)
        );
    }

    /**
     * @param string[] $customHeaders
     * @param int $scheduleId
     * @param int $scheduleInstanceId
     * @param int $noteAttachmentScheduleInstanceId
     *
     * @return BunqResponseNull
     */
    public static function delete(int $scheduleId, int $scheduleInstanceId, int $noteAttachmentScheduleInstanceId, int $monetaryAccountId = null, array $customHeaders = []): BunqResponseNull
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->delete(
            vsprintf(
                self::ENDPOINT_URL_DELETE,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $scheduleId, $scheduleInstanceId, $noteAttachmentScheduleInstanceId]
            ),
            $customHeaders
        );

        return BunqResponseNull::castFromBunqResponse(
            new BunqResponse(null, $responseRaw->getHeaders())
        );
    }

    /**
     * Manage the notes for a given user.
     *
     * This method is called "listing" because "list" is a restricted PHP word and cannot be used as constants, class names,
     * function or method names.
     *
     * @param int $scheduleId
     * @param int $scheduleInstanceId
     * @param int|null $monetaryAccountId
     * @param string[] $params
     * @param string[] $customHeaders
     *
     * @return BunqResponseNoteAttachmentScheduleInstanceApiObjectList
     */
    public static function listing(int $scheduleId, int $scheduleInstanceId, int $monetaryAccountId = null, array $params = [], array $customHeaders = []): BunqResponseNoteAttachmentScheduleInstanceApiObjectList
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_LISTING,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $scheduleId, $scheduleInstanceId]
            ),
            $params,
            $customHeaders
        );

        return BunqResponseNoteAttachmentScheduleInstanceApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * @param int $scheduleId
     * @param int $scheduleInstanceId
     * @param int $noteAttachmentScheduleInstanceId
     * @param int|null $monetaryAccountId
     * @param string[] $customHeaders
     *
     * @return BunqResponseNoteAttachmentScheduleInstance
     */
    public static function get(int $scheduleId, int $scheduleInstanceId, int $noteAttachmentScheduleInstanceId, int $monetaryAccountId = null, array $customHeaders = []): BunqResponseNoteAttachmentScheduleInstance
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_READ,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $scheduleId, $scheduleInstanceId, $noteAttachmentScheduleInstanceId]
            ),
            [],
            $customHeaders
        );

        return BunqResponseNoteAttachmentScheduleInstance::castFromBunqResponse(
            static::fromJson($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * The id of the note.
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
     * The timestamp of the note's creation.
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
     * The timestamp of the note's last update.
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
     * The label of the user who created this note.
     *
     * @return LabelUserObject
     */
    public function getLabelUserCreator()
    {
        return $this->labelUserCreator;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param LabelUserObject $labelUserCreator
     */
    public function setLabelUserCreator($labelUserCreator)
    {
        $this->labelUserCreator = $labelUserCreator;
    }

    /**
     * Optional description of the attachment.
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
     * The attachment attached to the note.
     *
     * @return AttachmentMonetaryAccountPaymentObject[]
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param AttachmentMonetaryAccountPaymentObject[] $attachment
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;
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

        if (!is_null($this->labelUserCreator)) {
            return false;
        }

        if (!is_null($this->description)) {
            return false;
        }

        if (!is_null($this->attachment)) {
            return false;
        }

        return true;
    }
}
