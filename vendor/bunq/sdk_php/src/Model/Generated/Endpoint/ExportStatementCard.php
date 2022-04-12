<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;

/**
 * Used to create new and read existing card statement exports. Statement
 * exports can be created in either CSV or PDF file format.
 *
 * @generated
 */
class ExportStatementCard extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_READ = 'user/%s/card/%s/export-statement-card/%s';
    const ENDPOINT_URL_LISTING = 'user/%s/card/%s/export-statement-card';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'ExportStatementCard';

    /**
     * The id of the customer statement model.
     *
     * @var int
     */
    protected $id;

    /**
     * The timestamp of the statement model's creation.
     *
     * @var string
     */
    protected $created;

    /**
     * The timestamp of the statement model's last update.
     *
     * @var string
     */
    protected $updated;

    /**
     * The date from when this statement shows transactions.
     *
     * @var string
     */
    protected $dateStart;

    /**
     * The date until which statement shows transactions.
     *
     * @var string
     */
    protected $dateEnd;

    /**
     * The status of the export.
     *
     * @var string
     */
    protected $status;

    /**
     * The regional format of a CSV statement.
     *
     * @var string
     */
    protected $regionalFormat;

    /**
     * The card for which this statement was created.
     *
     * @var int
     */
    protected $cardId;

    /**
     * @param int $cardId
     * @param int $exportStatementCardId
     * @param string[] $customHeaders
     *
     * @return BunqResponseExportStatementCard
     */
    public static function get(int $cardId, int $exportStatementCardId, array $customHeaders = []): BunqResponseExportStatementCard
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_READ,
                [static::determineUserId(), $cardId, $exportStatementCardId]
            ),
            [],
            $customHeaders
        );

        return BunqResponseExportStatementCard::castFromBunqResponse(
            static::fromJson($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * This method is called "listing" because "list" is a restricted PHP word
     * and cannot be used as constants, class names, function or method names.
     *
     * @param int $cardId
     * @param string[] $params
     * @param string[] $customHeaders
     *
     * @return BunqResponseExportStatementCardList
     */
    public static function listing(int $cardId, array $params = [], array $customHeaders = []): BunqResponseExportStatementCardList
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_LISTING,
                [static::determineUserId(), $cardId]
            ),
            $params,
            $customHeaders
        );

        return BunqResponseExportStatementCardList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * The id of the customer statement model.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @deprecated User should not be able to set values via setters, use
     * constructor.
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * The timestamp of the statement model's creation.
     *
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @deprecated User should not be able to set values via setters, use
     * constructor.
     *
     * @param string $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * The timestamp of the statement model's last update.
     *
     * @return string
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @deprecated User should not be able to set values via setters, use
     * constructor.
     *
     * @param string $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * The date from when this statement shows transactions.
     *
     * @return string
     */
    public function getDateStart()
    {
        return $this->dateStart;
    }

    /**
     * @deprecated User should not be able to set values via setters, use
     * constructor.
     *
     * @param string $dateStart
     */
    public function setDateStart($dateStart)
    {
        $this->dateStart = $dateStart;
    }

    /**
     * The date until which statement shows transactions.
     *
     * @return string
     */
    public function getDateEnd()
    {
        return $this->dateEnd;
    }

    /**
     * @deprecated User should not be able to set values via setters, use
     * constructor.
     *
     * @param string $dateEnd
     */
    public function setDateEnd($dateEnd)
    {
        $this->dateEnd = $dateEnd;
    }

    /**
     * The status of the export.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @deprecated User should not be able to set values via setters, use
     * constructor.
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * The regional format of a CSV statement.
     *
     * @return string
     */
    public function getRegionalFormat()
    {
        return $this->regionalFormat;
    }

    /**
     * @deprecated User should not be able to set values via setters, use
     * constructor.
     *
     * @param string $regionalFormat
     */
    public function setRegionalFormat($regionalFormat)
    {
        $this->regionalFormat = $regionalFormat;
    }

    /**
     * The card for which this statement was created.
     *
     * @return int
     */
    public function getCardId()
    {
        return $this->cardId;
    }

    /**
     * @deprecated User should not be able to set values via setters, use
     * constructor.
     *
     * @param int $cardId
     */
    public function setCardId($cardId)
    {
        $this->cardId = $cardId;
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

        if (!is_null($this->dateStart)) {
            return false;
        }

        if (!is_null($this->dateEnd)) {
            return false;
        }

        if (!is_null($this->status)) {
            return false;
        }

        if (!is_null($this->regionalFormat)) {
            return false;
        }

        if (!is_null($this->cardId)) {
            return false;
        }

        return true;
    }
}
