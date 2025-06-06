<?php

declare(strict_types=1);

namespace App\Domains\PaymentType\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum StaticPaymentTypes: int
{
    use PrepareEnumDataMethods;

    case CASH = 1;
    case CREDIT_NOTE = 2;
    case BOOKING_PAYMENT = 3;
    case LOYALTY_POINT = 4;
    case GIFT_CARD = 5;
}
