<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\Domains\Product\Enums\ProductTypes;
use App\Models\Category;
use App\Models\MasterProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ProductDetailsForApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->resource;

        /** @var Collection $categories */
        $categories = $product->categories;

        $masterProductArray = null;
        /** @var ?MasterProduct $masterProduct */
        $masterProduct = $product->masterProduct;

        if ($masterProduct instanceof MasterProduct) {
            $masterProductArray = [
                'id' => $masterProduct->id,
                'name' => $masterProduct->name,
                'article_number' => (string) $masterProduct->article_number,
                'brand' => $masterProduct->brand,
                'type_id' => [
                    'id' => $masterProduct->type_id,
                    'name' => ProductTypes::getFormattedCaseName($masterProduct->type_id),
                    'key' => ProductTypes::getCaseNameByValue($masterProduct->type_id),
                ],
                'categories' => $masterProduct->categories->map(function ($category): array {
                    /** @var Category $masterProductCategory */
                    $masterProductCategory = $category;

                    return [
                        'id' => $masterProductCategory->id,
                        'name' => $masterProductCategory->name,
                    ];
                }),
                'images' => $this->preparedImages($masterProduct),
                'thumbnail_url' => $masterProduct->getDiskBasedFirstMediaUrl('thumbnail'),
            ];
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'article_number' => $product->article_number,
            'upc' => $product->upc,
            'retail_price' => (float) $product->retail_price,
            'brand' => $product->brand,
            'color' => $product->color,
            'size' => $product->size,
            'type_id' => [
                'id' => $product->type_id,
                'name' => ProductTypes::getFormattedCaseName($product->type_id),
                'key' => ProductTypes::getCaseNameByValue($product->type_id),
            ],
            'categories' => $categories->map(function ($category): array {
                /** @var Category $productCategory */
                $productCategory = $category;

                return [
                    'id' => $productCategory->id,
                    'name' => $productCategory->name,
                ];
            }),
            'images' => $this->preparedImages($product),
            'thumbnail_url' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
            'attributes' => $product->productVariantValues->isNotEmpty() ? $product->productVariantValues : null,
            'master_product' => $masterProductArray,
        ];
    }

    public function preparedImages(Product|MasterProduct $product): array
    {
        return [
            'image_urls' => $product->getDiskBasedMediaUrls('images'),
            'video_urls' => $product->getDiskBasedMediaUrls('videos'),
            'thumbnail_url' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
        ];
    }
}
