<?php

declare(strict_types=1);

namespace App\Domains\CashMovement\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum CashMovementFilterTypes: int
{
    use PrepareEnumDataMethods;

    case BY_COUNTER = 1;
    case BY_CASHIER = 2;
}
