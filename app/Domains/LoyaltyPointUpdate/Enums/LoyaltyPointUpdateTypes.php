<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyPointUpdate\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum LoyaltyPointUpdateTypes: int
{
    use PrepareEnumDataMethods;

    case SALE = 1;
    case SALE_RETURN = 2;
    case USED = 3;
    case EXPIRED = 4;
    case SIGNUP_BONUS = 5;
    case VOID_SALE = 6;
    case MANUAL_UPDATE = 7;
    case CANCEL_LAYAWAY_SALE = 8;
    case VOUCHER = 9;
    case REVERT = 10;
    case CANCEL_CREDIT_SALE = 11;
    case ORDER = 12;
}
