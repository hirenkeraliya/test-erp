<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderPartialReceiveItem\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalPurchaseOrderPartialReceiveItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $externalPurchaseOrderPartialReceiveItem = $this->resource;

        $externalPurchaseOrderItem = $externalPurchaseOrderPartialReceiveItem->externalPurchaseOrderItem;

        $product = $externalPurchaseOrderItem->product;

        $derivative = '';
        if ($externalPurchaseOrderPartialReceiveItem->derivative) {
            $derivative = $externalPurchaseOrderPartialReceiveItem->derivative->name;
        }

        return [
            'id' => $externalPurchaseOrderPartialReceiveItem->id,
            'product_name' => $product->name,
            'product_upc' => $product->upc,
            'product_color' => config('app.product_variant') ? null : $product->color?->name ?? 'N/A',
            'product_size' => config('app.product_variant') ? null : $product->size?->name ?? 'N/A',
            'quantity' => $externalPurchaseOrderItem->quantity ?? 0,
            'received_quantity' => $externalPurchaseOrderPartialReceiveItem->quantity_received ?? 0,
            'notes' => $externalPurchaseOrderPartialReceiveItem->notes ?? 'N/A',
            'derivative' => $derivative,
        ];
    }
}
