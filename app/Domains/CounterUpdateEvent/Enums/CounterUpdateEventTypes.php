<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdateEvent\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum CounterUpdateEventTypes: int
{
    use PrepareEnumDataMethods;

    case TAKE_A_BREAK = 1;
    case BACK_FROM_BREAK = 2;
    case DRAWER_OPEN = 3;
    case DRAWER_CLOSE = 4;
    case PRODUCT_ADDED_TO_CART = 5;
    case PRODUCT_REMOVED_FROM_CART = 6;
    case GOES_OFFLINE = 7;
    case BACK_ONLINE = 8;
}
