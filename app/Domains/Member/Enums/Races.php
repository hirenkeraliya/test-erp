<?php

declare(strict_types=1);

namespace App\Domains\Member\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Races: int
{
    use PrepareEnumDataMethods;

    case MALAY = 1;
    case CHINESE = 2;
    case INDIAN = 3;
    case OTHERS = 8;
}
