<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotificationStore;

class AutomatedNotificationStoreQueries
{
    public function getBasicColumns(): string
    {
        return 'automated_notification_id,location_id,low_stock_alert_threshold';
    }
}
