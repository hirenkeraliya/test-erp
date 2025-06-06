<?php

declare(strict_types=1);

namespace App\Domains\Voucher\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum VoucherStatusTypes: int
{
    use PrepareEnumDataMethods;

    case ACTIVE = 1;
    case USED = 2;
    case EXPIRED = 3;
    case CANCELLED = 4;
}
