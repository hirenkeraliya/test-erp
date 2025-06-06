<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ProductUpdateWebhookResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Product $product */
        $product = $this->resource;

        /** @var Collection $categories */
        $categories = $product->categories;

        /** @var Carbon $updatedAt */
        $updatedAt = $product->updated_at;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'code' => $product->code,
            'season' => $product->season_id,
            'department' => $product->department_id,
            'vendor' => $product->vendor_id,
            'color' => $product->color_id,
            'size' => $product->size_id,
            'brand' => $product->brand_id,
            'style' => $product->style_id,
            'upc' => $product->upc,
            'ean' => $product->ean,
            'custom_sku' => $product->custom_sku,
            'manufacturer_sku' => $product->manufacturer_sku,
            'article_number' => $product->article_number,
            'price' => (float) $product->retail_price,
            'online_price' => (float) $product->online_price,
            'categories' => $categories->map(function ($category): array {
                /** @var Category $productCategory */
                $productCategory = $category;

                $productCategoryPivot = $category->pivot;

                return [
                    'id' => $productCategory->id,
                    'sort_order' => $productCategoryPivot->sort_order,
                ];
            }),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
            'thumbnail' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
            'images' => $product->getDiskBasedMediaUrls('images'),
            'videos' => $product->getDiskBasedMediaUrls('videos'),
        ];
    }
}
