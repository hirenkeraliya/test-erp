<?php

declare(strict_types=1);

namespace App\Domains\SaleItemUnit;

use App\Models\InventoryUnit;
use App\Models\Model;
use App\Models\SaleItemUnit;

class SaleItemUnitQueries
{
    public function addNew(
        Model $saleItem,
        int $inventoryId,
        InventoryUnit $inventoryUnit,
        float $quantity
    ): SaleItemUnit {
        return SaleItemUnit::create([
            'sale_item_id' => $saleItem->id,
            'inventory_id' => $inventoryId,
            'purchase_amount_id' => $inventoryUnit->purchase_amount_id,
            'batch_id' => $inventoryUnit->batch_id,
            'serial_number_id' => $inventoryUnit->serial_number_id,
            'quantity' => $quantity,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_item_id,inventory_id,purchase_amount_id,batch_id,quantity,returned_quantity';
    }

    public function getColumnNamesForPos(): string
    {
        return 'id,sale_item_id,batch_id,serial_number_id,quantity';
    }

    public function incrementReturnedQuantity(SaleItemUnit $saleItemUnit, float $quantity): void
    {
        $saleItemUnit->returned_quantity += $quantity;
        $saleItemUnit->save();
    }

    public function updateInventoryId(SaleItemUnit $saleItemUnit, int $newInventoryId): void
    {
        $saleItemUnit->inventory_id = $newInventoryId;
        $saleItemUnit->save();
    }
}
