<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StockTransferCustomReportDateTypes: int
{
    use PrepareEnumDataMethods;

    case TRANSFER_DATE = 1;
    case REQUIRE_DATE = 2;
    case MANUAL_RECEIVED_DATE = 3;
    case OPENED_AT = 4;
    case APPROVED_AT = 5;
    case SHIPPED_AT = 6;
    case SYSTEM_RECEIVED_AT = 7;
    case DISCREPANCY_AT = 8;
    case CLOSED_AT = 9;
    case CANCELLED_AT = 10;
    case REJECTED_AT = 11;
    case CREATED_AT = 12;
}
