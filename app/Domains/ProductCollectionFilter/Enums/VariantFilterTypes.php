<?php

declare(strict_types=1);

namespace App\Domains\ProductCollectionFilter\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum VariantFilterTypes: int
{
    use PrepareEnumDataMethods;

    case NAME = 1;
    case CATEGORY = 2;
    case DEPARTMENT = 3;
    case BRAND = 4;
    case TAG = 5;
    case PRICE = 6;
    case TYPE = 7;
    case IS_AVAILABLE_IN_POS = 8;
    case IS_AVAILABLE_IN_ECOMMERCE = 9;
    case CREATED_BY = 10;
    case SALE_UNIT_SOLD = 11;
    case SALE_AMOUNT = 12;
    case ORDER_UNIT_SOLD = 13;
    case ORDER_AMOUNT = 14;
    case ATTRIBUTES = 15;
}
