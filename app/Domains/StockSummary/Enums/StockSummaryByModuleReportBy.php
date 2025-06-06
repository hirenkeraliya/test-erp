<?php

declare(strict_types=1);

namespace App\Domains\StockSummary\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StockSummaryByModuleReportBy: int
{
    use PrepareEnumDataMethods;

    case SALES = 1;
    case GRN_IN = 2;
    case GRN_OUT = 3;
    case TRANSFER_OUT = 4;
    case DELIVERY_OUT = 5;
    case TRANSFER_IN = 6;
    case DELIVERY_IN = 7;
    case STOCK_ADJUSTMENT_IN = 8;
    case STOCK_ADJUSTMENT_OUT = 9;
}
