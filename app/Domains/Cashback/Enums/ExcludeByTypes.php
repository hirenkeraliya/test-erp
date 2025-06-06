<?php

declare(strict_types=1);

namespace App\Domains\Cashback\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ExcludeByTypes: int
{
    use PrepareEnumDataMethods;

    case NONE = 0;
    case PRODUCTS = 1;
    case CATEGORIES = 2;
    case ORIGINAL_ITEM_PRICE = 3;
    case DISCOUNT_ITEM_PRICE = 4;
}
