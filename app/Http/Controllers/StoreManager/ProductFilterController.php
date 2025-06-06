<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Product\ProductQueries;
use App\Http\Controllers\Controller;
use App\Models\BoxProduct;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Inventory;
use App\Models\PackageType;
use App\Models\Product;
use App\Models\Size;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProductFilterController extends Controller
{
    public function __construct(
        protected ProductQueries $productQueries,
    ) {
    }

    /**
     * @return array<string, Collection>
     */
    public function getFilteredProducts(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'number_of_records' => $request->input('number_of_records'),
        ];

        return [
            'products' => $this->productQueries->getActiveFilteredProducts(
                $filterData,
                session('store_manager_selected_location_company_id')
            ),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getFilteredProductsList(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'category_id' => $request->input('category_id'),
            'brand_id' => $request->input('brand_id'),
        ];

        $products = $this->productQueries->getActiveProductsFilteredByNameBrandAndCategory(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return [
            'products' => $products->toArray(),
        ];
    }

    /**
     * @return array<string, Product>
     */
    public function getProduct(int $productId): array
    {
        return [
            'product' => $this->productQueries->getActiveProductWithBasicColumnsById(
                $productId,
                session('store_manager_selected_location_company_id')
            ),
        ];
    }

    /**
     * @return array<string, Collection>
     */
    public function getFilteredInventoryProducts(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'number_of_records' => $request->input('number_of_records'),
        ];

        return [
            'products' => $this->productQueries->getActiveFilteredInventoryProducts(
                $filterData,
                session('store_manager_selected_location_company_id')
            ),
        ];
    }

    /**
     * @return array<string, mixed[]>
     */
    public function getFilteredInventoryProductsList(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
            'category_id' => $request->input('category_id'),
            'brand_id' => $request->input('brand_id'),
            'has_inventory' => $request->input('has_inventory'),
            'location_id' => $request->input('location_id'),
        ];

        $products = $this->productQueries->getActiveInventoryProductsFilteredByNameBrandAndCategory(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return [
            'products' => $products->toArray(),
        ];
    }

    public function getInventoryProductsList(Request $request): array
    {
        $filterData = [
            'search_text' => $request->input('search_text'),
        ];

        $products = $this->productQueries->getActiveInventoryProductsFilteredByName(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        $preparedProducts = collect([]);

        $products->each(function ($product) use (&$preparedProducts): void {
            /** @var Collection $productBoxes */
            $productBoxes = $product->boxes;

            $preparedProducts->push($this->getPreparedData($product, null));

            foreach ($productBoxes as $box) {
                /** @var BoxProduct $boxProduct */
                $boxProduct = $box;

                if (null !== $box->units) {
                    $preparedProducts->push($this->getPreparedData($product, $boxProduct));
                }
            }
        });

        return [
            'products' => $preparedProducts,
        ];
    }

    private function getPreparedData(Product $product, ?BoxProduct $boxProduct): array
    {
        /** @var ?Color $color */
        $color = $product->color;

        /** @var ?Size $size */
        $size = $product->size;

        /** @var Brand $brand */
        $brand = $product->brand;

        /** @var ?UnitOfMeasure $unitOfMeasure */
        $unitOfMeasure = $product->unitOfMeasure;

        if ($boxProduct instanceof BoxProduct) {
            /** @var PackageType $packageType */
            $packageType = $boxProduct->packageType;
        }

        /** @var ?Inventory $inventory */
        $inventory = $product->inventory;

        $inventoryUnits = null;

        if ($inventory instanceof Inventory) {
            /** @var Collection $inventoryUnits */
            $inventoryUnits = $inventory->inventoryUnits;
        }

        return [
            'id' => $product->id,
            'box_product_id' => $boxProduct?->id,
            'name' => $product->name,
            'article_number' => $product->article_number,
            'upc' => $product->upc,
            'price' => $boxProduct instanceof BoxProduct ? (float) $boxProduct->retail_price : (float) $product->retail_price,
            'brand' => $brand->name,
            'color' => $color->name ?? 'N/A',
            'size' => $size->name ?? 'N/A',
            'type_id' => $product->type_id,
            'unit_of_measure' => $unitOfMeasure instanceof UnitOfMeasure ? [
                'id' => $unitOfMeasure->getKey(),
                'name' => $unitOfMeasure->name,
                'allow_decimal_qty' => $unitOfMeasure->allow_decimal_qty,
            ] : [],
            'package_type_id' => $boxProduct instanceof BoxProduct ? $boxProduct->package_type_id : [],
            'package_type_name' => $boxProduct instanceof BoxProduct ? $packageType->name : '',
            'units' => $boxProduct instanceof BoxProduct ? $boxProduct->units : null,
            'staff_price' => $boxProduct instanceof BoxProduct ? $boxProduct->staff_price : null,
            'minimum_price' => $product->minimum_price,
            'wholesale_price' => $product->wholesale_price,
            'has_batch' => $product->has_batch,
            'batch_details' => $inventoryUnits instanceof Collection ? $this->getBatchNumbers($inventoryUnits) : [],
        ];
    }

    private function getBatchNumbers(Collection $inventoryUnits): Collection
    {
        return $inventoryUnits->groupBy('batch.number')
            ->map(fn ($inventoryUnit): array => [
                'batch_number' => $inventoryUnit[0]->batch?->number,
                'batch_expiry_date' => $inventoryUnit[0]->batch?->expiry_date,
            ])
            ->values();
    }
}
