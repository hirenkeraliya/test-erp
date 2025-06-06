<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PromotionUsageTypes: int
{
    use PrepareEnumDataMethods;

    case SINGLE_USE = 1;
    case MULTIPLE_USE = 2;
}
