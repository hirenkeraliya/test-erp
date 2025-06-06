<?php

declare(strict_types=1);

namespace App\Domains\Inventory\DataPreparer;

use App\CommonFunctions;
use App\Domains\Common\Services\ExportService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Product\Services\ProductService;
use App\Models\Brand;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExportDataPreparer
{
    public static function prepareInventoryExportData(
        Collection $inventories,
        Collection $filteredColumns,
    ): Collection {
        $productService = resolve(ProductService::class);

        return $inventories->map(function (Inventory $inventory) use ($filteredColumns, $productService): array {
            /** @var Product $product */
            $product = $inventory->product;

            /** @var Collection $categories */
            $categories = config('app.product_variant') ? $product?->masterProduct?->categories ?? collect([
            ]) : $product->categories;

            /** @var Brand ?$brand */
            $brand = config('app.product_variant') ? $product->masterProduct?->brand : $product->brand;

            /** @var Collection $tags */
            $tags = config('app.product_variant') ? $product?->masterProduct?->tags ?? collect([]) : $product->tags;

            /** @var Location $location */
            $location = $inventory->location;

            $productPrice = $product->retail_price;
            /* @phpstan-ignore-next-line */
            $inventoryStock = (float) $inventory->available_stock;
            $reservedStock = (float) $inventory->reserved_stock;
            $transitStock = (float) $inventory['transit_stocks_sum_quantity'];
            $currentStock = $inventoryStock + $reservedStock;

            [$category, $parentSubcategory, $subSubcategories] = static::getProductCategories($categories);

            /** @var Carbon $createdAt */
            $createdAt = $inventory->created_at;

            /** @var Carbon $updatedAt */
            $updatedAt = $inventory->updated_at;

            $return = [
                'item_name' => $product->name,
                'article_number' => config(
                    'app.product_variant'
                ) ? $product?->masterProduct?->article_number ?? 'N/A' : $product->article_number,
                'color' => config('app.product_variant') ? null : $product?->color?->name ?? null,
                'size' => config('app.product_variant') ? null : $product?->size?->name ?? null,
                'attributes' => $productService->getAttributesForPrint($product),
                'categories' => $category ? $category->name : 'N/A',
                'brand' => $brand->name ?? 'N/A',
                'upc' => $product->upc,
                'item_code' => $product->manufacturer_sku ?? 'N/A',
                'location' => LocationTypes::getFormattedCaseName($location->type_id) . ' : ' . $location->name,
                'unit_price' => CommonFunctions::currencyFormat((float) $productPrice),
                'current_stock' => CommonFunctions::numberFormatString($currentStock),
                'reserved_stock' => CommonFunctions::numberFormatString($reservedStock),
                'transit_stock' => CommonFunctions::numberFormatString($transitStock),
                'available_stock' => CommonFunctions::numberFormatString($inventoryStock),
                'total_value' => CommonFunctions::currencyFormat($productPrice * $currentStock),
                'created_at' => $createdAt->format('d-m-Y h:i:s A'),
                'last_updated_at' => $updatedAt->format('d-m-Y h:i:s A'),
                'parent_subcategory' => $parentSubcategory ? $parentSubcategory->name : 'N/A',
                'sub_subcategories' => $subSubcategories->isNotEmpty() ? $subSubcategories->implode(
                    'name',
                    ' > '
                ) : 'N/A',
                'tags' => implode(',', static::getProductTags($tags)),
            ];

            $exportService = resolve(ExportService::class);

            return $exportService->exportData($return, $filteredColumns);
        });
    }

    /**
     * @return mixed[]
     */
    public static function getProductCategories(Collection $categories): array
    {
        $category = $categories->first();
        $parentSubcategory = $categories->firstWhere('pivot.sort_order', 1);
        $subSubcategories = $categories->where('pivot.sort_order', '>=', 2);

        return [$category, $parentSubcategory, $subSubcategories];
    }

    /**
     * @return string[]
     */
    public static function getProductTags(Collection $tags): array
    {
        return $tags->map(function ($tag): string {
            /** @var Tag $productTag */
            $productTag = $tag;

            return $productTag->getName();
        })->toArray();
    }
}
