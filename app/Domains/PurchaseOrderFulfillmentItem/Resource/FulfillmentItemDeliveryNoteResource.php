<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillmentItem\Resource;

use App\Models\Batch;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class FulfillmentItemDeliveryNoteResource extends JsonResource
{
    public function __construct(
        $resource,
        protected Collection $externalLocationProductStocks
    ) {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        $purchaseOrderFulfillmentItem = $this->resource;

        $purchaseOrderItem = $purchaseOrderFulfillmentItem->purchaseOrderItem;

        $derivative = $purchaseOrderItem?->derivative;

        $unitOfMeasure = $derivative?->unitOfMeasure;

        $product = $purchaseOrderFulfillmentItem->product;

        $externalLocationStock = (array) $this->externalLocationProductStocks->firstWhere('upc', $product->upc);

        $purchaseOrderFulfillmentItemBatches = $purchaseOrderFulfillmentItem->itemBatches;

        return [
            'id' => $purchaseOrderFulfillmentItem->id,
            'purchase_order_fulfillment_id' => $purchaseOrderFulfillmentItem->purchase_order_fulfillment_id,
            'product_name' => $product->name,
            'product_upc' => $product->upc,
            'product_color' => config('app.product_variant') ? null : $product->color?->name,
            'product_size' => config('app.product_variant') ? null : $product->size?->name,
            'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
            'discrepancy_type' => $purchaseOrderFulfillmentItem->discrepancy_type,
            'is_extra_item' => $purchaseOrderFulfillmentItem->is_extra_item,
            'discrepancy_proof' => $purchaseOrderFulfillmentItem->getDiskBasedFirstMediaUrl('discrepancy_proof'),
            'transfer_quantity' => $purchaseOrderFulfillmentItem->transfer_quantity ?? 0,
            'received_quantity' => $purchaseOrderFulfillmentItem->received_quantity ?? 0,
            'partial_received' => $purchaseOrderFulfillmentItem->partialReceivedItems->sum('received_quantity'),
            'total_received' => $purchaseOrderFulfillmentItem->received_quantity ?? 0,
            'remarks' => $purchaseOrderFulfillmentItem->remarks ?? 'N/A',
            'external_stock' => array_key_exists(
                'external_stock',
                $externalLocationStock
            ) ? $externalLocationStock['external_stock'] : (float) 0,
            'has_batch' => config('app.product_variant') ? $product->masterProduct->has_batch : $product->has_batch,
            'batch_details' => [],
            'derivative' => $derivative instanceof UnitOfMeasureDerivative ? [
                'name' => $derivative->name,
                'ratio' => $derivative->ratio,
                'parent_unit_of_measure' => [
                    'name' => $unitOfMeasure->name,
                ],
            ] : null,
            'batches' => $this->getBatchDetails($purchaseOrderFulfillmentItemBatches),
        ];
    }

    public function getBatchDetails(Collection $purchaseOrderFulfillmentItemBatches): array
    {
        return $purchaseOrderFulfillmentItemBatches->map(
            function ($purchaseOrderFulfillmentItemBatch): array {
                /** @var Batch $batch */
                $batch = $purchaseOrderFulfillmentItemBatch->batch;

                return [
                    'purchase_order_fulfillment_item_id' => $purchaseOrderFulfillmentItemBatch->purchase_order_fulfillment_item_id,
                    'received_quantity' => $purchaseOrderFulfillmentItemBatch->received_quantity,
                    'is_discrepancy' => $purchaseOrderFulfillmentItemBatch->is_discrepancy,
                    'batch_number' => $batch->number,
                    'quantity' => $purchaseOrderFulfillmentItemBatch->quantity,
                ];
            }
        )->toArray();
    }
}
