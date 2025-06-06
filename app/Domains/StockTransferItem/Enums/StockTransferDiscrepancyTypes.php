<?php

declare(strict_types=1);

namespace App\Domains\StockTransferItem\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StockTransferDiscrepancyTypes: int
{
    use PrepareEnumDataMethods;

    case POSITIVE = 1;
    case NEGATIVE = 2;
}
