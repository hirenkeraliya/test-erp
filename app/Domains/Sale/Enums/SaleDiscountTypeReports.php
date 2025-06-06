<?php

declare(strict_types=1);

namespace App\Domains\Sale\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SaleDiscountTypeReports: int
{
    use PrepareEnumDataMethods;

    case VOUCHER = 1;
    case CASHBACK = 2;
    case PROMOTION = 3;
    case SALE_PRICE_OVERRIDE = 4;
    case SALE_LOYALTY_POINT = 5;
}
