<?php

declare(strict_types=1);

namespace App\Domains\ManualNotification\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum Statuses: int
{
    use PrepareEnumDataMethods;

    case PENDING = 1;
    case IN_PROGRESS = 2;
    case COMPLETED = 3;
}
