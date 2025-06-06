<?php

declare(strict_types=1);

namespace App\Domains\StockAdjustment\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StockAdjustmentTypes: int
{
    use PrepareEnumDataMethods;

    case STI = 1;
    case STO = 2;
}
