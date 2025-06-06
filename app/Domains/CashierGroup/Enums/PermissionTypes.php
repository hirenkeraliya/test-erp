<?php

declare(strict_types=1);

namespace App\Domains\CashierGroup\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PermissionTypes: int
{
    use PrepareEnumDataMethods;

    case SALE = 1;
    case VOID = 2;
    case RETURN = 3;
    case LAYAWAY = 4;
    case CREDIT_NOTE = 5;
    case REFUND_CREDIT_NOTE = 6;
    case PRICE_OVERRIDE = 7;
    case COMPLIMENTARY_ITEM = 8;
    case EMPLOYEE_DISCOUNT = 9;
    case BOOKING_PAYMENT = 10;
    case DAY_CLOSE = 11;
    case CREDIT_SALE = 12;
}
