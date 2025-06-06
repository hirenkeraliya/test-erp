<?php

declare(strict_types=1);

namespace App\Domains\BookingPayment\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum BookingPaymentStatuses: int
{
    use PrepareEnumDataMethods;

    case ACTIVE = 1;
    case USED = 2;
    case REFUNDED = 3;
}
