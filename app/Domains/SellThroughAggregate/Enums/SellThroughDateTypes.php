<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SellThroughDateTypes: int
{
    use PrepareEnumDataMethods;

    case ACCUMULATED = 1;
    case CUSTOMIZED = 2;
}
