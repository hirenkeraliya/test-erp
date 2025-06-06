<?php

declare(strict_types=1);

namespace App\Domains\Sale\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PromoterCommissionFilterTypes: int
{
    use PrepareEnumDataMethods;

    case BY_DEPARTMENT = 1;
    case BY_BRAND = 2;
    case BY_PROMOTER_GROUP = 3;
}
