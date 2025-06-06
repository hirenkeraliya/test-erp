<?php

declare(strict_types=1);

namespace App\Domains\HappyHourDiscount\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ProductTypes: int
{
    use PrepareEnumDataMethods;
    case ALL = 1;
    case BRAND = 2;
    case CATEGORY = 3;
    case STYLE = 4;
    case DEPARTMENTS = 5;
}
