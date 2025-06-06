<?php

declare(strict_types=1);

namespace App\Domains\OrderPickingListItem\Resources;

use App\CommonFunctions;
use App\Models\MasterProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderPickingListItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $orderItem = $this->resource;

        /** @var Product $product */
        $product = $orderItem->product;

        if (config('app.product_variant')) {
            /** @var MasterProduct $masterProduct */
            $masterProduct = $product->masterProduct;
        }

        return [
            'id' => $orderItem->id,
            'quantity' => CommonFunctions::truncateDecimal((float) $orderItem->total_quantity),
            'name' => $product->name,
            'upc' => $product->upc,
            'article_number' => config(
                'app.product_variant'
            ) ? $masterProduct->article_number ?? 'N/A' : $product->article_number ?? 'N/A',
            'color' => config('app.product_variant') ? null : $product->color?->name,
            'size' => config('app.product_variant') ? null : $product->size?->name,
            'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
        ];
    }
}
