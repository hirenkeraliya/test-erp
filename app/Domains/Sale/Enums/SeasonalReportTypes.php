<?php

declare(strict_types=1);

namespace App\Domains\Sale\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SeasonalReportTypes: int
{
    use PrepareEnumDataMethods;

    case BY_SUMMARY = 1;
    case BY_SEASON = 2;
    case BY_COMPARISON = 3;
}
