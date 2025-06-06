<?php

declare(strict_types=1);

namespace App\Domains\StockAdjustment\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StockAdjustmentFilterType: int
{
    use PrepareEnumDataMethods;

    case BY_PRODUCT = 1;
    case BY_MASTER_PRODUCT = 2;
    case BY_PRODUCT_COLLECTION = 3;
}
