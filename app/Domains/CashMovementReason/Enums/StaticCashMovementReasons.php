<?php

declare(strict_types=1);

namespace App\Domains\CashMovementReason\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StaticCashMovementReasons: int
{
    use PrepareEnumDataMethods;

    case CASHBACK = 1;
    case CASHBACK_REVERSAL = 2;
}
