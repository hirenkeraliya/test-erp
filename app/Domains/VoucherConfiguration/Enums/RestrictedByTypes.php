<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfiguration\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum RestrictedByTypes: int
{
    use PrepareEnumDataMethods;

    case ALL = 1;
    case MEMBER_ONLY = 2;
    case NON_MEMBER_ONLY = 3;
}
