<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillmentItem\Resource;

use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderFulfillmentItemDiscrepancyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $purchaseOrderFulfillmentItem = $this->resource;

        $transactions = $purchaseOrderFulfillmentItem->transactions;

        $purchaseOrderFulfillmentItemBatches = $purchaseOrderFulfillmentItem->itemBatches;

        $product = $purchaseOrderFulfillmentItem->product;

        $latestTransaction = $transactions->sortDesc()->firstWhere('status', FulfillmentStatuses::RECEIVED->value);

        return [
            'id' => $purchaseOrderFulfillmentItem->id,
            'product' => $product->name,
            'has_batch' => $product->has_batch,
            'size' => config('app.product_variant') ? null : $product->size?->name ?? 'N/A',
            'color' => config('app.product_variant') ? null : $product->color?->name ?? 'N/A',
            'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
            'transfer_quantity' => $purchaseOrderFulfillmentItem->transfer_quantity,
            'received_quantity' => $purchaseOrderFulfillmentItem->received_quantity,
            'discrepancy_type' => $purchaseOrderFulfillmentItem->discrepancy_type,
            'remarks' => $purchaseOrderFulfillmentItem->remarks,
            'delivery_remarks' => $latestTransaction?->remarks,
            'is_extra_item' => $purchaseOrderFulfillmentItem->is_extra_item,
            'batches' => $purchaseOrderFulfillmentItemBatches->map(
                function ($purchaseOrderFulfillmentItemBatch): array {
                    /** @var Batch $batch */
                    $batch = $purchaseOrderFulfillmentItemBatch->batch;

                    return [
                        'batch_number' => $batch->number,
                        'quantity' => $purchaseOrderFulfillmentItemBatch->quantity,
                        'received_quantity' => $purchaseOrderFulfillmentItemBatch->received_quantity,
                        'is_discrepancy' => $purchaseOrderFulfillmentItemBatch->is_discrepancy,
                    ];
                }
            ),
            'batch_details' => [],
            'discrepancy_proof' => $purchaseOrderFulfillmentItem->getDiskBasedFirstMediaUrl('discrepancy_proof'),
        ];
    }
}
