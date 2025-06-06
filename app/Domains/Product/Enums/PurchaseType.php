<?php

declare(strict_types=1);

namespace App\Domains\Product\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PurchaseType: int
{
    use PrepareEnumDataMethods;

    case ALL = 1;
    case FOC = 2;
    case PAID = 3;
}
