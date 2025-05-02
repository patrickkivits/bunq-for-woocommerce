<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\NotificationFilterUrlObject;

/**
 * Manage the url notification filters for a user.
 *
 * @generated
 */
class NotificationFilterUrlMonetaryAccountApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_CREATE = 'user/%s/monetary-account/%s/notification-filter-url';
    const ENDPOINT_URL_LISTING = 'user/%s/monetary-account/%s/notification-filter-url';

    /**
     * Field constants.
     */
    const FIELD_NOTIFICATION_FILTERS = 'notification_filters';

    /**
     * Object type.
     */
    const OBJECT_TYPE_GET = 'NotificationFilterUrl';

    /**
     * The types of notifications that will result in a url notification for this monetary account.
     *
     * @var NotificationFilterUrlObject[]
     */
    protected $notificationFilters;

    /**
     * The types of notifications that will result in a url notification for this monetary account.
     *
     * @var NotificationFilterUrlObject[]|null
     */
    protected $notificationFiltersFieldForRequest;

    /**
     * @param NotificationFilterUrlObject[]|null $notificationFilters The types of notifications that will result in a url
     * notification for this monetary account.
     */
    public function __construct(array  $notificationFilters = null)
    {
        $this->notificationFiltersFieldForRequest = $notificationFilters;
    }

    /**
     * @param int|null $monetaryAccountId
     * @param NotificationFilterUrlObject[]|null $notificationFilters The types of notifications that will result in a url
     * notification for this monetary account.
     * @param string[] $customHeaders
     *
     * @return BunqResponseInt
     */
    public static function create(int $monetaryAccountId = null, array  $notificationFilters = null, array $customHeaders = []): BunqResponseInt
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->post(
            vsprintf(
                self::ENDPOINT_URL_CREATE,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId)]
            ),
            [self::FIELD_NOTIFICATION_FILTERS => $notificationFilters],
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
     * @param int|null $monetaryAccountId
     * @param string[] $params
     * @param string[] $customHeaders
     *
     * @return BunqResponseNotificationFilterUrlMonetaryAccountApiObjectList
     */
    public static function listing(int $monetaryAccountId = null, array $params = [], array $customHeaders = []): BunqResponseNotificationFilterUrlMonetaryAccountApiObjectList
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

        return BunqResponseNotificationFilterUrlMonetaryAccountApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * The types of notifications that will result in a url notification for this monetary account.
     *
     * @return NotificationFilterUrlObject[]
     */
    public function getNotificationFilters()
    {
        return $this->notificationFilters;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param NotificationFilterUrlObject[] $notificationFilters
     */
    public function setNotificationFilters($notificationFilters)
    {
        $this->notificationFilters = $notificationFilters;
    }

    /**
     * @return bool
     */
    public function isAllFieldNull()
    {
        if (!is_null($this->notificationFilters)) {
            return false;
        }

        return true;
    }
}
