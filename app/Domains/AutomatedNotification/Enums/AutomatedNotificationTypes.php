<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotification\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum AutomatedNotificationTypes: int
{
    use PrepareEnumDataMethods;

    case LOW_STOCK_COMPANY = 1;
    case NO_STOCK = 2;
    case REQUEST_STOCK = 3;
    case DEADLINE_REQUEST_STOCK = 4;
    case LOW_STOCK_LOCATION = 5;
    case LOW_STOCK_PRODUCT = 6;
}
