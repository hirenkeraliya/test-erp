<?php

declare(strict_types=1);

namespace App\Domains\Product\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SellingTypes: int
{
    use PrepareEnumDataMethods;

    case ALL = 1;
    case SELLING = 2;
    case NON_SELLING = 3;
}
