<?php

declare(strict_types=1);

namespace App\Domains\Member\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Types: int
{
    use PrepareEnumDataMethods;

    case VIP = 1;
    case REGULAR = 2;
    case CORPORATE = 3;
    case ONLINE_STORE = 4;
}
