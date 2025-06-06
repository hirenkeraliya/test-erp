<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillmentItemBatch;

use App\Models\PurchaseOrderFulfillmentItemBatch;
use Illuminate\Support\Collection;

class PurchaseOrderFulfillmentItemBatchQueries
{
    public function addNew(array $purchaseOrderFulfillmentItemBatchData): PurchaseOrderFulfillmentItemBatch
    {
        return PurchaseOrderFulfillmentItemBatch::create($purchaseOrderFulfillmentItemBatchData);
    }

    public function deleteBatchItem(int $purchaseOrderFulfillmentItemId, string $batchNumber): void
    {
        PurchaseOrderFulfillmentItemBatch::where('purchase_order_fulfillment_item_id', $purchaseOrderFulfillmentItemId)
            ->whereHas('batch', function ($query) use ($batchNumber): void {
                $query->where('number', $batchNumber);
            })->delete();
    }

    public function addOrUpdate(array $purchaseOrderFulfillmentItemBatchData): PurchaseOrderFulfillmentItemBatch
    {
        return PurchaseOrderFulfillmentItemBatch::updateOrCreate(
            [
                'purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItemBatchData['purchase_order_fulfillment_item_id'],
                'batch_id' => $purchaseOrderFulfillmentItemBatchData['batch_id'],
            ],
            [
                'is_discrepancy' => $purchaseOrderFulfillmentItemBatchData['is_discrepancy'],
                'received_quantity' => $purchaseOrderFulfillmentItemBatchData['received_quantity'],
            ]
        );
    }

    public function addOrUpdateWithQuantity(
        array $purchaseOrderFulfillmentItemBatchData
    ): PurchaseOrderFulfillmentItemBatch {
        return PurchaseOrderFulfillmentItemBatch::updateOrCreate(
            [
                'purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItemBatchData['purchase_order_fulfillment_item_id'],
                'batch_id' => $purchaseOrderFulfillmentItemBatchData['batch_id'],
            ],
            [
                'is_discrepancy' => $purchaseOrderFulfillmentItemBatchData['is_discrepancy'],
                'received_quantity' => $purchaseOrderFulfillmentItemBatchData['received_quantity'],
                'quantity' => $purchaseOrderFulfillmentItemBatchData['quantity'],
            ]
        );
    }

    public function getBasicColumnNames(): string
    {
        return 'id,purchase_order_fulfillment_item_id,batch_id,quantity,is_discrepancy,received_quantity';
    }

    public function deleteByPurchaseOrderFulfillmentItem(int $purchaseOrderFulfillmentItemId): void
    {
        PurchaseOrderFulfillmentItemBatch::where(
            'purchase_order_fulfillment_item_id',
            $purchaseOrderFulfillmentItemId
        )->delete();
    }

    public function getByFulfillmentItemId(int $purchaseOrderFulfillmentItemId): Collection
    {
        return PurchaseOrderFulfillmentItemBatch::query()
            ->where('purchase_order_fulfillment_item_id', $purchaseOrderFulfillmentItemId)
            ->where('is_discrepancy', true)
            ->get();
    }
}
