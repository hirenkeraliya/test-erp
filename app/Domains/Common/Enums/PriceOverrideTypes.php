<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PriceOverrideTypes: int
{
    use PrepareEnumDataMethods;

    case PERCENTAGE = 1;
    case FLAT = 2;
}
