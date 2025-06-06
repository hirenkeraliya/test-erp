<?php

declare(strict_types=1);

namespace App\Domains\SyncTransaction\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SyncTypes: int
{
    use PrepareEnumDataMethods;

    case PRODUCT = 1;
    case CATEGORY = 2;
    case COLOR = 3;
    case SIZE = 4;
    case MEMBER = 5;
    case MEMBER_GROUP = 6;
    case BANNER = 7;
    case BRAND = 8;
    case INVENTORY = 9;
    case DREAM_PRICE = 10;
}
