<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum NumberConditionTypes: int
{
    use PrepareEnumDataMethods;

    case GREATER_THAN = 1;
    case LESS_THAN = 2;
    case BETWEEN = 3;
    case EXACTLY_TO = 4;
}
