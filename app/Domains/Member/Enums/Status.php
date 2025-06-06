<?php

declare(strict_types=1);

namespace App\Domains\Member\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Status: int
{
    use PrepareEnumDataMethods;

    case ACTIVE = 1;
    case DELETED_BY_USER = 2;
    case DELETED_BY_ADMIN = 3;
    case INACTIVE = 4;
}
