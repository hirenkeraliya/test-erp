<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotification\Enums;

use App\Http\Traits\PrepareEnumDataMethods;

enum AutomatedNotificationProductImportColumns: string
{
    use PrepareEnumDataMethods;

    case UPC = 'upc';
    case LOW_STOCK_ALERT_THRESHOLD = 'low_stock_alert_threshold';
}
