<?php
namespace bunq\Model\Core;

use bunq\Http\ApiClient;
use bunq\Model\Generated\Endpoint\BunqResponseNotificationFilterUrlMonetaryAccountApiObjectList;
use bunq\Model\Generated\Endpoint\NotificationFilterUrlMonetaryAccountApiObject;
use bunq\Model\Generated\Object\NotificationFilterUrlObject;

/**
 * Class for monetary account notification filter endpoints
 */
class NotificationFilterUrlMonetaryAccountInternal extends NotificationFilterUrlMonetaryAccountApiObject
{
    /**
     * Create notification filters
     *
     * @param int|null $monetaryAccountId
     * @param NotificationFilterUrlObject[] $allNotificationFilter
     * @param string[] $allHeaderCustom
     *
     * @return BunqResponseNotificationFilterUrlMonetaryAccountApiObjectList
     */
    public static function createWithListResponse(
        int $monetaryAccountId = null,
        array $allNotificationFilter = [],
        array $allHeaderCustom = []
    ): BunqResponseNotificationFilterUrlMonetaryAccountApiObjectList {
        $apiClient = new ApiClient(static::getApiContext());
        $responseRaw = $apiClient->post(
            vsprintf(
                self::ENDPOINT_URL_CREATE,
                [static::determineUserId(), static::determineMonetaryAccountId($monetaryAccountId)]
            ),
            [self::FIELD_NOTIFICATION_FILTERS => $allNotificationFilter],
            $allHeaderCustom
        );

        return BunqResponseNotificationFilterUrlMonetaryAccountApiObjectList::castFromBunqResponse(
            static::fromJsonList($responseRaw, self::OBJECT_TYPE_GET)
        );
    }
}
