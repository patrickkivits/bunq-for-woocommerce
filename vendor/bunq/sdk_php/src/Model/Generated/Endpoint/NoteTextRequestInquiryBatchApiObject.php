<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\LabelUserObject;

/**
 * Used to manage text notes.
 *
 * @generated
 */
class NoteTextRequestInquiryBatchApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_CREATE = 'user/%s/monetary-account/%s/request-inquiry-batch/%s/note-text';
    const ENDPOINT_URL_UPDATE = 'user/%s/monetary-account/%s/request-inquiry-batch/%s/note-text/%s';
    const ENDPOINT_URL_DELETE = 'user/%s/monetary-account/%s/request-inquiry-batch/%s/note-text/%s';
    const ENDPOINT_URL_LISTING = 'user/%s/monetary-account/%s/request-inquiry-batch/%s/note-text';
    const ENDPOINT_URL_READ = 'user/%s/monetary-account/%s/request-inquiry-batch/%s/note-text/%s';

    /**
     * Field constants.
     */
    const FIELD_CONTENT = 'content';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'NoteText';

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
     * The content of the note.
     *
     * @var string
     */
    protected $content;

    /**
     * The content of the note.
     *
     * @var string|null
     */
    protected $contentFieldForRequest;

    /**
     * @param string|null $content The content of the note.
     */
    public function __construct(string  $content = null)
    {
        $this->contentFieldForRequest = $content;
    }

    /**
     * @param int $requestInquiryBatchId
     * @param int|null $monetaryAccountId
     * @param string|null $content The content of the note.
     * @param string[] $customHeaders
     *
     * @return BunqResponseInt
     */
    public static function create(int $requestInquiryBatchId, int $monetaryAccountId = null, string  $content = null, array $customHeaders = []): BunqResponseInt
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->post(
            vsprintf(
                self::ENDPOINT_URL_CREATE,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $requestInquiryBatchId]
            ),
            [self::FIELD_CONTENT => $content],
            $customHeaders
        );

        return BunqResponseInt::castFromBunqResponse(
            static::processForId($responseRaw)
        );
    }

    /**
     * @param int $requestInquiryBatchId
     * @param int $noteTextRequestInquiryBatchId
     * @param int|null $monetaryAccountId
     * @param string|null $content The content of the note.
     * @param string[] $customHeaders
     *
     * @return BunqResponseInt
     */
    public static function update(int $requestInquiryBatchId, int $noteTextRequestInquiryBatchId, int $monetaryAccountId = null, string  $content = null, array $customHeaders = []): BunqResponseInt
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->put(
            vsprintf(
                self::ENDPOINT_URL_UPDATE,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $requestInquiryBatchId, $noteTextRequestInquiryBatchId]
            ),
            [self::FIELD_CONTENT => $content],
            $customHeaders
        );

        return BunqResponseInt::castFromBunqResponse(
            static::processForId($responseRaw)
        );
    }

    /**
     * @param string[] $customHeaders
     * @param int $requestInquiryBatchId
     * @param int $noteTextRequestInquiryBatchId
     *
     * @return BunqResponseNull
     */
    public static function delete(int $requestInquiryBatchId, int $noteTextRequestInquiryBatchId, int $monetaryAccountId = null, array $customHeaders = []): BunqResponseNull
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->delete(
            vsprintf(
                self::ENDPOINT_URL_DELETE,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $requestInquiryBatchId, $noteTextRequestInquiryBatchId]
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
     * @param int $requestInquiryBatchId
     * @param int|null $monetaryAccountId
     * @param string[] $params
     * @param string[] $customHeaders
     *
     * @return BunqResponseNoteTextRequestInquiryBatchApiObjectList
     */
    public static function listing(int $requestInquiryBatchId, int $monetaryAccountId = null, array $params = [], array $customHeaders = []): BunqResponseNoteTextRequestInquiryBatchApiObjectList
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_LISTING,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $requestInquiryBatchId]
            ),
            $params,
            $customHeaders
        );

        return BunqResponseNoteTextRequestInquiryBatchApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * @param int $requestInquiryBatchId
     * @param int $noteTextRequestInquiryBatchId
     * @param int|null $monetaryAccountId
     * @param string[] $customHeaders
     *
     * @return BunqResponseNoteTextRequestInquiryBatch
     */
    public static function get(int $requestInquiryBatchId, int $noteTextRequestInquiryBatchId, int $monetaryAccountId = null, array $customHeaders = []): BunqResponseNoteTextRequestInquiryBatch
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_READ,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId), $requestInquiryBatchId, $noteTextRequestInquiryBatchId]
            ),
            [],
            $customHeaders
        );

        return BunqResponseNoteTextRequestInquiryBatch::castFromBunqResponse(
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
     * The content of the note.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
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

        if (!is_null($this->content)) {
            return false;
        }

        return true;
    }
}
