<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotification\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum AutomatedNotificationTimeframeTypes: int
{
    use PrepareEnumDataMethods;

    case LIMIT_BY_DAY_OF_THE_WEEK = 1;
    case LIMIT_BY_DAY_OF_THE_MONTH = 2;
}
