<?php

declare(strict_types=1);

namespace App\Domains\SerialNumber\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SerialNumberStatus: int
{
    use PrepareEnumDataMethods;

    case ACTIVE = 1;
    case RESERVED = 2;
    case SOLD = 3;
    case VIRTUAL = 4;
    case TRANSFER_OUT = 5;
    case DELETED = 6;
}
