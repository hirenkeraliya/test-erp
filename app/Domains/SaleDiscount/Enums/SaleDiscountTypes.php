<?php

declare(strict_types=1);

namespace App\Domains\SaleDiscount\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SaleDiscountTypes: int
{
    use PrepareEnumDataMethods;

    case ITEM_WISE = 1;
    case CART_WISE = 2;
}
