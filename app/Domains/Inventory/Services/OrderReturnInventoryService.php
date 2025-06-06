<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Models\OrderReturnItem;
use Illuminate\Foundation\Auth\User;

class OrderReturnInventoryService
{
    public function addInventory(
        OrderReturnItem $orderReturnItem,
        User $user,
        float $quantity,
        int $locationId,
        int $productId,
        int $purchaseAmountId,
        ?int $batchId,
        ?string $happenedAt,
    ): void {
        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->fetchOrCreate($locationId, $productId);

        $inventoryStock = $inventoryQueries->increaseStock($inventory, $quantity);
        $inventoryStock += $inventory->reserved_stock;

        $inventoryService = resolve(InventoryService::class);
        $inventoryService->updateInventoryUnit($quantity, $inventory->id, $purchaseAmountId, $batchId);

        $inventoryUpdateQueries = resolve(InventoryUpdateQueries::class);
        $inventoryUpdateQueries->addNew(
            $productId,
            $quantity,
            $locationId,
            $orderReturnItem,
            $user,
            $inventoryStock,
            $batchId,
            $purchaseAmountId,
            $happenedAt
        );
    }
}
