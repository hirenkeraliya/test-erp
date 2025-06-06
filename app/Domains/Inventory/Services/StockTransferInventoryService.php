<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\ReservedStock\ReservedStockQueries;
use App\Domains\StockTransferItemUnit\StockTransferItemUnitQueries;
use App\Domains\TransitStock\TransitStockQueries;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\StockTransferItemUnit;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;

class StockTransferInventoryService
{
    public function updateInventoryUnits(
        Inventory $inventory,
        Product $product,
        int $sourceLocationId,
        StockTransferItem $stockTransferItem,
        User $user,
        float $quantity,
        ?int $batchId = null,
    ): void {
        $inventoryService = resolve(InventoryService::class);

        $totalQuantity = abs($quantity);
        if (config('app.product_variant')) {
            /** @var MasterProduct $masterProduct */
            $masterProduct = $product->masterProduct;
            $inventoryUnits = $inventoryService->getInventoryUnits(
                $masterProduct->has_batch,
                $inventory->id,
                $batchId
            );
        } else {
            $inventoryUnits = $inventoryService->getInventoryUnits($product->has_batch, $inventory->id, $batchId);
        }

        $inventoryStock = ($inventory->stock + $inventory->reserved_stock);

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventoryQueries->decreaseStock($inventory, (string) $quantity);

        foreach ($inventoryUnits as $inventoryUnit) {
            if ($totalQuantity <= 0) {
                return;
            }

            if ($inventoryUnit->quantity >= $totalQuantity) {
                $inventoryStock -= $totalQuantity;

                $this->updateInventoryUnitsForStockTransferItem(
                    $inventory,
                    $inventoryUnit,
                    $stockTransferItem,
                    $user,
                    $product->id,
                    $sourceLocationId,
                    $totalQuantity,
                    $inventoryStock
                );

                $totalQuantity = 0;

                return;
            }

            $totalQuantity -= $inventoryUnit->quantity;
            $inventoryStock -= (float) $inventoryUnit->quantity;

            $this->updateInventoryUnitsForStockTransferItem(
                $inventory,
                $inventoryUnit,
                $stockTransferItem,
                $user,
                $product->id,
                $sourceLocationId,
                (float) $inventoryUnit->quantity,
                $inventoryStock
            );
        }
    }

    public function updateInventoryUnitsForStockTransferItem(
        Inventory $inventory,
        InventoryUnit $inventoryUnit,
        StockTransferItem $stockTransferItem,
        User $user,
        int $productId,
        int $locationId,
        float $quantity,
        float $inventoryStock
    ): void {
        $stockTransferItemUnitQueries = resolve(StockTransferItemUnitQueries::class);
        $stockTransferItemUnitQueries->addNew($inventoryUnit, $stockTransferItem->id, $inventory->id, $quantity);

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryUpdateQueries->addNew(
            $productId,
            (float) ('-' . $quantity),
            $locationId,
            $stockTransferItem,
            $user,
            $inventoryStock,
            $inventoryUnit->batch_id,
            $inventoryUnit->purchase_amount_id,
        );

        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUnitQueries->decreaseStock($inventoryUnit, $quantity);
    }

    public function updateInventoryAsPerStockTransfer(
        StockTransferItem $stockTransferItem,
        StockTransfer $stockTransfer,
        User $user,
        StockTransferItemUnit $stockTransferItemUnit,
    ): void {
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryService = resolve(InventoryService::class);

        /** @var string $stockTransferReceivedDate */
        $stockTransferReceivedDate = $stockTransfer->received_date;

        /** @var Carbon $receivedDate */
        $receivedDate = Carbon::createFromFormat('Y-m-d', $stockTransferReceivedDate);

        $inventoryUpdates = $inventoryUpdateQueries->getRecordsAfterDateByLocationAndProduct(
            $receivedDate->format('Y-m-d'),
            $stockTransfer->destination_location_id,
            $stockTransferItem->product_id
        );

        if ($inventoryUpdates->isNotEmpty()) {
            $latestInventoryUpdate = $inventoryUpdateQueries->getLatestClosingStockBy(
                $receivedDate->format('Y-m-d'),
                $stockTransfer->destination_location_id,
                $stockTransferItem->product_id
            );

            $latesInventoryUpdateClosingStock = 0;

            if ($latestInventoryUpdate) {
                $latesInventoryUpdateClosingStock = (float) $latestInventoryUpdate->closing_stock;
            }

            $inventoryStock = $latesInventoryUpdateClosingStock + $stockTransferItemUnit->quantity;

            $inventoryUpdateQueries->addNew(
                $stockTransferItem->product_id,
                (float) $stockTransferItemUnit->quantity,
                $stockTransfer->destination_location_id,
                $stockTransferItem,
                $user,
                $inventoryStock,
                $stockTransferItemUnit->batch_id,
                $stockTransferItemUnit->purchase_amount_id,
                CommonFunctions::addEndTime($receivedDate->format('Y-m-d'))
            );

            $closingStock = $inventoryStock;
            foreach ($inventoryUpdates as $inventoryUpdate) {
                $closingStock = $inventoryUpdateQueries->updateClosingStockOfPreviousRecord(
                    $inventoryUpdate,
                    (float) $closingStock,
                    $stockTransferItem->id,
                );
            }

            $inventoryId = $inventoryQueries->updateStockBy(
                $stockTransfer->destination_location_id,
                $stockTransferItem->product_id,
                (float) $closingStock
            );

            $inventoryService->updateInventoryUnit(
                (float) $stockTransferItemUnit->quantity,
                $inventoryId,
                $stockTransferItemUnit->purchase_amount_id,
                $stockTransferItemUnit->batch_id
            );

            return;
        }

        $inventory = $inventoryQueries->fetchOrCreate(
            $stockTransfer->destination_location_id,
            $stockTransferItem->product_id
        );

        $inventoryStock = $inventoryQueries->increaseStock($inventory, (float) $stockTransferItemUnit->quantity);

        $inventoryService->updateInventoryUnit(
            (float) $stockTransferItemUnit->quantity,
            $inventory->id,
            $stockTransferItemUnit->purchase_amount_id,
            $stockTransferItemUnit->batch_id
        );

        $inventoryUpdateQueries->addNew(
            $stockTransferItem->product_id,
            (float) $stockTransferItemUnit->quantity,
            $stockTransfer->destination_location_id,
            $stockTransferItem,
            $user,
            $inventoryStock,
            $stockTransferItemUnit->batch_id,
            $stockTransferItemUnit->purchase_amount_id,
            CommonFunctions::addEndTime($receivedDate->format('Y-m-d'))
        );
    }

    public function revertInventoryAsPerStockTransfer(
        StockTransferItem $stockTransferItem,
        StockTransfer $stockTransfer,
        User $user,
        StockTransferItemUnit $stockTransferItemUnit,
        float $revertQuantity
    ): void {
        $inventoryQueries = resolve(InventoryQueries::class);

        $inventory = $inventoryQueries->getInventoryBy(
            $stockTransfer->source_location_id,
            $stockTransferItem->product_id
        );

        $inventoryStock = $inventoryQueries->increaseStock($inventory, $revertQuantity);

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);

        $inventoryService = resolve(InventoryService::class);
        $inventoryService->updateInventoryUnit(
            $revertQuantity,
            $inventory->id,
            $stockTransferItemUnit->purchase_amount_id,
            $stockTransferItemUnit->batch_id
        );

        $inventoryUpdateQueries->addNew(
            $stockTransferItem->product_id,
            $revertQuantity,
            $stockTransfer->source_location_id,
            $stockTransferItem,
            $user,
            $inventoryStock,
            $stockTransferItemUnit->batch_id,
            $stockTransferItemUnit->purchase_amount_id,
        );
    }

    public function revertReservedStock(StockTransferItem $stockTransferItem): void
    {
        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);

        $reservedStocks = $reservedStockQueries->getByAffectedBy($stockTransferItem);
        foreach ($reservedStocks as $reservedStock) {
            $inventory = $inventoryQueries->getInventoryById($reservedStock->inventory_id);
            $inventoryUnit = $inventoryUnitQueries->getById($reservedStock->inventory_unit_id);
            $quantity = (float) $reservedStock->quantity;

            $inventoryUnitQueries->revertReservedStock($inventoryUnit, $quantity);
            $inventoryQueries->revertReservedStock($inventory, $quantity);
            $reservedStockQueries->delete($reservedStock);
        }
    }

    public function removeReservationStock(StockTransferItem $stockTransferItem, User $user): void
    {
        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);

        $reservedStocks = $reservedStockQueries->getByAffectedBy($stockTransferItem);
        foreach ($reservedStocks as $reservedStock) {
            $inventoryUnit = $reservedStock->inventoryUnit;
            $quantity = (float) $reservedStock->quantity;

            $inventoryUnitQueries->decreaseReservedStock($inventoryUnit, $quantity);
            $inventory = $inventoryQueries->decreaseReservedStock($reservedStock->inventory, $quantity);

            $inventoryStock = ($inventory->stock + $inventory->reserved_stock);

            $inventoryUpdateQueries->addNew(
                $inventory->product_id,
                (float) ('-' . $quantity),
                $inventory->location_id,
                $stockTransferItem,
                $user,
                $inventoryStock,
                $inventoryUnit->batch_id,
                $inventoryUnit->purchase_amount_id,
                Carbon::now()->format('Y-m-d H:i:s')
            );

            $reservedStockQueries->delete($reservedStock);
        }
    }

    public function addTransitStock(int $destinationLocationId, StockTransferItem $stockTransferItem): void
    {
        foreach ($stockTransferItem->units as $stockTransferItemUnit) {
            if ($stockTransferItemUnit->quantity > 0) {
                $this->addTransitStockAsPerStockTransferItemUnit(
                    $stockTransferItem,
                    $destinationLocationId,
                    $stockTransferItemUnit,
                );
            }
        }
    }

    private function addTransitStockAsPerStockTransferItemUnit(
        StockTransferItem $stockTransferItem,
        int $destinationLocationId,
        StockTransferItemUnit $stockTransferItemUnit,
    ): void {
        $inventoryQueries = resolve(InventoryQueries::class);
        $transitStockQueries = resolve(TransitStockQueries::class);

        $inventory = $inventoryQueries->fetchOrCreate($destinationLocationId, $stockTransferItem->product_id);

        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUnit = $inventoryUnitQueries->addNewAndGetId(
            $inventory->id,
            $stockTransferItemUnit->purchase_amount_id,
            $stockTransferItemUnit->batch_id
        );

        $transitStockQueries->addNew([
            'inventory_id' => $inventory->id,
            'inventory_unit_id' => $inventoryUnit->id,
            'affected_by_id' => $stockTransferItem->id,
            'affected_by_type' => ModelMapping::STOCK_TRANSFER_ITEM->name,
            'quantity' => (float) $stockTransferItemUnit->quantity,
            'notes' => null,
        ]);
    }

    public function updateInventoryUnitsWithReserved(
        Inventory $inventory,
        Product $product,
        StockTransferItem $stockTransferItem,
        float $quantity,
        ?int $batchId = null,
    ): void {
        $inventoryService = resolve(InventoryService::class);

        if ($stockTransferItem->unitOfMeasureDerivative) {
            $quantity /= (float) $stockTransferItem->unitOfMeasureDerivative->ratio;
        }

        $totalQuantity = abs($quantity);
        if (config('app.product_variant')) {
            /** @var MasterProduct $masterProduct */
            $masterProduct = $product->masterProduct;
            $inventoryUnits = $inventoryService->getInventoryUnits(
                $masterProduct->has_batch,
                $inventory->id,
                $batchId
            );
        } else {
            $inventoryUnits = $inventoryService->getInventoryUnits($product->has_batch, $inventory->id, $batchId);
        }

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->getInventoryById($inventory->id);
        $inventoryQueries->increaseReservedStock($inventory, $quantity);

        foreach ($inventoryUnits as $inventoryUnit) {
            if ($totalQuantity <= 0) {
                return;
            }

            if ($inventoryUnit->quantity >= $totalQuantity) {
                $this->updateInventoryUnitsForStockTransferItemForReserved(
                    $inventory,
                    $inventoryUnit,
                    $stockTransferItem,
                    $totalQuantity,
                );

                $totalQuantity = 0;

                return;
            }

            $totalQuantity -= $inventoryUnit->quantity;

            $this->updateInventoryUnitsForStockTransferItemForReserved(
                $inventory,
                $inventoryUnit,
                $stockTransferItem,
                (float) $inventoryUnit->quantity,
            );
        }
    }

    private function updateInventoryUnitsForStockTransferItemForReserved(
        Inventory $inventory,
        InventoryUnit $inventoryUnit,
        StockTransferItem $stockTransferItem,
        float $quantity,
    ): void {
        $reservedStockQueries = resolve(ReservedStockQueries::class);
        $reservedStockQueries->addNew($inventory->id, $inventoryUnit->id, $quantity, $stockTransferItem);

        $stockTransferItemUnitQueries = resolve(StockTransferItemUnitQueries::class);
        $stockTransferItemUnitQueries->addNew($inventoryUnit, $stockTransferItem->id, $inventory->id, $quantity);

        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUnitQueries->increaseReservedStock($inventoryUnit, $quantity);
    }
}
