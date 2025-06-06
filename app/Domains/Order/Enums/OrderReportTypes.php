<?php

declare(strict_types=1);

namespace App\Domains\Order\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum OrderReportTypes: int
{
    use PrepareEnumDataMethods;

    case BY_DOCUMENT = 1;
    case BY_DETAILS = 2;
    case BY_SUMMARY = 3;
}
