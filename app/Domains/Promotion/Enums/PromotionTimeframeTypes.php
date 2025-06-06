<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PromotionTimeframeTypes: int
{
    use PrepareEnumDataMethods;

    case NO_LIMIT = 1;
    case LIMITED_BY_DATES = 2;
    case LIMIT_BY_DAY_OF_THE_WEEK = 3;
    case LIMIT_BY_DAY_OF_THE_MONTH = 4;
    case LIMIT_BY_HOUR_OF_THE_DAY = 5;
}
