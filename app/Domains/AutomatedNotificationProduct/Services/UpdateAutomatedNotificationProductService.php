<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotificationProduct\Services;

use App\Domains\AutomatedNotification\AutomatedNotificationQueries;
use App\Domains\AutomatedNotificationProduct\AutomatedNotificationProductQueries;

class UpdateAutomatedNotificationProductService
{
    public function updateProduct(int $oldProductId, int $newProductId): void
    {
        $automatedNotificationQueries = resolve(AutomatedNotificationQueries::class);
        $automatedNotificationProductQueries = resolve(AutomatedNotificationProductQueries::class);

        $automatedNotificationProducts = $automatedNotificationProductQueries->getListWithProductAndInventoryByProductId(
            $oldProductId
        );

        foreach ($automatedNotificationProducts as $automatedNotificationProduct) {
            $automatedNotificationProduct->product_id = $newProductId;
            $automatedNotificationProduct->save();
        }

        $automatedNotificationQueries->updateProductIdsInAutomatedNotificationProductPivot(
            $oldProductId,
            $newProductId
        );
    }
}
