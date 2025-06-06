<?php

declare(strict_types=1);

namespace App\Domains\Member\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum FilterStatus: int
{
    use PrepareEnumDataMethods;

    case ACTIVE = Status::ACTIVE->value;
    case INACTIVE = Status::INACTIVE->value;
    case ALL = 3;
}
