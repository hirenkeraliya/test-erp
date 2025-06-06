<?php

declare(strict_types=1);

namespace App\Domains\Product\Resources;

use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\SubDepartment\Enums\SubDepartments;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Department;
use App\Models\MasterProduct;
use App\Models\Season;
use App\Models\Size;
use App\Models\Style;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class MatchActiveProductsListResource extends JsonResource
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

        if (config('app.product_variant')) {
            /** @var MasterProduct $masterProduct */
            $masterProduct = $product->masterProduct;

            /** @var Collection $categories */
            $categories = $masterProduct->categories;

            /** @var ?UnitOfMeasure $unitOfMeasure */
            $unitOfMeasure = $masterProduct->unitOfMeasure;

            /** @var ?Department $department */
            $department = $masterProduct->department;

            /** @var ?Brand $brand */
            $brand = $masterProduct->brand;

            $color = $product->productVariantValues
                ->filter(fn ($variantValue): bool => strcasecmp($variantValue->attribute->name, 'color') === 0)
                ->first();

            $size = $product->productVariantValues
                ->filter(fn ($variantValue): bool => strcasecmp($variantValue->attribute->name, 'size') === 0)
                ->first();

            $style = $product->productVariantValues
                ->filter(fn ($variantValue): bool => strcasecmp($variantValue->attribute->name, 'style') === 0)
                ->first();

            $season = $product->productVariantValues
                ->filter(fn ($variantValue): bool => strcasecmp($variantValue->attribute->name, 'season') === 0)
                ->first();

            return [
                'name' => $product->name,
                'categories' => $categories,
                'brand' => $brand->name ?? 'N/A',
                'season' => $season?->getValue() ?? 'N/A',
                'color' => $color?->getValue() ?? 'N/A',
                'size' => $size?->getValue() ?? 'N/A',
                'department' => $department->name ?? 'N/A',
                'sub_department' => 'N/A',
                'style' => $style?->getValue() ?? 'N/A',
                'article_number' => $masterProduct->article_number,
                'retail_price' => (float) $product->retail_price,
                'type_id' => $product->type_id ? ProductTypes::getCaseNameByValue($product->type_id) : 'N/A',
                'unit_of_measure_id' => $unitOfMeasure->name ?? 'N/A',
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
                'staff_price' => $product->staff_price,
                'purchase_cost' => $product->purchase_cost,
                'is_temporarily_unavailable' => $product->is_temporarily_unavailable,
                'has_batch' => $masterProduct->has_batch,
                'is_non_inventory' => $masterProduct->is_non_inventory,
                'is_non_selling_item' => $masterProduct->is_non_selling_item,
                'is_available_in_pos' => $product->is_available_in_pos,
                'is_available_in_ecommerce' => $product->is_available_in_ecommerce,
                'online_price' => $product->online_price,
                'status' => Statuses::getFormattedCaseName($product->status),
            ];
        }

        /** @var ?Size $size */
        $size = $product->size;

        /** @var ?Brand $brand */
        $brand = $product->brand;

        /** @var ?Color $color */
        $color = $product->color;

        /** @var ?UnitOfMeasure $unitOfMeasure */
        $unitOfMeasure = $product->unitOfMeasure;

        /** @var ?Season $season */
        $season = $product->season;

        /** @var ?Style $style */
        $style = $product->style;

        /** @var ?Department $department */
        $department = $product->department;

        /** @var Collection $categories */
        $categories = $product->categories;

        return [
            'name' => $product->name,
            'categories' => $categories,
            'brand' => $brand->name ?? 'N/A',
            'season' => $season->name ?? 'N/A',
            'color' => $color->name ?? 'N/A',
            'size' => $size->name ?? 'N/A',
            'department' => $department->name ?? 'N/A',
            'sub_department' => $product->sub_department_id ? SubDepartments::getCaseNameByValue(
                $product->sub_department_id
            ) : 'N/A',
            'style' => $style->name ?? 'N/A',
            'article_number' => $product->article_number,
            'retail_price' => (float) $product->retail_price,
            'type_id' => $product->type_id ? ProductTypes::getCaseNameByValue($product->type_id) : 'N/A',
            'unit_of_measure_id' => $unitOfMeasure->name ?? 'N/A',
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
            'staff_price' => $product->staff_price,
            'purchase_cost' => $product->purchase_cost,
            'is_temporarily_unavailable' => $product->is_temporarily_unavailable,
            'has_batch' => $product->has_batch,
            'is_non_inventory' => $product->is_non_inventory,
            'is_non_selling_item' => $product->is_non_selling_item,
            'is_available_in_pos' => $product->is_available_in_pos,
            'is_available_in_ecommerce' => $product->is_available_in_ecommerce,
            'online_price' => $product->online_price,
            'status' => Statuses::getFormattedCaseName($product->status),
        ];
    }
}
