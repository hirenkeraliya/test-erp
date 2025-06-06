<?php

declare(strict_types=1);

namespace App\Domains\Sale\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum DiscountTypeReports: int
{
    use PrepareEnumDataMethods;

    case DREAM_PRICE = 1;
    case COMPLIMENTARY = 2;
    case PROMOTION = 3;
    case PRICE_OVERRIDE = 4;
    case HAPPY_HOUR_DISCOUNT = 5;
}
