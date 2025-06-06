<?php

declare(strict_types=1);

namespace App\Domains\EmployeeGroup\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PurchaseLimitTypes: int
{
    use PrepareEnumDataMethods;

    case BY_ITEMS = 1;
    case BY_SALE = 2;
    case BY_AMOUNT = 3;
}
