<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Types: int
{
    use PrepareEnumDataMethods;

    case NO_STOCK = 1;
    case LOW_STOCK_COMPANY = 2;
    case NEGATIVE_STOCK = 3;
    case LOW_STOCK_LOCATION = 4;
    case LOW_STOCK_PRODUCT = 5;
}
