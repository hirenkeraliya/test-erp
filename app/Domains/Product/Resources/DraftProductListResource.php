<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\Domains\Product\Services\ProductService;
use App\Models\Brand;
use App\Models\Department;
use App\Models\MasterProduct;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DraftProductListResource extends JsonResource
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

        $categories = $this->getCategories($product);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'brand' => $this->getBrand($product),
            'attributes' => $attributes,
            'color' => config('app.product_variant') ? null : $product->color,
            'size' => config('app.product_variant') ? null : $product->size,
            'department' => $this->getDepartment($product),
            'style' => config('app.product_variant') ? null : $product->style,
            'categories' => $categories,
            'upc' => $product->upc,
            'article_number' => $this->getArticleNumber($product),
            'retail_price' => (float) $product->retail_price,
            'created_at' => $createdAt->format('d-m-Y h:i:s A'),
            'status' => $product->status,
            'images' => $this->preparedImages($product),
            'thumbnail_url' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
            'updated_at' => $updatedAt->format('d-m-Y h:i:s A'),
            'created_by_id' => $this->getCreatedById($product),
            'created_by_type' => $this->getCreatedByType($product),
            'match_count' => $product->match_count ?? 0,
            'created_by' => $this->getCreatedByName($product),
        ];
    }

    private function preparedImages(Product|MasterProduct $product): array
    {
        return [
            'image_urls' => $product->getDiskBasedMediaUrls('images'),
            'video_urls' => $product->getDiskBasedMediaUrls('videos'),
            'thumbnail_url' => $product->getDiskBasedFirstMediaUrl('thumbnail'),
        ];
    }

    private function getCategories(Product $product): ?array
    {
        $categories = config('app.product_variant')
            ? $product->masterProduct?->categories
            : $product->categories;

        return $categories?->map(fn ($category): array => [
            'id' => $category->id,
            'name' => $category->name,
        ])->toArray();
    }

    private function getDepartment(Product $product): ?Department
    {
        return config('app.product_variant') ? $product->masterProduct?->department : $product->department;
    }

    private function getBrand(Product $product): ?Brand
    {
        return config('app.product_variant') ? $product->masterProduct?->brand : $product->brand;
    }

    private function getArticleNumber(Product $product): ?string
    {
        return config('app.product_variant') ? $product->masterProduct?->article_number : $product->article_number;
    }

    private function getCreatedById(Product $product): ?int
    {
        return config('app.product_variant')
            ? $product->masterProduct?->created_by_id
            : $product->created_by_id;
    }

    private function getCreatedByType(Product $product): ?string
    {
        return config('app.product_variant')
            ? $product->masterProduct?->created_by_type
            : $product->created_by_type;
    }

    private function getCreatedByName(Product $product): string
    {
        return config('app.product_variant')
            ? $product->masterProduct?->createdBy?->employee?->getFullName() ?? 'N/A'
            : $product->createdBy?->employee?->getFullName() ?? 'N/A';
    }
}
