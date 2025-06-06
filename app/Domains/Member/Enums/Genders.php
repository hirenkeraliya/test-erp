<?php

declare(strict_types=1);

namespace App\Domains\Member\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Genders: int
{
    use PrepareEnumDataMethods;

    case MALE = 1;
    case FEMALE = 2;
}
