<?php

declare(strict_types=1);

namespace App\Domains\Member\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Preferences: int
{
    use PrepareEnumDataMethods;

    case PREFERRED_COLOR = 1;
    case PREFERRED_SIZE = 2;
    case PREFERRED_CATEGORY = 3;
    case PREFERRED_DATE = 4;
    case PREFERRED_DAY = 5;
}
