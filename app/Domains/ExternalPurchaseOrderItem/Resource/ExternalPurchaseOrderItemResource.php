<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderItem\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalPurchaseOrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $externalPurchaseOrderItem = $this->resource;

        $product = $externalPurchaseOrderItem->product;

        $derivative = '';
        if ($externalPurchaseOrderItem->derivative) {
            $derivative = $externalPurchaseOrderItem->derivative->name;
        }

        return [
            'id' => $externalPurchaseOrderItem->id,
            'product_name' => $product->name,
            'product_upc' => $product->upc,
            'product_color' => config('app.product_variant') ? null : $product->color?->name ?? 'N/A',
            'product_size' => config('app.product_variant') ? null : $product->size?->name ?? 'N/A',
            'quantity' => $externalPurchaseOrderItem->quantity ?? 0,
            'received_quantity' => $externalPurchaseOrderItem->received_quantity ?? 0,
            'remarks' => $externalPurchaseOrderItem->remarks ?? 'N/A',
            'derivative' => $derivative,
        ];
    }
}
