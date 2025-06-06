<?php

declare(strict_types=1);

namespace App\Domains\PurchaseOrderFulfillmentItem\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderFulfillmentItemResource extends JsonResource
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

        $product = $purchaseOrderFulfillmentItem->product;

        return [
            'id' => $purchaseOrderFulfillmentItem->id,
            'product_name' => $product->name,
            'product_upc' => $product->upc,
            'product_color' => config('app.product_variant') ? null : $product->color?->name ?? 'N/A',
            'product_size' => config('app.product_variant') ? null : $product->size?->name ?? 'N/A',
            'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
            'transfer_quantity' => $purchaseOrderFulfillmentItem->transfer_quantity ?? 0,
            'received_quantity' => $purchaseOrderFulfillmentItem->received_quantity ?? 0,
            'remarks' => $purchaseOrderFulfillmentItem->remarks ?? 'N/A',
        ];
    }
}
