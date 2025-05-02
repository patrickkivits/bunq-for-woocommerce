<?php
namespace bunq\Model\Generated\Endpoint;

use bunq\Context\ApiContext;
use bunq\Http\ApiClient;
use bunq\Http\BunqResponse;
use bunq\Model\Core\BunqModel;
use bunq\Model\Generated\Object\NotificationFilterPushObject;

/**
 * Manage the push notification filters for a user.
 *
 * @generated
 */
class NotificationFilterPushApiObject extends BunqModel
{
    /**
     * Endpoint constants.
     */
    const ENDPOINT_URL_CREATE = 'user/%s/notification-filter-push';
    const ENDPOINT_URL_LISTING = 'user/%s/notification-filter-push';

    /**
     * Field constants.
     */
    const FIELD_NOTIFICATION_FILTERS = 'notification_filters';

    /**
     * Object type.
     */
    const OBJECT_TYPE_POST = 'NotificationFilterPush';
    const OBJECT_TYPE_GET = 'NotificationFilterPush';

    /**
     * The types of notifications that will result in a push notification for this user.
     *
     * @var NotificationFilterPushObject[]
     */
    protected $notificationFilters;

    /**
     * The types of notifications that will result in a push notification for this user.
     *
     * @var NotificationFilterPushObject[]|null
     */
    protected $notificationFiltersFieldForRequest;

    /**
     * @param NotificationFilterPushObject[]|null $notificationFilters The types of notifications that will result in a push
     * notification for this user.
     */
    public function __construct(array  $notificationFilters = null)
    {
        $this->notificationFiltersFieldForRequest = $notificationFilters;
    }

    /**
     * @param NotificationFilterPushObject[]|null $notificationFilters The types of notifications that will result in a push
     * notification for this user.
     * @param string[] $customHeaders
     *
     * @return BunqResponseNotificationFilterPush
     */
    public static function create(array  $notificationFilters = null, array $customHeaders = []): BunqResponseNotificationFilterPush
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->post(
            vsprintf(
                self::ENDPOINT_URL_CREATE,
                [static::determineUserId()]
            ),
            [self::FIELD_NOTIFICATION_FILTERS => $notificationFilters],
            $customHeaders
        );

        return BunqResponseNotificationFilterPush::castFromBunqResponse(
            static::fromJson($responseRaw, self::OBJECT_TYPE_POST)
        );
    }

    /**
     * This method is called "listing" because "list" is a restricted PHP word and cannot be used as constants, class names,
     * function or method names.
     *
     * @param string[] $params
     * @param string[] $customHeaders
     *
     * @return BunqResponseNotificationFilterPushApiObjectList
     */
    public static function listing( array $params = [], array $customHeaders = []): BunqResponseNotificationFilterPushApiObjectList
    {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->get(
            vsprintf(
                self::ENDPOINT_URL_LISTING,
                [static::determineUserId()]
            ),
            $params,
            $customHeaders
        );

        return BunqResponseNotificationFilterPushApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }

    /**
     * The types of notifications that will result in a push notification for this user.
     *
     * @return NotificationFilterPushObject[]
     */
    public function getNotificationFilters()
    {
        return $this->notificationFilters;
    }

    /**
     * @deprecated User should not be able to set values via setters, use constructor.
     *
     * @param NotificationFilterPushObject[] $notificationFilters
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
