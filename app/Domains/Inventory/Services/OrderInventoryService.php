<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Services;

use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\OrderItemUnit\OrderItemUnitQueries;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Model;
use App\Models\Product;
use Illuminate\Foundation\Auth\User;

class OrderInventoryService
{
    public function updateInventoryUnits(
        Inventory $inventory,
        Product $product,
        int $locationId,
        Model $orderItem,
        User $user,
        float $quantity,
        string $happenedAt,
        ?int $batchId,
    ): void {
        $inventoryService = resolve(InventoryService::class);

        $totalQuantity = abs($quantity);
        $inventoryUnits = $inventoryService->getInventoryUnits($product->has_batch, $inventory->id, $batchId);
        $inventoryStock = ($inventory->stock + $inventory->reserved_stock);

        foreach ($inventoryUnits as $inventoryUnit) {
            if ($totalQuantity <= 0) {
                return;
            }

            if ($inventoryUnit->quantity >= $totalQuantity) {
                $inventoryStock -= $totalQuantity;

                $this->updateInventoryUnitsForOrderItem(
                    $inventory,
                    $inventoryUnit,
                    $orderItem,
                    $user,
                    $product->id,
                    $locationId,
                    $totalQuantity,
                    $inventoryStock,
                    $happenedAt
                );

                $totalQuantity = 0;

                return;
            }

            $totalQuantity -= $inventoryUnit->quantity;
            $inventoryStock -= (float) $inventoryUnit->quantity;

            $this->updateInventoryUnitsForOrderItem(
                $inventory,
                $inventoryUnit,
                $orderItem,
                $user,
                $product->id,
                $locationId,
                (float) $inventoryUnit->quantity,
                $inventoryStock,
                $happenedAt
            );
        }

        if ($totalQuantity > 0.0) {
            $this->updateNegativeInventoryUnitsForOrderItem(
                $inventory,
                $orderItem,
                $user,
                $product->id,
                $locationId,
                $totalQuantity,
                (float) $inventoryStock,
                $happenedAt,
                $batchId
            );
        }
    }

    public function updateInventoryUnitsForOrderItem(
        Inventory $inventory,
        InventoryUnit $inventoryUnit,
        Model $orderItem,
        User $user,
        int $productId,
        int $locationId,
        float $quantity,
        float $inventoryStock,
        string $happenedAt
    ): void {
        $orderItemUnitQueries = resolve(OrderItemUnitQueries::class);
        $orderItemUnitQueries->addNew($orderItem, $inventory->id, $inventoryUnit, $quantity);

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryUpdateQueries->addNew(
            $productId,
            (float) ('-' . $quantity),
            $locationId,
            $orderItem,
            $user,
            $inventoryStock,
            $inventoryUnit->batch_id,
            $inventoryUnit->purchase_amount_id,
            $happenedAt
        );

        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUnitQueries->decreaseStock($inventoryUnit, $quantity);
    }

    public function updateNegativeInventoryUnitsForOrderItem(
        Inventory $inventory,
        Model $orderItem,
        User $user,
        int $productId,
        int $locationId,
        float $quantity,
        float $inventoryStock,
        string $happenedAt,
        ?int $batchId,
    ): void {
        $purchaseAmountQueries = resolve(PurchaseAmountQueries::class);
        $purchaseAmountId = $purchaseAmountQueries->addBlankRecord();

        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUnit = $inventoryUnitQueries->addNewAndGetId($inventory->id, $purchaseAmountId, $batchId);

        $orderItemUnitQueries = resolve(OrderItemUnitQueries::class);
        $orderItemUnitQueries->addNew($orderItem, $inventory->id, $inventoryUnit, $quantity);

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryUpdateQueries->addNew(
            $productId,
            (float) ('-' . $quantity),
            $locationId,
            $orderItem,
            $user,
            $inventoryStock -= $quantity,
            $batchId,
            $purchaseAmountId,
            $happenedAt
        );

        $inventoryUnitQueries->decreaseStock($inventoryUnit, $quantity);
    }
}
