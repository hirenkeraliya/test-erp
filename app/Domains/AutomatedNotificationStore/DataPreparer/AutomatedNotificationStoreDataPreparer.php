<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotificationStore\DataPreparer;

use App\Models\AutomatedNotificationStore;
use App\Models\Location;
use Illuminate\Support\Collection;

class AutomatedNotificationStoreDataPreparer
{
    public static function prepareDataForAutomatedNotification(Collection $automatedNotificationStores): array
    {
        if ($automatedNotificationStores->isEmpty()) {
            return [];
        }

        return $automatedNotificationStores->map(
            function (AutomatedNotificationStore $automatedNotificationStore): array {
                /** @var Location $location */
                $location = $automatedNotificationStore->location;

                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'code' => $location->code,
                    'low_stock_alert_threshold' => $automatedNotificationStore->low_stock_alert_threshold,
                ];
            }
        )->toArray();
    }
}
