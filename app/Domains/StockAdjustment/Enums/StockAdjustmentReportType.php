<?php

declare(strict_types=1);

namespace App\Domains\StockAdjustment\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StockAdjustmentReportType: int
{
    use PrepareEnumDataMethods;

    case BY_SUMMARY = 1;
    case BY_DETAILS = 2;
}
