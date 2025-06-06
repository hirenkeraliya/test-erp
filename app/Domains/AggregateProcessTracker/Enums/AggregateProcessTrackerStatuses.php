<?php

declare(strict_types=1);

namespace App\Domains\AggregateProcessTracker\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum AggregateProcessTrackerStatuses: int
{
    use PrepareEnumDataMethods;

    case IN_PROGRESS = 1;
    case COMPLETED = 2;
    case FAILED = 3;
}
