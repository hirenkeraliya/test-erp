<?php

declare(strict_types=1);

namespace App\Domains\Sale\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SalesOverallReportTypes: int
{
    use PrepareEnumDataMethods;

    case BY_TOTAL_RECEIPT = 1;
    case BY_NET_TOTAL = 2;
}
