<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum AvailabilityType: int
{
    use PrepareEnumDataMethods;

    case ALL = 1;
    case AVAILABLE_IN_POS = 2;
    case AVAILABLE_IN_ECOMMERCE = 3;
}
