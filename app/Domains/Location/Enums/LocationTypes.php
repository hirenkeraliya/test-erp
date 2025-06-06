<?php

declare(strict_types=1);

namespace App\Domains\Location\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum LocationTypes: int
{
    use PrepareEnumDataMethods;

    case STORE = 1;
    case WAREHOUSE = 2;
}
