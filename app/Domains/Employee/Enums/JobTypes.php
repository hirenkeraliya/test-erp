<?php

declare(strict_types=1);

namespace App\Domains\Employee\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum JobTypes: int
{
    use PrepareEnumDataMethods;

    case FULL_TIME = 1;
    case PART_TIME = 2;
}
