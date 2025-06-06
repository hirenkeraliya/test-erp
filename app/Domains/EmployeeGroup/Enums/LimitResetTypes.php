<?php

declare(strict_types=1);

namespace App\Domains\EmployeeGroup\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum LimitResetTypes: int
{
    use PrepareEnumDataMethods;

    case BY_MONTH = 1;
    case BY_WEEK = 2;
    case BY_DAYS = 3;
}
