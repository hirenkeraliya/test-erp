<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Services;

use App\Domains\ExportRecord\Enums\ExportRecordTypes;
use App\Domains\ExportRecord\ExportRecordQueries;
use App\Domains\ExportRecord\Jobs\ExportToExcelJob;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\OrderItemUnit\OrderItemUnitQueries;
use App\Domains\PurchaseOrderFulfillmentItemUnit\PurchaseOrderFulfillmentItemUnitQueries;
use App\Domains\ReservedStock\ReservedStockQueries;
use App\Domains\SaleItemUnit\SaleItemUnitQueries;
use App\Domains\StockTransferItemUnit\StockTransferItemUnitQueries;
use App\Domains\TransitStock\TransitStockQueries;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function getInventoryUnits(
        bool $hasBatch,
        int $inventoryId,
        ?int $batchId,
        ?int $serialNumberId = null
    ): Collection {
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);

        if (! $hasBatch) {
            return $inventoryUnitQueries->getByInventoryId($inventoryId, $serialNumberId);
        }

        if ($batchId) {
            return $inventoryUnitQueries->getByInventoryBatchId($inventoryId, $batchId, $serialNumberId);
        }

        return $inventoryUnitQueries->getByInventoryIdOrderByBatchExpiryDate($inventoryId, $serialNumberId);
    }

    public function updateInventoryUnit(
        float $quantity,
        int $inventoryId,
        int $purchaseAmountId,
        ?int $batchId,
        ?int $serialNumberId = null
    ): void {
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);

        $inventoryUnit = $inventoryUnitQueries->getByInventoryIdBatchIdAndPurchaseAmountId(
            $inventoryId,
            $purchaseAmountId,
            $batchId,
            $serialNumberId
        );

        if ($inventoryUnit instanceof InventoryUnit) {
            $inventoryUnitQueries->increaseStock($inventoryUnit, $quantity);

            return;
        }

        $inventoryUnitQueries->addNew((string) $quantity, $inventoryId, $purchaseAmountId, $batchId, $serialNumberId);
    }

    public function updateInventoryUnitReservedStock(
        float $quantity,
        int $inventoryId,
        int $purchaseAmountId,
        ?int $batchId,
        ?int $serialNumberId = null
    ): void {
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);

        $inventoryUnit = $inventoryUnitQueries->getByInventoryIdBatchIdAndPurchaseAmountId(
            $inventoryId,
            $purchaseAmountId,
            $batchId,
            $serialNumberId
        );

        if ($inventoryUnit instanceof InventoryUnit) {
            $inventoryUnitQueries->increaseOnlyReservedStock($inventoryUnit, $quantity);

            return;
        }

        $inventoryUnitQueries->addNew((string) $quantity, $inventoryId, $purchaseAmountId, $batchId, $serialNumberId);
    }

    public function mergeInventory(int $oldProductId, int $newProductId): void
    {
        DB::transaction(function () use ($oldProductId, $newProductId): void {
            $inventoryQueries = resolve(InventoryQueries::class);
            $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
            $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
            $reservedStockQueries = resolve(ReservedStockQueries::class);
            $transitStockQueries = resolve(TransitStockQueries::class);
            $saleItemUnitQueries = resolve(SaleItemUnitQueries::class);
            $orderItemUnitQueries = resolve(OrderItemUnitQueries::class);
            $stockTransferItemUnitQueries = resolve(StockTransferItemUnitQueries::class);
            $purchaseOrderFulfillmentItemUnitQueries = resolve(PurchaseOrderFulfillmentItemUnitQueries::class);

            $inventoryOfOldProducts = $inventoryQueries->getByProductId($oldProductId);
            $inventoryOfNewProducts = $inventoryQueries->getByProductId($newProductId);

            foreach ($inventoryOfOldProducts as $inventoryOfOldProduct) {
                $inventoryOfNewProduct = $inventoryOfNewProducts->where(
                    'location_id',
                    $inventoryOfOldProduct->location_id
                )
                    ->first();

                if ($inventoryOfNewProduct instanceof Inventory) {
                    $stock = (float) $inventoryOfOldProduct->stock;
                    $reservedStock = (float) $inventoryOfOldProduct->reserved_stock;

                    foreach ($inventoryOfOldProduct->inventoryUnits as $oldInventoryInventoryUnit) {
                        $inventoryUnitQueries->updateInventoryId(
                            $oldInventoryInventoryUnit,
                            $inventoryOfNewProduct->id
                        );
                    }

                    foreach ($inventoryOfOldProduct->reservedStocksWithDeleted as $oldInventoryReservedStock) {
                        $reservedStockQueries->updateInventoryId(
                            $oldInventoryReservedStock,
                            $inventoryOfNewProduct->id
                        );
                    }

                    foreach ($inventoryOfOldProduct->transitStocksWithDeleted as $oldInventoryTransitStock) {
                        $transitStockQueries->updateInventoryIdDuringProductMerge(
                            $oldInventoryTransitStock,
                            $inventoryOfNewProduct->id
                        );
                    }

                    foreach ($inventoryOfOldProduct->saleItemUnits as $oldInventorySaleItemUnit) {
                        $saleItemUnitQueries->updateInventoryId($oldInventorySaleItemUnit, $inventoryOfNewProduct->id);
                    }

                    foreach ($inventoryOfOldProduct->orderItemUnits as $oldInventoryOrderItemUnit) {
                        $orderItemUnitQueries->updateInventoryId($oldInventoryOrderItemUnit, $inventoryOfNewProduct->id);
                    }

                    foreach ($inventoryOfOldProduct->purchaseOrderFulfillmentItemUnits as $purchaseOrderFulfillmentItemUnit) {
                        $purchaseOrderFulfillmentItemUnitQueries->updateInventoryId(
                            $purchaseOrderFulfillmentItemUnit,
                            $inventoryOfNewProduct->id
                        );
                    }

                    foreach ($inventoryOfOldProduct->stockTransferItemUnitsWithDeleted as $oldInventoryStockTransferItemUnit) {
                        $stockTransferItemUnitQueries->updateInventoryId(
                            $oldInventoryStockTransferItemUnit,
                            $inventoryOfNewProduct->id
                        );
                    }

                    $inventoryQueries->increaseStockAndReservedStockAndDeleteOldInventoryData(
                        $inventoryOfNewProduct,
                        $inventoryOfOldProduct,
                        $stock,
                        $reservedStock
                    );

                    $inventoryUpdateQueries->getByProductIdAndLocationAndUpdateWithNewProductId(
                        $oldProductId,
                        $newProductId,
                        'This entry is of product merge from product id: ' . $oldProductId . 'to product id ' . $newProductId,
                    );

                    continue;
                }

                $inventoryQueries->updateProductId($inventoryOfOldProduct, $newProductId);
                $stock = (float) $inventoryOfOldProduct->stock;

                $inventoryUpdateQueries->getByProductIdAndLocationAndUpdateWithNewProductId(
                    $oldProductId,
                    $newProductId,
                    'This entry is of product merge from product id: ' . $oldProductId . 'to product id ' . $newProductId,
                );
            }

            $inventories = $inventoryQueries->getInventoriesByProductId($newProductId);

            foreach ($inventories as $inventory) {
                $inventoryUpdates = $inventoryUpdateQueries->getInventoryUpdatesByProductIdAndLocation(
                    $inventory->product_id,
                    $inventory->location_id,
                );

                $closingStock = 0;
                foreach ($inventoryUpdates as $inventoryUpdate) {
                    $closingStock += $inventoryUpdate->quantity;
                    $inventoryUpdateQueries->updateClosingStock($inventoryUpdate, (float) $closingStock);
                }

                $inventoryQueries->updateStock($inventory, (float) $closingStock - $inventory->reserved_stock);
            }
        });
    }

    public function getLocation(
        int $requestSourceLocationId,
        int $requestDestinationLocationId,
        int $companyId
    ): array {
        $locationQueries = resolve(LocationQueries::class);
        $sourceLocationId = null;
        $destinationLocationId = null;

        $sourceLocation = $locationQueries->getIdById($requestSourceLocationId, $companyId);

        $sourceLocationId = $sourceLocation?->id;

        $destinationLocation = $locationQueries->getIdById($requestDestinationLocationId, $companyId);

        $destinationLocationId = $destinationLocation?->id;

        return [$sourceLocationId, $destinationLocationId];
    }

    public function decreaseInventoryUnit(
        float $quantity,
        int $inventoryId,
        int $purchaseAmountId,
        ?int $batchId
    ): void {
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);

        $inventoryUnit = $inventoryUnitQueries->getByInventoryIdBatchIdAndPurchaseAmountId(
            $inventoryId,
            $purchaseAmountId,
            $batchId
        );

        if ($inventoryUnit instanceof InventoryUnit) {
            $inventoryUnitQueries->decreaseStock($inventoryUnit, $quantity);
        }
    }

    public function exportInventoriesWithJob(User $user, array $filterData, int $companyId, Collection $columns): array
    {
        $inventoryQueries = resolve(InventoryQueries::class);
        $totalRecords = $inventoryQueries->getInventoriesExportCount($filterData, $companyId);

        if ($totalRecords <= (int) config('app.excel.export.job_limit')) {
            return [
                'exceeds_limit' => false,
            ];
        }

        $exportRecordQueries = resolve(ExportRecordQueries::class);

        $headerColumns = $columns->toArray();

        $exportRecord = $exportRecordQueries->addNew(
            $user,
            $filterData,
            $companyId,
            ExportRecordTypes::INVENTORIES->value,
            $headerColumns,
            $totalRecords
        );

        ExportToExcelJob::dispatch($exportRecord);

        return [
            'exceeds_limit' => true,
            'message' => 'Your export request is being processed in the background. You can track its progress in the Export Record module.',
        ];
    }
}
