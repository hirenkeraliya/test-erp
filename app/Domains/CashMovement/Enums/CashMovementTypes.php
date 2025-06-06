<?php

declare(strict_types=1);

namespace App\Domains\CashMovement\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum CashMovementTypes: int
{
    use PrepareEnumDataMethods;

    case CASH_IN = 1;
    case CASH_OUT = 2;
}
