<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\Domains\Permission\Services\PermissionModuleService;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Services\ProductService;
use App\Domains\Product\SubDepartment\Enums\SubDepartments;
use App\Models\Product;
use App\Models\Season;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ProductListForMergeResource extends JsonResource
{
    public function __construct(
        $resource,
        public array $allowedAllPermissionList = []
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var Product $product */
        $product = $this->resource;

        /** @var ?Season $season */
        $season = $product->season;

        $requiredPermissions = array_map(
            fn ($value): string => 'product_' . $value,
            PermissionModuleService::getModuleSubLists()['Product']
        );

        /** @var array<mixed, mixed>|mixed $optionalColumns */
        $optionalColumns = array_map(
            fn ($value): string => Str::replace('product_', '', $value),
            array_diff($requiredPermissions, $this->allowedAllPermissionList)
        );

        $productService = resolve(ProductService::class);

        $productDetails = [
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code ?? 'N/A',
            'unit_of_measure' => config(
                'app.product_variant'
            ) ? $product->masterProduct?->unitOfMeasure ?? 'N/A' : $product->unitOfMeasure,
            'season' => $season->name ?? 'N/A',
            'brand' => $this->getBrand($product),
            'attributes' => $productService->getAttributesArray($product),
            'color' => config('app.product_variant') ? null : $product->color,
            'size' => config('app.product_variant') ? null : $product->size,
            'department' => $this->getDepartment($product),
            'style' => config('app.product_variant') ? null : $product->style,
            'sub_department' => $product->getSubDepartmentId() ? SubDepartments::getFormattedCaseName(
                $product->getSubDepartmentId()
            ) : 'N/A',
            'categories' => $this->getCategories($product),
            'tags' => $this->getTags($product),
            'article_number' => $this->getArticleNumber($product),
            'ean' => $product->ean,
            'custom_sku' => $product->custom_sku,
            'manufacturer_sku' => $product->manufacturer_sku,
            'product_type' => ProductTypes::getFormattedCaseName($product->type_id),
            'retail_price' => (float) $product->retail_price,
            'franchise_price_1' => (float) $product->franchise_price_1,
            'franchise_price_2' => (float) $product->franchise_price_2,
            'franchise_price_3' => (float) $product->franchise_price_3,
            'wholesale_price' => (float) $product->wholesale_price,
            'company_or_tender_price' => (float) $product->company_or_tender_price,
            'branch_price' => (float) $product->branch_price,
            'minimum_price' => (float) $product->minimum_price,
            'original_capital_price' => (float) $product->original_capital_price,
            'capital_price' => (float) $product->capital_price,
            'staff_price' => (float) $product->staff_price,
            'purchase_cost' => (float) $product->purchase_cost,
            'upc' => $product->upc,
            'is_temporarily_unavailable' => $product->is_temporarily_unavailable,
            'has_batch' => $product->has_batch,
            'is_non_inventory' => $product->is_non_inventory,
            'is_non_selling_item' => $product->is_non_selling_item,
        ];

        return array_diff_key($productDetails, array_flip($optionalColumns));
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

    private function getTags(Product $product): ?array
    {
        $tags = config('app.product_variant')
        ? $product->masterProduct?->tags
            : $product->tags;

        return $tags?->map(fn ($tag): array => [
            'id' => $tag->id,
            'name' => $tag->name,
        ])->toArray();
    }

    private function getDepartment(Product $product): ?string
    {
        return config(
            'app.product_variant'
        ) ? $product->masterProduct?->department?->name : $product->department?->name;
    }

    private function getBrand(Product $product): ?string
    {
        return config('app.product_variant') ? $product->masterProduct?->brand?->name : $product->brand?->name;
    }

    private function getArticleNumber(Product $product): ?string
    {
        return config('app.product_variant') ? $product->masterProduct?->article_number : $product->article_number;
    }
}
