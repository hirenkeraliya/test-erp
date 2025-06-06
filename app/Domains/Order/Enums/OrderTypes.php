<?php

declare(strict_types=1);

namespace App\Domains\Order\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum OrderTypes: int
{
    use PrepareEnumDataMethods;

    case REGULAR_ORDER = 1;
    case CANCEL_ORDER = 2;
    case PENDING_LAYAWAY_ORDER = 3;
    case COMPLETE_LAYAWAY_ORDER = 4;
    case PENDING_CREDIT_ORDER = 5;
    case COMPLETE_CREDIT_ORDER = 6;
}
