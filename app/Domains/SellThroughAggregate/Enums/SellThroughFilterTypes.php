<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SellThroughFilterTypes: int
{
    use PrepareEnumDataMethods;

    case ALL = 1;
    case ONLY_SOLD = 2;
    case ONLY_FREE_ITEMS_SOLD = 3;
}
