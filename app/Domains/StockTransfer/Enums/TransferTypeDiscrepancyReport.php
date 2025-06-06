<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum TransferTypeDiscrepancyReport: int
{
    use PrepareEnumDataMethods;
    use PrepareEnumDataMethods;

    case BY_DOCUMENT = 1;
    case BY_DETAILS = 2;
    case BY_SUMMARY = 3;
}
