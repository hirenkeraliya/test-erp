<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotificationProduct;

use App\Models\AutomatedNotificationProduct;
use Illuminate\Support\Collection;

class AutomatedNotificationProductQueries
{
    public function getBasicColumns(): string
    {
        return 'automated_notification_id,product_id,location_id,low_stock_alert_threshold';
    }

    public function getListWithProductAndInventoryByProductId(int $oldProductId): Collection
    {
        return AutomatedNotificationProduct::select('id', 'product_id', 'location_id')
            ->where('product_id', $oldProductId)
            ->get();
    }

    public function addNewOrUpdate(array $data): void
    {
        AutomatedNotificationProduct::updateOrCreate(
            [
                'product_id' => $data['product_id'],
                'location_id' => $data['location_id'],
                'automated_notification_id' => $data['automated_notification_id'],
            ],
            [
                'low_stock_alert_threshold' => $data['low_stock_alert_threshold'],
            ]
        );
    }
}
