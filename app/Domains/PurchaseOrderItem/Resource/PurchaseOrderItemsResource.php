<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderItem\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemsResource extends JsonResource
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

        $derivative = '';
        if ($purchaseOrderItem->derivative) {
            $derivative = $purchaseOrderItem->derivative->name;
        }

        return [
            'id' => $purchaseOrderItem->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_upc' => $product->upc,
            'product_color' => config('app.product_variant') ? null : $product->color?->name ?? 'N/A',
            'product_size' => config('app.product_variant') ? null : $product->size?->name ?? 'N/A',
            'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
            'product_has_batch' => $product->has_batch,
            'quantity' => $purchaseOrderItem->quantity,
            'rejected_quantity' => $purchaseOrderItem->rejected_quantity ?? 0,
            'transferred_quantity' => $purchaseOrderItem->transferred_quantity ?? 0,
            'price_per_unit' => $purchaseOrderItem->price_per_unit ?? 0,
            'remarks' => $purchaseOrderItem->remarks ?? 'N/A',
            'derivative' => $derivative,
            'batch_details' => [],
        ];
    }
}
