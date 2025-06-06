<?php

declare(strict_types=1);

namespace App\Domains\Common\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum LogoutEnums: int
{
    use PrepareEnumDataMethods;

    case LOGOUT_FROM_ALL = 1;
    case LOGOUT_FROM_CURRENT = 0;
}
