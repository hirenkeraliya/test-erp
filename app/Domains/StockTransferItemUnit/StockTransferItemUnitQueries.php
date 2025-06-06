<?php

declare(strict_types=1);

namespace App\Domains\StockTransferItemUnit;

use App\Models\InventoryUnit;
use App\Models\StockTransferItemUnit;
use Illuminate\Support\Collection;

class StockTransferItemUnitQueries
{
    public function addNew(
        InventoryUnit $inventoryUnit,
        int $stockTransferItemId,
        int $inventoryId,
        float $quantity
    ): void {
        StockTransferItemUnit::create([
            'stock_transfer_item_id' => $stockTransferItemId,
            'inventory_id' => $inventoryId,
            'purchase_amount_id' => $inventoryUnit->purchase_amount_id,
            'batch_id' => $inventoryUnit->batch_id,
            'quantity' => $quantity,
        ]);
    }

    public function getColumnNames(): string
    {
        return 'id,stock_transfer_item_id,inventory_id,purchase_amount_id,batch_id,quantity';
    }

    public function decreaseQuantity(StockTransferItemUnit $stockTransferItemUnit, float $quantity): void
    {
        $stockTransferItemUnit->quantity -= $quantity;
        $stockTransferItemUnit->save();
    }

    public function updateInventoryId(StockTransferItemUnit $stockTransferItemUnit, int $newInventoryId): void
    {
        $stockTransferItemUnit->inventory_id = $newInventoryId;
        $stockTransferItemUnit->save();
    }

    public function delete(Collection $stockTransferItemUnits): void
    {
        $stockTransferItemUnits->each->delete();
    }
}
