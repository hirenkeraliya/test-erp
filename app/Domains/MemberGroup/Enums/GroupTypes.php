<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum GroupTypes: int
{
    use PrepareEnumDataMethods;

    case MANUAL_GROUP = 1;
    case SMART_GROUP = 2;
}
