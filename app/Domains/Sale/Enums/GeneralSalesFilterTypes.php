<?php

declare(strict_types=1);

namespace App\Domains\Sale\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum GeneralSalesFilterTypes: int
{
    use PrepareEnumDataMethods;

    case BY_PROMOTER = 1;
    case BY_BRAND = 2;
    case BY_DEPARTMENT = 3;
    case BY_COUNTER = 4;
}
