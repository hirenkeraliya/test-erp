<?php

declare(strict_types=1);

namespace App\Domains\SaleTarget\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SaleTargetAmountTypes: int
{
    use PrepareEnumDataMethods;

    case AMOUNT = 1;
    case PERCENTAGE = 2;
}
