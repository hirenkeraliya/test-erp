<?php

declare(strict_types=1);

namespace App\Domains\User\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum UserTypes: int
{
    use PrepareEnumDataMethods;

    case MASTER = 1;
    case COMPANY_OWNER = 2;
}
