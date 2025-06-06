<?php

declare(strict_types=1);

namespace App\Domains\Product\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ProductTypes: int
{
    use PrepareEnumDataMethods;

    case REGULAR_PRODUCT = 1;
    case SPECIAL_ORDER = 2;
    case CUSTOM_ORDER = 3;
    case POSTAGE_COST = 4;
    case ASSEMBLY_PRODUCT = 5;
    case SERIAL_PRODUCT = 6;
}
