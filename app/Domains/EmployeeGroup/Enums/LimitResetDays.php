<?php

declare(strict_types=1);

namespace App\Domains\EmployeeGroup\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum LimitResetDays: int
{
    use PrepareEnumDataMethods;

    case MONDAY = 1;
    case TUESDAY = 2;
    case WEDNESDAY = 3;
    case THURSDAY = 4;
    case FRIDAY = 5;
    case SATURDAY = 6;
    case SUNDAY = 7;
}
