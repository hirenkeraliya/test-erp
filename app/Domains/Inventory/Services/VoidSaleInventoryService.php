<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\InventoryQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Models\VoidSale;
use Illuminate\Foundation\Auth\User;

class VoidSaleInventoryService
{
    public function addInventory(
        VoidSale $voidSale,
        User $user,
        float $quantity,
        int $locationId,
        int $productId,
        int $purchaseAmountId,
        ?int $batchId,
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
            $voidSale,
            $user,
            $inventoryStock,
            $batchId,
            $purchaseAmountId,
        );
    }
}
