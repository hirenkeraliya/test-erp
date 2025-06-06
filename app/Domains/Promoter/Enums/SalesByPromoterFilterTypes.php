<?php

declare(strict_types=1);

namespace App\Domains\Promoter\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SalesByPromoterFilterTypes: int
{
    use PrepareEnumDataMethods;

    case BY_BRANDS = 1;
    case BY_DEPARTMENTS = 2;
    case BY_CATEGORIES = 3;
    case BY_PROMOTER_GROUP = 4;
}
