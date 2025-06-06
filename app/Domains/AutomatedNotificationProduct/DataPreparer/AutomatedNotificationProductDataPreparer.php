<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotificationProduct\DataPreparer;

use App\Models\AutomatedNotificationProduct;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Support\Collection;

class AutomatedNotificationProductDataPreparer
{
    public static function prepareDataForAutomatedNotification(Collection $automatedNotificationProducts): array
    {
        if ($automatedNotificationProducts->isEmpty()) {
            return [];
        }

        return $automatedNotificationProducts->map(
            function (AutomatedNotificationProduct $automatedNotificationProduct): array {
                /** @var Product $product */
                $product = $automatedNotificationProduct->product;
                /** @var Location $location */
                $location = $automatedNotificationProduct->location;

                return [
                    'id' => $product->id,
                    'upc' => $product->upc,
                    'name' => $product->name,
                    'location_id' => $location->id,
                    'location_name' => $location->name,
                    'location_code' => $location->code,
                    'low_stock_alert_threshold' => $automatedNotificationProduct->low_stock_alert_threshold,
                ];
            }
        )->toArray();
    }
}
