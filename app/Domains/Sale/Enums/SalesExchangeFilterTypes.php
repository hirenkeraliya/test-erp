<?php

declare(strict_types=1);

namespace App\Domains\Sale\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SalesExchangeFilterTypes: int
{
    use PrepareEnumDataMethods;

    case BY_COUNTER = 1;
    case BY_CASHIER = 2;
}
