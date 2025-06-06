<?php

declare(strict_types=1);

namespace App\Domains\VoidSale\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum VoidFilterTypes: int
{
    use PrepareEnumDataMethods;

    case BY_COUNTER = 1;
}
