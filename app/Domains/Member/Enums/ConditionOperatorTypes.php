<?php

declare(strict_types=1);

namespace App\Domains\Member\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ConditionOperatorTypes: int
{
    use PrepareEnumDataMethods;

    case LESS_THAN = 1;
    case GREATER_THAN = 2;
    case EQUAL = 3;
}
