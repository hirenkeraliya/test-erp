<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SalesReport: int
{
    use PrepareEnumDataMethods;

    case GENERAL_SALES = 1;
    case SALES_COLLECTION = 2;
    case RETURN_AND_EXCHANGE = 3;
    case SALES_RETURN = 4;
    case SALES_EXCHANGE = 5;
    case VOID_REPORT = 6;
    case CASH_MOVEMENT = 7;
    case HOLD_AND_RESUME = 8;
    case SALES_BY_PROMOTER = 9;
    case SUMMARY_OF_SALES_BY_STORES = 10;
    case HOURLY_SALES_REPORT = 11;
}
