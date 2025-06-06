<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum OrdersReport: int
{
    use PrepareEnumDataMethods;

    case ORDERS_REPORT = 1;
    case CREDIT_SALES = 2;
    case LAYAWAY_SALES = 3;
}
