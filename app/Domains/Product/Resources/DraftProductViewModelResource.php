<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\Domains\Product\Services\ProductService;
use App\Models\Category;
use App\Models\MasterProduct;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class DraftProductViewModelResource extends JsonResource
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

        /** @var Carbon $createdAt */
        $createdAt = $product->created_at;

        /** @var Carbon $updatedAt */
        $updatedAt = $product->updated_at;

        $productService = resolve(ProductService::class);
        $attributes = $productService->getAttributesArray($product);

        $isProductVariant = config('app.product_variant');

        /** @var MasterProduct $masterProduct */
        $masterProduct = $isProductVariant ? $product->masterProduct : null;

        /** @var Collection $categories */
        $categories = $isProductVariant ? $masterProduct->categories : $product->categories;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'brand' => $isProductVariant ? $masterProduct->brand : $product->brand,
            'department' => $isProductVariant ? $masterProduct->department : $product->department,
            'attributes' => $isProductVariant ? $attributes : [],
            'unitOfMeasure' => $isProductVariant ? $masterProduct->unitOfMeasure : $product->unitOfMeasure,
            'categories' => $categories->map(function ($category): array {
                /** @var Category $productCategory */
                $productCategory = $category;

                return [
                    'id' => $productCategory->id,
                    'name' => $productCategory->name,
                ];
            }),
            'upc' => $product->upc,
            'color' => $isProductVariant ? null : $product->color,
            'size' => $isProductVariant ? null : $product->size,
            'style' => $isProductVariant ? null : $product->style,
            'season' => $isProductVariant ? null : $product->season,
            'article_number' => $isProductVariant ? $masterProduct->article_number : $product->article_number,
            'retail_price' => (float) $product->retail_price,
            'created_at' => $createdAt->format('d-m-Y h:i:s A'),
            'status' => $product->status,
            'images' => $this->preparedImages($product),
            'thumbnail_url' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
            'updated_at' => $updatedAt->format('d-m-Y h:i:s A'),
            'ean' => $product->ean,
            'custom_sku' => $product->custom_sku,
            'manufacturer_sku' => $product->manufacturer_sku,
            'franchise_price_1' => $product->franchise_price_1,
            'franchise_price_2' => $product->franchise_price_2,
            'franchise_price_3' => $product->franchise_price_3,
            'wholesale_price' => $product->wholesale_price,
            'company_or_tender_price' => $product->company_or_tender_price,
            'branch_price' => $product->branch_price,
            'minimum_price' => $product->minimum_price,
            'original_capital_price' => $product->original_capital_price,
            'capital_price' => $product->capital_price,
            'purchase_cost' => $product->purchase_cost,
            'is_temporarily_unavailable' => $product->is_temporarily_unavailable,
            'has_batch' => $isProductVariant ? $masterProduct->has_batch : $product->has_batch,
            'is_non_inventory' => $isProductVariant ? $masterProduct->is_non_inventory : $product->is_non_inventory,
            'is_non_selling_item' => $isProductVariant ? $masterProduct->is_non_selling_item : $product->is_non_selling_item,
            'created_by_id' => $isProductVariant ? $masterProduct->created_by_id : $product->created_by_id,
            'created_by_type' => $isProductVariant ? $masterProduct->created_by_type : $product->created_by_type,
            'created_by' => $isProductVariant ? $masterProduct->createdBy->employee->getFullName() : $product->createdBy?->employee?->getFullName(),
        ];
    }

    public function preparedImages(Product $product): array
    {
        return [
            'image_urls' => $product->getDiskBasedMediaUrls('images'),
            'video_urls' => $product->getDiskBasedMediaUrls('videos'),
            'thumbnail_url' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
        ];
    }
}
