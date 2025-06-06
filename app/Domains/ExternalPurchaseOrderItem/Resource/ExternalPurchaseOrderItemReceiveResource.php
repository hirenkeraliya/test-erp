<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderItem\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalPurchaseOrderItemReceiveResource extends JsonResource
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

        if (($externalPurchaseOrderItem->quantity - $externalPurchaseOrderItem->received_quantity) <= 0) {
            return [];
        }

        return [
            'external_purchase_order_item_id' => $externalPurchaseOrderItem->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_upc' => $product->upc,
            'product_color' => config('app.product_variant') ? null : $product->color?->name ?? 'N/A',
            'product_size' => config('app.product_variant') ? null : $product->size?->name ?? 'N/A',
            'quantity' => ($externalPurchaseOrderItem->quantity - $externalPurchaseOrderItem->received_quantity),
            'quantity_received' => 0,
            'product_has_batch' => $product->has_batch,
            'remarks' => $externalPurchaseOrderItem->remarks ?? 'N/A',
            'unit_of_measure_derivative_id' => $externalPurchaseOrderItem->unit_of_measure_derivative_id,
            'batch_details' => [],
        ];
    }
}
