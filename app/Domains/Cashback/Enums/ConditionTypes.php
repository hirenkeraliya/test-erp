<?php

declare(strict_types=1);

namespace App\Domains\Cashback\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ConditionTypes: int
{
    use PrepareEnumDataMethods;

    case LESS_THAN = 1;
    case GREATER_THAN = 2;
    case EQUAL = 3;
}
