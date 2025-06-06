<?php

declare(strict_types=1);

namespace App\Domains\MemberGroup\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum SmartGroupTypes: int
{
    use PrepareEnumDataMethods;

    case PURCHASE_DATE = 1;
    case FIRST_VISIT_DATE = 2;
    case LAST_VISIT_DATE = 3;
    case CATEGORY = 4;
    case ITEM = 5;
    case PURCHASE_COUNT = 6;
    case LIFETIME_SPENT = 7;
}
