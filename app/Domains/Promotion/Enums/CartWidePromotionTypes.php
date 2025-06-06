<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum CartWidePromotionTypes: int
{
    use PrepareEnumDataMethods;

    case AUTOMATIC = 3;
    case AS_PER_AMOUNT = 1;
    case AS_PER_PAYMENT_TYPE = 2;
}
