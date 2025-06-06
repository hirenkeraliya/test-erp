<?php

declare(strict_types=1);

namespace App\Domains\OrderItemUnit;

use App\Models\InventoryUnit;
use App\Models\Model;
use App\Models\OrderItemUnit;

class OrderItemUnitQueries
{
    public function addNew(
        Model $orderItem,
        int $inventoryId,
        InventoryUnit $inventoryUnit,
        float $quantity
    ): OrderItemUnit {
        return OrderItemUnit::create([
            'order_item_id' => $orderItem->id,
            'inventory_id' => $inventoryId,
            'purchase_amount_id' => $inventoryUnit->purchase_amount_id,
            'batch_id' => $inventoryUnit->batch_id,
            'quantity' => $quantity,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,order_item_id,inventory_id,purchase_amount_id,batch_id,quantity,return_quantity';
    }

    public function incrementReturnedQuantity(OrderItemUnit $saleItemUnit, float $quantity): void
    {
        $saleItemUnit->return_quantity += $quantity;
        $saleItemUnit->save();
    }

    public function updateInventoryId(OrderItemUnit $orderItemUnit, int $newInventoryId): void
    {
        $orderItemUnit->inventory_id = $newInventoryId;
        $orderItemUnit->save();
    }
}
