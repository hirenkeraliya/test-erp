<?php

declare(strict_types=1);

namespace App\Domains\ManualNotification\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum MembersFilter: int
{
    use PrepareEnumDataMethods;

    case STORES = 1;
    case GROUPS = 2;
    case TYPES = 3;
    case MEMBERS = 4;
}
