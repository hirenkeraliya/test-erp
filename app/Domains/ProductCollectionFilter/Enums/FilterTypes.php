<?php

declare(strict_types=1);

namespace App\Domains\ProductCollectionFilter\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum FilterTypes: int
{
    use PrepareEnumDataMethods;

    case NAME = 1;
    case CATEGORY = 2;
    case SEASON = 3;
    case DEPARTMENT = 4;
    case COLOR = 5;
    case SIZE = 6;
    case BRAND = 7;
    case STYLE = 8;
    case TAG = 9;
    case PRICE = 10;
    case TYPE = 11;
    case IS_AVAILABLE_IN_POS = 12;
    case IS_AVAILABLE_IN_ECOMMERCE = 13;
    case CREATED_BY = 14;
    case SALE_UNIT_SOLD = 15;
    case SALE_AMOUNT = 16;
    case ORDER_UNIT_SOLD = 17;
    case ORDER_AMOUNT = 18;
}
