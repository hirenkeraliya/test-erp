<?php

declare(strict_types=1);

namespace App\Domains\PosMismatch\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Causer: int
{
    use PrepareEnumDataMethods;

    case POS = 1;
    case BACKEND = 2;
    case CONFIGURATION_UPDATES = 3;
}
