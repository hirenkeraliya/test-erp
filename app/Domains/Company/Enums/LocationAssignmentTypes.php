<?php

declare(strict_types=1);

namespace App\Domains\Company\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum LocationAssignmentTypes: int
{
    use PrepareEnumDataMethods;

    case MANUAL_ASSIGNMENT = 1;
    case DEFAULT_LOCATION = 2;
    case BASED_ON_FIRST_PURCHASE = 3;
}
