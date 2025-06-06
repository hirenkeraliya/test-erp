<?php

declare(strict_types=1);

namespace App\Domains\StockSummary\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StockSummaryByModuleReportType: int
{
    use PrepareEnumDataMethods;
    case BY_MASTER_PRODUCT = 1;
    case BY_UPC = 2;
    case BY_BRAND = 3;
    case BY_DEPARTMENT = 4;
}
