<?php

namespace Infrastructure\Enumerations;

/**
 * Class OrderItemStatusEnums
 *
 * @package Infrastructure\Enumerations
 */
final class OrderItemStatusEnums
{
    const PARENT_ID = 1294;

    const WAITING_FOR_GROUP_BUY = 1295;
    const VERIFIED = 1296;
    const PREPARATION = 1297;
    const POSTED = 1298;
    const CANCELED_BEFORE_POSTING = 1299;
    const RETURN_REQUEST = 1300;
    const RETURN_ACCEPTED = 1301;
    const RETURN_REJECTED = 1302;
    const RETURNED = 1303;

    const ALL = [
        self::WAITING_FOR_GROUP_BUY,
        self::VERIFIED,
        self::PREPARATION,
        self::POSTED,
        self::CANCELED_BEFORE_POSTING,
        self::RETURN_REQUEST,
        self::RETURN_ACCEPTED,
        self::RETURN_REJECTED,
        self::RETURNED,
    ];
}
