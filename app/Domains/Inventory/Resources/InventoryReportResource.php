<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Resources;

use App\CommonFunctions;
use App\Domains\Product\Services\ProductService;
use App\Models\Brand;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class InventoryReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $productService = resolve(ProductService::class);

        /** @var Inventory $inventory */
        $inventory = $this;

        /** @var Product $product */
        $product = $inventory->product;

        /** @var Collection $categories */
        $categories = config('app.product_variant') ? $product?->masterProduct?->categories ?? collect([
        ]) : $product->categories;

        /** @var Brand ?$brand */
        $brand = config('app.product_variant') ? $product->masterProduct?->brand : $product->brand;

        $productPrice = $product->retail_price;

        /** @var Location $location */
        $location = $inventory->location;

        $currentRouteName = Route::currentRouteName();

        $prefix = 'admin';
        if (null !== $currentRouteName && str_starts_with($currentRouteName, 'store_manager.')) {
            $prefix = 'store_manager';
        }

        if (null !== $currentRouteName && str_starts_with($currentRouteName, 'warehouse_manager.')) {
            $prefix = 'warehouse_manager';
        }

        $reservedInventoryUrl = route(
            $prefix . '.reserved_inventory_reports.index',
            [
                'product_id' => $inventory->product_id,
                'location_id' => $inventory->location_id,
                'type_id' => $location->type_id,
            ]
        );

        $transitInventoryUrl = route(
            $prefix . '.transit_inventory_reports.index',
            [
                'product_id' => $inventory->product_id,
                'location_id' => $inventory->location_id,
                'type_id' => $location->type_id,
            ]
        );

        $transitStock = (float) $inventory['transit_stocks_sum_quantity'];

        /** @phpstan-ignore-next-line */
        $currentStock = $inventory->current_stock;

        /** @var Carbon $createdAt */
        $createdAt = $inventory->created_at;

        /** @var Carbon $updatedAt */
        $updatedAt = $inventory->updated_at;

        return [
            'item_name' => $product->name,
            'article_number' => config(
                'app.product_variant'
            ) ? $product?->masterProduct->article_number ?? 'N/A' : $product->article_number ?? 'N/A',
            'categories' => $categories->map(fn ($category): array => [
                'id' => $category->id,
                'name' => $category->name,
            ]),
            'brand' => $brand->name ?? 'N/A',
            'upc' => $product->upc,
            'color' => config('app.product_variant') ? null : $product?->color?->name ?? null,
            'size' => config('app.product_variant') ? null : $product?->size?->name ?? null,
            'attributes' => $productService->getAttributesWithNameAndValueKey($product),
            'item_code' => $product->manufacturer_sku ?? 'N/A',
            'location' => $inventory->location?->name,
            'unit_price' => $productPrice,
            /* @phpstan-ignore-next-line */
            'available_stock' => $inventory->available_stock,
            'reserved_stock' => $inventory->reserved_stock,
            'transit_stock' => CommonFunctions::numberFormatString($transitStock),
            'current_stock' => $currentStock,
            'total_value' => CommonFunctions::numberFormat($productPrice * $currentStock),
            'reserved_inventory_url' => $reservedInventoryUrl,
            'transit_inventory_url' => $transitInventoryUrl,
            'created_at' => $createdAt->format('d-m-Y h:i:s A'),
            'last_updated_at' => $updatedAt->format('d-m-Y h:i:s A'),
        ];
    }
}
