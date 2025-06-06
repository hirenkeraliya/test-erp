<?php

declare(strict_types=1);

namespace App\Domains\VoucherTransaction\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum VoucherTransactionActionTypes: int
{
    use PrepareEnumDataMethods;

    case RESET = 1;
    case CANCELLED = 2;
    case CREATED = 3;
    case USED = 4;
}
