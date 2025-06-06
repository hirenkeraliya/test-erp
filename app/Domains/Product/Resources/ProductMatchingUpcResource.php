<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductMatchingUpcResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Product $product */
        $product = $this;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'has_batch' => $product->has_batch,
            'retail_price' => (float) $product->retail_price,
            'upc' => $product->upc,
            'color' => config('app.product_variant') ? null : $product->color?->name ?? null,
            'size' => config('app.product_variant') ? null : $product->size?->name ?? null,
            'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
        ];
    }
}
