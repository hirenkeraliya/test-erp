<?php

declare(strict_types=1);

namespace App\Domains\Sale\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SalesCollectionReportTypes: int
{
    use PrepareEnumDataMethods;

    case BY_DATE = 1;
    case BY_RECEIPT = 2;
    case BY_CASHIER = 3;
    case BY_COUNTER = 4;
    case BY_TIME = 5;
    case BY_COUNTER_AND_CASHIER = 6;
    case BY_SUMMARY = 7;
    case BY_SUMMARY_DETAILS = 8;
    case BY_DATE_AND_BRAND = 9;
    case BY_CURRENT_DAY_VS_PREVIOUS_DAY = 10;
    case BY_SUMMARY_MONTH_AND_BRAND = 11;
}
