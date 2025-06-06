<?php

declare(strict_types=1);

namespace App\Domains\Member\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PurchaseFilterTypes: int
{
    use PrepareEnumDataMethods;

    case UNITS_PURCHASED = 1;
    case PURCHASES = 2;
    case LIFT_TIME_VALUE = 3;
}
