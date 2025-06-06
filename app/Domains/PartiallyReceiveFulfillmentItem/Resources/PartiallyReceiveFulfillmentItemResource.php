<?php

declare(strict_types=1);

namespace App\Domains\PartiallyReceiveFulfillmentItem\Resources;

use App\Models\Product;
use App\Models\PurchaseOrderFulfillmentItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartiallyReceiveFulfillmentItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $partiallyReceiveFulfillmentItem = $this->resource;

        /** @var PurchaseOrderFulfillmentItem $purchaseOrderFulfillmentItem */
        $purchaseOrderFulfillmentItem = $partiallyReceiveFulfillmentItem->purchaseOrderFulfillmentItem;

        /** @var Product $product */
        $product = $purchaseOrderFulfillmentItem->product;

        return [
            'id' => $partiallyReceiveFulfillmentItem->id,
            'name' => $product->name,
            'color' => config('app.product_variant') ? null : $product->color?->name ?? 'N/A',
            'size' => config('app.product_variant') ? null : $product->size?->name ?? 'N/A',
            'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
            'received_quantity' => $partiallyReceiveFulfillmentItem->received_quantity,
        ];
    }
}
