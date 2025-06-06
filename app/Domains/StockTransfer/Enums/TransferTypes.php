<?php

declare(strict_types=1);

namespace App\Domains\StockTransfer\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum TransferTypes: int
{
    use PrepareEnumDataMethods;

    case ALL = 1;
    case TRANSFER_IN = 2;
    case TRANSFER_OUT = 3;
    case REQUEST_ORDER = 4;
    case TRANSFER_ORDER = 5;
}
