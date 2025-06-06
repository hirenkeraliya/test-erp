<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfiguration\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum VoucherTypes: int
{
    use PrepareEnumDataMethods;

    case BIRTHDAY_VOUCHER = 1;
    case TIER_VOUCHER = 2;
    case MULTIPLE_VOUCHER = 3;
    case WELCOME_MEMBER = 4;
    case LOYALTY_POINT = 5;
}
