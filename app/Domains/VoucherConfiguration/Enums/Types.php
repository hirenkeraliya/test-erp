<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfiguration\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Types: int
{
    use PrepareEnumDataMethods;

    case ALL = 0;
    case MANUAL = 1;
    case SYSTEM_GENERATED = 2;
}
