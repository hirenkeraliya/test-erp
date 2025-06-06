<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum DateConditionTypes: int
{
    use PrepareEnumDataMethods;

    case MORE_THAN = 1;
    case LESS_THAN = 2;
    case EXACTLY_ON = 3;
    case BETWEEN = 4;
}
