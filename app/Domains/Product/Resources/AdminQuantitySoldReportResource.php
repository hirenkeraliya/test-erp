<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminQuantitySoldReportResource extends JsonResource
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

        /** @var string ?$colorName */
        $colorName = $product?->color_name ?? 'N/A';

        /** @var string ?$sizeName */
        $sizeName = $product?->size_name ?? 'N/A';

        /** @var array ?$variant_values */
        $variantValues = config('app.product_variant')
            ? ($product?->variant_values ?? [])
            : [];

        return [
            'id' => $product->id,
            'product' => $product->name,
            'upc' => $product->upc,
            'article_number' => $product->article_number ?? 'N/A',
            'color' => $colorName,
            'size' => $sizeName,
            'product_variant_values' => $variantValues,
            'qty_sold' => $this->calculateDifference(
                $this->resource->toArray(),
                'total_quantity_sold',
                'total_quantity_returned'
            ),
            'amount_sold' => $this->calculateDifference(
                $this->resource->toArray(),
                'total_amount_sold',
                'total_returned_amount'
            ),
            'compare_qty_sold' => $this->calculateDifference(
                $this->resource->toArray(),
                'compare_total_quantity_sold',
                'compare_total_quantity_returned'
            ),
            'compare_sold_amount' => $this->calculateDifference(
                $this->resource->toArray(),
                'compare_total_amount_sold',
                'compare_total_returned_amount'
            ),
        ];
    }

    private function calculateDifference(array $product, string $soldKey, string $returnedKey): float
    {
        $soldValue = $this->getProductValue($product, $soldKey);
        $returnedValue = $this->getProductValue($product, $returnedKey);

        return $soldValue - $returnedValue;
    }

    private function getProductValue(array $product, string $key): float
    {
        return array_key_exists($key, $product) ? ((float) $product[$key]) : 0;
    }
}
