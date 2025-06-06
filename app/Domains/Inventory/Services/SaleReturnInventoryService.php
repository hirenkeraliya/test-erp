<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Models\SaleReturnItem;
use Illuminate\Foundation\Auth\User;

class SaleReturnInventoryService
{
    public function addInventory(
        SaleReturnItem $saleReturnItem,
        User $user,
        float $quantity,
        int $locationId,
        int $productId,
        int $purchaseAmountId,
        ?int $batchId,
        ?string $happenedAt,
        ?int $serialNumberId = null,
    ): void {
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->fetchOrCreate($locationId, $productId);

        $inventoryStock = $inventoryQueries->increaseStock($inventory, $quantity);
        $inventoryStock += $inventory->reserved_stock;

        $inventoryService = resolve(InventoryService::class);
        $inventoryService->updateInventoryUnit($quantity, $inventory->id, $purchaseAmountId, $batchId, $serialNumberId);

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryUpdateQueries->addNew(
            $productId,
            $quantity,
            $locationId,
            $saleReturnItem,
            $user,
            $inventoryStock,
            $batchId,
            $purchaseAmountId,
            $happenedAt,
            null,
            $serialNumberId
        );
    }
}
