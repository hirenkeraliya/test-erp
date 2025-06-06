<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum BarcodePrintModuleTypes: int
{
    use PrepareEnumDataMethods;

    case GOODS_RECEIVED_NOTES = 1;
    case TRANSFER_ORDER = 2;
    case REQUEST_ORDER = 3;
    case TRANSFER_IN = 4;
    case TRANSFER_OUT = 5;
}
