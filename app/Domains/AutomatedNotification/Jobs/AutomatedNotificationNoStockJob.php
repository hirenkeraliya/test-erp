<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotification\Jobs;

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Notification\NotificationQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutomatedNotificationNoStockJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        protected int $automatedNotificationId,
        protected int $companyId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $locationQueries = resolve(LocationQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $storeManagerQueries = resolve(StoreManagerQueries::class);
        $notificationQueries = resolve(NotificationQueries::class);
        $locationQueries = resolve(LocationQueries::class);
        $warehouseManagerQueries = resolve(WarehouseManagerQueries::class);

        $allLocation = $locationQueries->getStoreWithBasicColumns($this->companyId);
        $allWarehouse = $locationQueries->getWithBasicColumnsOfWarehouse($this->companyId);
        $prepareStoreWiseNoStockData = [];
        $prepareWarehouseWiseNoStockData = [];

        try {
            foreach ($allLocation as $location) {
                $productCount = $inventoryQueries->getProductCountOutOfStock($location->id, $location->company_id);

                if ($productCount > 0) {
                    $prepareStoreWiseNoStockData[] = [
                        'name' => $location->name,
                        'product_count' => $productCount,
                    ];
                    $storeManagers = $storeManagerQueries->getAllStoreManagerWithStore($location->id);

                    foreach ($storeManagers as $storeManager) {
                        $message = $productCount . ' products are out of stock at ' . $location->name . ' location.';
                        $textMessage = $productCount . ' products are out of stock at ' . $location->name . ' location.';
                        $notificationQueries->addNew(
                            $this->companyId,
                            null,
                            null,
                            ModelMapping::STORE_MANAGER->name,
                            $storeManager->id,
                            $message,
                            null,
                            $textMessage,
                            null,
                        );
                    }
                }
            }

            foreach ($allWarehouse as $location) {
                $productCount = $inventoryQueries->getProductCountOutOfStock($location->id, $location->company_id);

                if ($productCount > 0) {
                    $prepareWarehouseWiseNoStockData[] = [
                        'name' => $location->name,
                        'product_count' => $productCount,
                    ];
                    $warehouseManagers = $warehouseManagerQueries->getAllWarehouseManagerWithWarehouse($location->id);

                    foreach ($warehouseManagers as $warehouseManager) {
                        $message = $productCount . ' products are out of stock at ' . $location->name . ' warehouse.';
                        $notificationQueries->addNew(
                            $this->companyId,
                            null,
                            null,
                            ModelMapping::WAREHOUSE_MANAGER->name,
                            $warehouseManager->id,
                            $message
                        );
                    }
                }
            }
        } catch (Throwable $throwable) {
            Log::error('Automated Notification Deadline No Stock Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }
    }
}
