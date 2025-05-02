<?php
namespace bunq\Model\Core;

use bunq\Http\ApiClient;
use bunq\Model\Generated\Endpoint\BunqResponseNotificationFilterUrlApiObjectList;
use bunq\Model\Generated\Endpoint\NotificationFilterUrlApiObject;
use bunq\Model\Generated\Object\NotificationFilterUrlObject;

/**
 * Class for user notification filter endpoints
 */
class NotificationFilterUrlUserInternal extends NotificationFilterUrlApiObject
{
    /**
     * @param NotificationFilterUrlObject[] $allNotificationFilter
     * @param string[] $allHeaderCustom
     *
     * @return BunqResponseNotificationFilterUrlApiObjectList
     */
    public static function createWithListResponse(
        array $allNotificationFilter = [],
        array $allHeaderCustom = []
    ): BunqResponseNotificationFilterUrlApiObjectList {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->post(
            vsprintf(
                self::ENDPOINT_URL_CREATE,
                [static::determineUserId()]
            ),
            [self::FIELD_NOTIFICATION_FILTERS => $allNotificationFilter],
            $allHeaderCustom
        );

        return BunqResponseNotificationFilterUrlApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }
}
