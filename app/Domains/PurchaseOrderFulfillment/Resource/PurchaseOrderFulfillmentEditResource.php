<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillment\Resource;

use App\Models\Batch;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PurchaseOrderFulfillmentEditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $purchaseOrderFulfillment = $this->resource;

        $items = $purchaseOrderFulfillment->items;
        $purchaseOrderItems = $purchaseOrderFulfillment->purchaseOrder->items;

        return [
            'id' => $purchaseOrderFulfillment->id,
            'purchase_order_id' => $purchaseOrderFulfillment->purchase_order_id,
            'happened_at' => $purchaseOrderFulfillment->happened_at,
            'delivery_order_number' => $purchaseOrderFulfillment->delivery_order_number,
            'notes' => $purchaseOrderFulfillment->notes,
            'transfer_items' => $this->getTransferItems($purchaseOrderItems, $items),
        ];
    }

    public function getTransferItems(Collection $purchaseOrderItems, Collection $items): Collection
    {
        return $purchaseOrderItems->map(function (PurchaseOrderItem $purchaseOrderItem) use ($items): array {
            /** @var Product $product */
            $product = $purchaseOrderItem->product;

            $purchaseOrderFulfillmentItem = $items->firstWhere('purchase_order_item_id', $purchaseOrderItem->id);

            $itemBatches = $purchaseOrderFulfillmentItem?->itemBatches;

            return [
                'id' => $purchaseOrderFulfillmentItem?->id,
                'purchase_order_item_id' => $purchaseOrderItem->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_upc' => $product->upc,
                'product_color' => config('app.product_variant') ? null : $product->color?->name,
                'product_size' => config('app.product_variant') ? null : $product->size?->name,
                'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
                'product_has_batch' => $product->has_batch,
                'quantity' => ($purchaseOrderItem->quantity - $purchaseOrderItem->rejected_quantity - $purchaseOrderItem->transferred_quantity + $purchaseOrderFulfillmentItem?->transfer_quantity),
                'rejected_quantity' => $purchaseOrderItem->rejected_quantity ?? 0,
                'transfer_quantity' => $purchaseOrderFulfillmentItem?->transfer_quantity,
                'price_per_unit' => $purchaseOrderItem->price_per_unit ?? 0,
                'remarks' => $purchaseOrderItem->remarks ?? 'N/A',
                'package_type_id' => $purchaseOrderFulfillmentItem?->package_type_id,
                'package_quantity' => $purchaseOrderFulfillmentItem?->package_quantity,
                'package_total_quantity' => $purchaseOrderFulfillmentItem?->package_total_quantity,
                'batch_details' => $product->has_batch && $itemBatches ? $this->getBatchDetails($itemBatches) : [],
            ];
        });
    }

    /**
     * @return mixed[]
     */
    private function getBatchDetails(Collection $itemBatches): array
    {
        return $itemBatches->transform(function ($itemBatch): array {
            /** @var ?Batch $batch */
            $batch = $itemBatch->batch;

            return [
                'batch_number' => $batch instanceof Batch ? $batch->number : 'N/A',
                'quantity' => $itemBatch->quantity,
            ];
        })->toArray();
    }
}
