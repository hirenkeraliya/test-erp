<?php

declare(strict_types=1);

namespace App\Domains\Courier\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum CourierTypes: int
{
    use PrepareEnumDataMethods;

    case NINJA_VAN = 1;
}
