<?php

declare(strict_types=1);

namespace App\Domains\HoldSale\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum HoldSaleTypes: int
{
    use PrepareEnumDataMethods;

    case REGULAR_SALE = 1;
    case LAYAWAY_SALE = 2;
    case BOOKING_PAYMENT = 3;
    case CREDIT_SALE = 4;
}
