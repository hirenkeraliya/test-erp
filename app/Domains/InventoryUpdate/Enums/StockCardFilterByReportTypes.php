<?php

declare(strict_types=1);

namespace App\Domains\InventoryUpdate\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StockCardFilterByReportTypes: int
{
    use PrepareEnumDataMethods;

    case BY_PRODUCT = 1;
    case BY_MASTER_PRODUCT = 2;
    case BY_PRODUCT_COLLECTION = 3;
    case BY_BRAND = 4;
    case BY_DEPARTMENT = 5;
    case BY_CATEGORY = 6;
}
