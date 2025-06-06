<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum TimeIntervalType: int
{
    use PrepareEnumDataMethods;

    case DAILY = 1;
    case WEEKLY = 2;
    case MONTHLY = 3;
    case YEARLY = 4;
    case CUSTOM_PERIOD = 5;
}
