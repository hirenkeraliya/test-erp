<?php

declare(strict_types=1);

namespace App\Domains\OnlineSalesCharges\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ShippingChargeTypes: int
{
    use PrepareEnumDataMethods;

    case WEIGHT = 1;
    case NUMBER_OF_ITEMS = 2;
    case NUMBER_OF_UNITS = 3;
    case TOTAL_CART_AMOUNT_BEFORE_DISCOUNT = 4;
    case TOTAL_CART_AMOUNT_AFTER_DISCOUNTS = 5;
}
