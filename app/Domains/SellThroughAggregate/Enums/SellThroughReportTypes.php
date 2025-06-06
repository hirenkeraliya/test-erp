<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SellThroughReportTypes: int
{
    use PrepareEnumDataMethods;

    case BY_PRODUCT = 1;
    case BY_LOCATION = 2;
}
