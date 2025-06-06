<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Models\Model;
use App\Models\Product;
use App\Models\StockAdjustmentItem;
use Illuminate\Foundation\Auth\User;

class StockAdjustmentInventoryService
{
    public function updateInventory(
        StockAdjustmentItem $stockAdjustmentItem,
        array $stockAdjustmentProduct,
        User $user,
        int $locationId,
        Product $product,
        int $purchaseAmountId,
        ?int $batchId
    ): void {
        $inventoryQueries = resolve(InventoryQueries::class);

        $inventory = $inventoryQueries->fetchOrCreate($locationId, $product->id);

        $inventoryStock = $inventoryQueries->increaseStock($inventory, (float) $stockAdjustmentProduct['quantity']);
        $inventoryStock += $inventory->reserved_stock;

        if ($stockAdjustmentProduct['quantity'] > 0) {
            $this->updateInventoryUnits(
                $inventory->id,
                $product->id,
                $locationId,
                $batchId,
                $stockAdjustmentItem,
                $user,
                $stockAdjustmentProduct,
                $inventoryStock,
                $purchaseAmountId
            );

            return;
        }

        $this->removeInventoryUnits(
            $inventory->id,
            $product,
            $locationId,
            $batchId,
            $stockAdjustmentItem,
            $user,
            $stockAdjustmentProduct,
            $inventoryStock,
        );
    }

    public function updateInventoryUnits(
        int $inventoryId,
        int $productId,
        int $locationId,
        ?int $batchId,
        StockAdjustmentItem $stockAdjustmentItem,
        User $user,
        array $stockAdjustmentProduct,
        float $inventoryStock,
        int $purchaseAmountId
    ): void {
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);

        $inventoryService = resolve(InventoryService::class);
        $inventoryService->updateInventoryUnit(
            (float) $stockAdjustmentProduct['quantity'],
            $inventoryId,
            $purchaseAmountId,
            $batchId
        );

        $inventoryUpdateQueries->addNew(
            $productId,
            (float) $stockAdjustmentProduct['quantity'],
            $locationId,
            $stockAdjustmentItem,
            $user,
            $inventoryStock,
            $batchId,
            $purchaseAmountId,
        );
    }

    public function removeInventoryUnits(
        int $inventoryId,
        Product $product,
        int $locationId,
        ?int $batchId,
        Model $model,
        User $user,
        array $inventoryDetails,
        float $inventoryStock,
    ): void {
        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryService = resolve(InventoryService::class);

        $inventoryUnits = $inventoryService->getInventoryUnits($product->has_batch, $inventoryId, $batchId);

        $totalQuantity = abs((float) $inventoryDetails['quantity']);
        $inventoryStock += $totalQuantity;

        foreach ($inventoryUnits as $inventoryUnit) {
            if ($totalQuantity <= 0) {
                return;
            }

            if ($inventoryUnit->quantity >= $totalQuantity) {
                $inventoryUnitQueries->decreaseStock($inventoryUnit, $totalQuantity);
                $inventoryStock -= $totalQuantity;

                $inventoryUpdateQueries->addNew(
                    $product->id,
                    (float) ('-' . $totalQuantity),
                    $locationId,
                    $model,
                    $user,
                    $inventoryStock,
                    $inventoryUnit->batch_id,
                    $inventoryUnit->purchase_amount_id,
                );

                $totalQuantity = 0;

                return;
            }

            $totalQuantity -= $inventoryUnit->quantity;
            $inventoryStock -= $inventoryUnit->quantity;
            $inventoryUpdateQueries->addNew(
                $product->id,
                (float) ('-' . $inventoryUnit->quantity),
                $locationId,
                $model,
                $user,
                $inventoryStock,
                $inventoryUnit->batch_id,
                $inventoryUnit->purchase_amount_id,
            );

            $inventoryUnitQueries->decreaseStock($inventoryUnit, (float) $inventoryUnit->quantity);
        }
    }
}
