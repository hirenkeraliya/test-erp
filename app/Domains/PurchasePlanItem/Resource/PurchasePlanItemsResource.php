<?php

declare(strict_types=1);

namespace App\Domains\PurchasePlanItem\Resource;

use App\Domains\Product\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchasePlanItemsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $productService = resolve(ProductService::class);

        $purchasePlanItem = $this->resource;

        $product = $purchasePlanItem->product;

        [$color, $size] = $productService->getColorAndSize($product);

        $derivative = '';
        if ($purchasePlanItem->derivative) {
            $derivative = $purchasePlanItem->derivative->name;
        }

        return [
            'id' => $purchasePlanItem->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_upc' => $product->upc,
            'product_color' => $color,
            'product_size' => $size,
            'product_has_batch' => $product->has_batch,
            'quantity' => $purchasePlanItem->quantity,
            'transferred_quantity' => $purchasePlanItem->transferred_quantity ?? 0,
            'cost_price' => $purchasePlanItem->cost_price ?? 0,
            'total_price' => $purchasePlanItem->total_price ?? 0,
            'remarks' => $purchasePlanItem->remarks ?? 'N/A',
            'derivative' => $derivative,
            'batch_details' => [],
        ];
    }
}
