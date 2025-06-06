<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\Domains\Product\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductMatchingUpcInventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $product = $this->resource;

        resolve(ProductService::class);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'compound_product_name' => $product->compound_product_name,
            'has_batch' => config('app.product_variant') ? $product?->masterProduct?->has_batch : $product->has_batch,
            'upc' => $product->upc,
            'color' => config('app.product_variant') ? 'N/A' : $product?->color->name ?? 'N/A',
            'size' => config('app.product_variant') ? 'N/A' : $product?->size->name ?? 'N/A',
            'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
        ];
    }
}
