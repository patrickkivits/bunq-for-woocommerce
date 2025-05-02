<?php
namespace bunq\Model\Core;

use bunq\Model\Generated\Endpoint\BunqMeTabResultInquiryApiObject;
use bunq\Model\Generated\Endpoint\PaymentApiObject;
use JsonSerializable;

/**
 * Base class for all endpoints, responsible for parsing json received from the server.
 */
abstract class BunqModelWrapper implements JsonSerializable
{
    /**
     * Field constants.
     */
    const FIELD_PAYMENT = 'payment';

    /**
     * The double wrapping map.
     */
    const MODEL_WRAPPING_MAP = [
        BunqMeTabResultInquiryApiObject::class => [
            self::FIELD_PAYMENT => PaymentApiObject::OBJECT_TYPE_GET
        ]
    ];

    /**
     * @param $className
     * @param $propertyName
     *
     * @return string|null
     */
    public static function determineWrappingKey($className, $propertyName)
    {
        if (isset(self::MODEL_WRAPPING_MAP[$className])) {
            if (isset(self::MODEL_WRAPPING_MAP[$className][$propertyName])) {
                return self::MODEL_WRAPPING_MAP[$className][$propertyName];
            }
        }

        return null;
    }
}
