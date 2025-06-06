<?php

declare(strict_types=1);

namespace App\Domains\Sale\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum CreditAndLayawaySaleStatuses: int
{
    use PrepareEnumDataMethods;

    case PENDING = 1;
    case COMPLETE = 2;
}
