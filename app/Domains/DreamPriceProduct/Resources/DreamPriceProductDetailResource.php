<?php

declare(strict_types=1);

namespace App\Domains\DreamPriceProduct\Resources;

use App\Models\DreamPriceProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DreamPriceProductDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var DreamPriceProduct $dreamPriceProduct */
        $dreamPriceProduct = $this;

        /** @var Product $product */
        $product = $dreamPriceProduct->product;

        return [
            'name' => $product->name,
            'upc' => $product->upc,
            'color' => config('app.product_variant') ? 'N/A' : $product?->color?->name ?? 'N/A',
            'size' => config('app.product_variant') ? 'N/A' : $product?->size?->name ?? 'N/A',
            'retail_price' => $product->retail_price,
            'price' => $dreamPriceProduct->price,
            'product_variant_values' => config('app.product_variant') ? $product->productVariantValues ?? [] : [],
        ];
    }
}
