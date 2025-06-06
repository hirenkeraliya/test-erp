<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum TransferReportType: int
{
    use PrepareEnumDataMethods;

    case BY_SUMMARY = 1;
    case BY_DOCUMENT = 2;
    case BY_DETAILS = 3;
    case BY_SUMMARY_UPC = 4;
}
