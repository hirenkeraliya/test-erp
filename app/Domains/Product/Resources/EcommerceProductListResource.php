<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Department;
use App\Models\MasterProduct;
use App\Models\Product;
use App\Models\Season;
use App\Models\Size;
use App\Models\Style;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class EcommerceProductListResource extends JsonResource
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
        $product = $this->resource;

        /** @var Collection $categories */
        $categories = $product->categories;

        /** @var Collection $tags */
        $tags = $product->tags;

        /** @var Collection $saleChannels */
        $saleChannels = $product->saleChannels;

        /** @var ?Color $color */
        $color = $product->color;

        /** @var ?Size $size */
        $size = $product->size;

        /** @var ?Brand $brand */
        $brand = $product->brand;

        /** @var ?Department $department */
        $department = $product->department;

        /** @var ?Style $style */
        $style = $product->style;

        /** @var ?Season $season */
        $season = $product->season;

        /** @var Carbon $updatedAt */
        $updatedAt = $product->updated_at;

        /** @var Carbon $createdAt */
        $createdAt = $product->created_at;

        $masterProductArray = null;
        /** @var ?MasterProduct $masterProduct */
        $masterProduct = $product->masterProduct;

        if ($masterProduct instanceof MasterProduct) {
            /** @var ?Department $masterProductDepartment */
            $masterProductDepartment = $masterProduct->department;

            /** @var ?Brand $masterProductBrand */
            $masterProductBrand = $masterProduct->brand;

            /** @var Collection $masterProductCategories */
            $masterProductCategories = $masterProduct->categories;

            /** @var Carbon $masterProductUpdatedAt */
            $masterProductUpdatedAt = $masterProduct->updated_at;

            /** @var Carbon $masterProductCreatedAt */
            $masterProductCreatedAt = $product->created_at;

            $masterProductArray = [
                'id' => $masterProduct->id,
                'name' => $masterProduct->name,
                'description' => $masterProduct->description,
                'code' => $masterProduct->code,
                'department' => $masterProductDepartment instanceof Department ? [
                    'id' => $masterProductDepartment->id,
                    'name' => $masterProductDepartment->name,
                    'code' => $masterProductDepartment->code,
                    'created_at' => $this->formatDate($masterProductDepartment->created_at),
                    'updated_at' => $this->formatDate($masterProductDepartment->updated_at),
                ] : null,
                'brand' => $masterProductBrand instanceof Brand ? [
                    'id' => $masterProductBrand->id,
                    'name' => $masterProductBrand->name,
                    'code' => $masterProductBrand->code,
                    'created_at' => $this->formatDate($masterProductBrand->created_at),
                    'updated_at' => $this->formatDate($masterProductBrand->updated_at),
                ] : null,
                'article_number' => (string) $masterProduct->article_number,
                'categories' => $masterProductCategories->map(function ($category): array {
                    /** @var Category $masterProductCategory */
                    $masterProductCategory = $category;

                    $masterProductCategoryPivot = $category->pivot;

                    return [
                        'id' => $masterProductCategory->id,
                        'sort_order' => $masterProductCategoryPivot->sort_order,
                    ];
                }),
                'tags' => $masterProduct->tags->map(fn ($tag): array => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ]),
                'status' => $masterProduct->status ? 'Active' : 'Inactive',
                'updated_at' => $masterProductUpdatedAt->format('Y-m-d H:i:s'),
                'created_at' => $masterProductCreatedAt->format('Y-m-d H:i:s'),
                'thumbnail' => $masterProduct->getDiskBasedFirstMediaUrl('thumbnail'),
                'images' => $masterProduct->getDiskBasedMediaUrls('images'),
                'videos' => $masterProduct->getDiskBasedMediaUrls('videos'),
                'thumbnail_detail' => $masterProduct->getIdAndName('thumbnail'),
                'image_details' => $masterProduct->getDiskBasedMediaIdAndNames('images'),
                'video_details' => $masterProduct->getDiskBasedMediaIdAndNames('videos'),
            ];
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'code' => $product->code,
            'season' => $season instanceof Season ? [
                'id' => $season->id,
                'name' => $season->name,
                'code' => $season->code,
                'created_at' => $this->formatDate($season->created_at),
                'updated_at' => $this->formatDate($season->updated_at),
            ] : null,

            'department' => $department instanceof Department ? [
                'id' => $department->id,
                'name' => $department->name,
                'code' => $department->code,
                'created_at' => $this->formatDate($department->created_at),
                'updated_at' => $this->formatDate($department->updated_at),
            ] : null,
            'color' => $color instanceof Color ? [
                'id' => $color->id,
                'name' => $color->name,
                'code' => $color->code,
                'created_at' => $this->formatDate($color->created_at),
                'updated_at' => $this->formatDate($color->updated_at),
            ] : null,
            'size' => $size instanceof Size ? [
                'id' => $size->id,
                'name' => $size->name,
                'code' => $size->code,
                'created_at' => $this->formatDate($size->created_at),
                'updated_at' => $this->formatDate($size->updated_at),
            ] : null,
            'brand' => $brand instanceof Brand ? [
                'id' => $brand->id,
                'name' => $brand->name,
                'code' => $brand->code,
                'created_at' => $this->formatDate($brand->created_at),
                'updated_at' => $this->formatDate($brand->updated_at),
            ] : null,
            'style' => $style instanceof Style ? [
                'id' => $style->id,
                'name' => $style->name,
                'code' => $style->code,
                'created_at' => $this->formatDate($style->created_at),
                'updated_at' => $this->formatDate($style->updated_at),
            ] : null,
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
            'tags' => $tags->map(fn ($tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
            ]),
            'sale_channels' => $saleChannels->map(fn ($saleChannel): array => [
                'id' => $saleChannel->id,
                'name' => $saleChannel->name,
            ]),
            'status' => $product->status ? 'Active' : 'Inactive',
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'thumbnail' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
            'images' => $product->getDiskBasedMediaUrls('images'),
            'videos' => $product->getDiskBasedMediaUrls('videos'),
            'thumbnail_detail' => $product->getIdAndName('thumbnail'),
            'image_details' => $product->getDiskBasedMediaIdAndNames('images'),
            'video_details' => $product->getDiskBasedMediaIdAndNames('videos'),
            'attributes' => $product->productVariantValues->isNotEmpty() ? $product->productVariantValues : null,
            'master_product' => $masterProductArray,
        ];
    }

    public function formatDate(?Carbon $date): string
    {
        /** @var Carbon $date */
        return $date->format('Y-m-d H:i:s');
    }
}
