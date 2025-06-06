<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ProductDetailsForIntegrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Product $product */
        $product = $this;

        /** @var Collection $categories */
        $categories = $product->categories;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'article_number' => $product->article_number,
            'upc' => $product->upc,
            'retail_price' => (float) $product->retail_price,
            'purchase_cost' => (float) $product->purchase_cost,
            'brand' => $product->brand,
            'categories' => $categories->map(function ($category): array {
                /** @var Category $productCategory */
                $productCategory = $category;

                return [
                    'id' => $productCategory->id,
                    'name' => $productCategory->name,
                ];
            }),
        ];
    }
}
