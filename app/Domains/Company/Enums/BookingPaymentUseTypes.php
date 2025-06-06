<?php

declare(strict_types=1);

namespace App\Domains\Company\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum BookingPaymentUseTypes: int
{
    use PrepareEnumDataMethods;

    case PARTIALLY = 1;
    case FULLY = 2;
}
