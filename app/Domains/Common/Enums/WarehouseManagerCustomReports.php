<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum WarehouseManagerCustomReports: int
{
    use PrepareEnumDataMethods;

    case STOCK_MOVEMENTS = 1;
    case STOCK_CARD = 2;
    case GOODS_RECEIVED_NOTES = 4;
    case STOCK_TRANSFER = 5;
    case STOCK_ADJUSTMENT = 6;
    case STOCK_TRANSFER_STATUSES = 7;
    case STOCK_TRANSFER_DISCREPANCY = 8;
    case INTER_COMPANY_TRANSFER = 9;
    case INTER_COMPANY_INVOICE = 10;
}
