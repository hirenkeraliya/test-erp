<?php

declare(strict_types=1);

namespace App\Domains\PurchasePlanItem\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchasePlanShippingItemsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $purchasePlanItem = $this->resource;

        $product = $purchasePlanItem->product;

        if (($purchasePlanItem->quantity - $purchasePlanItem->transferred_quantity) <= 0) {
            return [];
        }

        return [
            'purchase_plan_item_id' => $purchasePlanItem->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_upc' => $product->upc,
            'product_color' => $product->color?->name,
            'product_size' => $product->size?->name,
            'product_has_batch' => $product->has_batch,
            'quantity' => ($purchasePlanItem->quantity - $purchasePlanItem->transferred_quantity),
            'received_quantity' => 0,
            'remarks' => $purchasePlanItem->remarks ?? '',
            'batch_details' => [],
            'cost_price' => $purchasePlanItem->cost_price,
            'unit_of_measure_derivative_id' => $purchasePlanItem->unit_of_measure_derivative_id,
        ];
    }
}
