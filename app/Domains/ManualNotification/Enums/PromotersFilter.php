<?php

declare(strict_types=1);

namespace App\Domains\ManualNotification\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum PromotersFilter: int
{
    use PrepareEnumDataMethods;

    case LOCATIONS = 1;
    case GROUPS = 2;
    case PROMOTERS = 3;
}
