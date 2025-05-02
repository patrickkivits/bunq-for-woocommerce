<?php
namespace bunq\Model\Core;

use bunq\Http\ApiClient;
use bunq\Model\Generated\Endpoint\BunqResponseNotificationFilterPushApiObjectList;
use bunq\Model\Generated\Endpoint\NotificationFilterPushApiObject;
use bunq\Model\Generated\Object\NotificationFilterPushObject;

/**
 * Class for push notification filter endpoints
 */
class NotificationFilterPushUserInternal extends NotificationFilterPushApiObject
{
    /**
     * Create notification filters
     *
     * @param NotificationFilterPushObject[] $allNotificationFilter
     * @param string[] $allHeaderCustom
     * @return BunqResponseNotificationFilterPushApiObjectList
     */
    public static function createWithListResponse(
        array $allNotificationFilter = [],
        array $allHeaderCustom = []
    ): BunqResponseNotificationFilterPushApiObjectList {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->post(
            vsprintf(
                self::ENDPOINT_URL_CREATE,
                [static::determineUserId()]
            ),
            [self::FIELD_NOTIFICATION_FILTERS => $allNotificationFilter],
            $allHeaderCustom
        );

        return BunqResponseNotificationFilterPushApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }
}
