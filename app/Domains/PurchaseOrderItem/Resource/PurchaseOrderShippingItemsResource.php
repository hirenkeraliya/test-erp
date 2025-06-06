<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderItem\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderShippingItemsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $purchaseOrderItem = $this->resource;

        $product = $purchaseOrderItem->product;

        if (($purchaseOrderItem->quantity - $purchaseOrderItem->rejected_quantity - $purchaseOrderItem->transferred_quantity) <= 0) {
            return [];
        }

        return [
            'purchase_order_item_id' => $purchaseOrderItem->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_upc' => $product->upc,
            'product_color' => config('app.product_variant') ? null : $product->color?->name,
            'product_size' => config('app.product_variant') ? null : $product->size?->name,
            'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
            'product_has_batch' => config(
                'app.product_variant'
            ) ? $product->masterProduct->has_batch : $product->has_batch,
            'quantity' => ($purchaseOrderItem->quantity - $purchaseOrderItem->rejected_quantity - $purchaseOrderItem->transferred_quantity),
            'rejected_quantity' => $purchaseOrderItem->rejected_quantity ?? 0,
            'transfer_quantity' => 0,
            'price_per_unit' => $purchaseOrderItem->price_per_unit ?? 0,
            'remarks' => $purchaseOrderItem->remarks ?? 'N/A',
            'batch_details' => [],
        ];
    }
}
