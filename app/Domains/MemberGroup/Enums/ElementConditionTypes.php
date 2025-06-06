<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum ElementConditionTypes: int
{
    use PrepareEnumDataMethods;

    case WAS = 1;
    case WAS_NOT = 2;
}
