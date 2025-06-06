<?php

declare(strict_types=1);

namespace App\Domains\AggregateProcessTracker\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum AggregateProcessTrackerModules: int
{
    use PrepareEnumDataMethods;

    case SELL_THROUGH = 1;
    case PRODUCT_AGEING = 2;
}
