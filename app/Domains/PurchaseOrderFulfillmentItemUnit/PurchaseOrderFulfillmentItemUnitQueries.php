<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillmentItemUnit;

use App\Models\PurchaseOrderFulfillmentItemUnit;

class PurchaseOrderFulfillmentItemUnitQueries
{
    public function addNew(array $purchaseOrderFulfillmentItemUnitData): void
    {
        PurchaseOrderFulfillmentItemUnit::create($purchaseOrderFulfillmentItemUnitData);
    }

    public function increaseQuantity(
        PurchaseOrderFulfillmentItemUnit $purchaseOrderFulfillmentItemUnit,
        float $quantity
    ): void {
        $purchaseOrderFulfillmentItemUnit->quantity += $quantity;
        $purchaseOrderFulfillmentItemUnit->save();
    }

    public function decreaseQuantity(
        PurchaseOrderFulfillmentItemUnit $purchaseOrderFulfillmentItemUnit,
        float $quantity
    ): void {
        $purchaseOrderFulfillmentItemUnit->quantity -= $quantity;
        $purchaseOrderFulfillmentItemUnit->save();
    }

    public function getByIdInventoryIdPurchaseAmountIdAndBatchId(
        int $purchaseOrderFulfillmentItemId,
        int $inventoryId,
        int $purchaseAmountId,
        ?int $batchId = null,
    ): ?PurchaseOrderFulfillmentItemUnit {
        return PurchaseOrderFulfillmentItemUnit::query()
            ->select('id', 'quantity')
            ->where('purchase_order_fulfillment_item_id', $purchaseOrderFulfillmentItemId)
            ->where('inventory_id', $inventoryId)
            ->where('purchase_amount_id', $purchaseAmountId)
            ->where('batch_id', $batchId)
            ->first();
    }

    public function updateInventoryId(
        PurchaseOrderFulfillmentItemUnit $purchaseOrderFulfillmentItemUnit,
        int $newInventoryId
    ): void {
        $purchaseOrderFulfillmentItemUnit->inventory_id = $newInventoryId;
        $purchaseOrderFulfillmentItemUnit->save();
    }

    public function getBasicColumn(): string
    {
        return 'id,purchase_order_fulfillment_item_id,inventory_id,purchase_amount_id,batch_id,quantity';
    }
}
