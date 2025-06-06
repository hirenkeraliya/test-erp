<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\PurchaseAmount\PurchaseAmountQueries;
use App\Domains\SaleItemUnit\SaleItemUnitQueries;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\Model;
use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Foundation\Auth\User;

class SaleInventoryService
{
    public function updateInventoryUnits(
        Inventory $inventory,
        Product $product,
        int $locationId,
        Model $saleItem,
        User $user,
        float $quantity,
        string $happenedAt,
        ?int $batchId,
        ?int $serialNumberId = null,
    ): void {
        $inventoryService = resolve(InventoryService::class);

        $totalQuantity = abs($quantity);
        $inventoryUnits = $inventoryService->getInventoryUnits(
            $product->has_batch,
            $inventory->id,
            $batchId,
            $serialNumberId
        );
        $inventoryStock = ($inventory->stock + $inventory->reserved_stock);

        foreach ($inventoryUnits as $inventoryUnit) {
            if ($totalQuantity <= 0) {
                return;
            }

            if ($inventoryUnit->quantity >= $totalQuantity) {
                $inventoryStock -= $totalQuantity;

                $this->updateInventoryUnitsForSaleItem(
                    $inventory,
                    $inventoryUnit,
                    $saleItem,
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

            $this->updateInventoryUnitsForSaleItem(
                $inventory,
                $inventoryUnit,
                $saleItem,
                $user,
                $product->id,
                $locationId,
                (float) $inventoryUnit->quantity,
                $inventoryStock,
                $happenedAt
            );
        }

        if ($totalQuantity > 0.0) {
            $this->updateNegativeInventoryUnitsForSaleItem(
                $inventory,
                $saleItem,
                $user,
                $product->id,
                $locationId,
                $totalQuantity,
                (float) $inventoryStock,
                $happenedAt,
                $batchId,
                $serialNumberId
            );
        }
    }

    public function updateInventoryUnitsForSaleItem(
        Inventory $inventory,
        InventoryUnit $inventoryUnit,
        Model $saleItem,
        User $user,
        int $productId,
        int $locationId,
        float $quantity,
        float $inventoryStock,
        string $happenedAt
    ): void {
        $saleItemUnitQueries = resolve(SaleItemUnitQueries::class);
        $saleItemUnitQueries->addNew($saleItem, $inventory->id, $inventoryUnit, $quantity);

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryUpdateQueries->addNew(
            $productId,
            (float) ('-' . $quantity),
            $locationId,
            $saleItem,
            $user,
            $inventoryStock,
            $inventoryUnit->batch_id,
            $inventoryUnit->purchase_amount_id,
            $happenedAt,
            null,
            $inventoryUnit->serial_number_id,
        );

        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUnitQueries->decreaseStock($inventoryUnit, $quantity);
    }

    public function updateNegativeInventoryUnitsForSaleItem(
        Inventory $inventory,
        Model $saleItem,
        User $user,
        int $productId,
        int $locationId,
        float $quantity,
        float $inventoryStock,
        string $happenedAt,
        ?int $batchId,
        ?int $serialNumberId = null,
    ): void {
        $purchaseAmountQueries = resolve(PurchaseAmountQueries::class);
        $purchaseAmountId = $purchaseAmountQueries->addBlankRecord();

        $inventoryUnitQueries = resolve(InventoryUnitQueries::class);
        $inventoryUnit = $inventoryUnitQueries->addNewAndGetId(
            $inventory->id,
            $purchaseAmountId,
            $batchId,
            $serialNumberId
        );

        $saleItemUnitQueries = resolve(SaleItemUnitQueries::class);
        $saleItemUnitQueries->addNew($saleItem, $inventory->id, $inventoryUnit, $quantity);

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryUpdateQueries->addNew(
            $productId,
            (float) ('-' . $quantity),
            $locationId,
            $saleItem,
            $user,
            $inventoryStock -= $quantity,
            $batchId,
            $purchaseAmountId,
            $happenedAt,
            null,
            $serialNumberId,
        );

        $inventoryUnitQueries->decreaseStock($inventoryUnit, $quantity);
    }

    public function addInventory(
        SaleItem $saleItem,
        User $user,
        float $quantity,
        int $locationId,
        int $purchaseAmountId,
        ?int $batchId,
        ?string $happenedAt,
    ): void {
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->fetchOrCreate($locationId, $saleItem->product_id);

        $inventoryStock = $inventoryQueries->increaseStock($inventory, $quantity);
        $inventoryStock += $inventory->reserved_stock;

        $inventoryService = resolve(InventoryService::class);
        $inventoryService->updateInventoryUnit($quantity, $inventory->id, $purchaseAmountId, $batchId, null);

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryUpdateQueries->addNew(
            $saleItem->product_id,
            $quantity,
            $locationId,
            $saleItem,
            $user,
            $inventoryStock,
            $batchId,
            $purchaseAmountId,
            $happenedAt
        );
    }
}
