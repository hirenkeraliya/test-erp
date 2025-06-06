<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StockTransferStatusSummaryReportType: int
{
    use PrepareEnumDataMethods;

    case BY_SUMMARY = 1;
}
