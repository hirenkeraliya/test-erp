<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WarehouseManager;

use App\CommonFunctions;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Product\DataObjects\WarehouseManagerApiProductData;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\ApplicationProductListResource;
use App\Domains\Product\Resources\ProductDetailsForApplicationResource;
use App\Http\Controllers\Controller;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function getProducts(Request $request, WarehouseManagerApiProductData $warehouseManagerApiProductData): array
    {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $filterData = [
            'search_text' => $warehouseManagerApiProductData->search_text,
            'sort_by' => $warehouseManagerApiProductData->sort_by,
            'sort_direction' => $warehouseManagerApiProductData->sort_direction,
            'per_page' => $warehouseManagerApiProductData->per_page,
            'location_id' => $warehouseManagerApiProductData->warehouse_id ?? $warehouseManagerApiProductData->location_id,
            'stock_product' => $warehouseManagerApiProductData->stock_product,
        ];

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($warehouseManager->employee_id);

        $productQueries = resolve(ProductQueries::class);
        $products = $productQueries->getProductsForApplication($filterData, $companyId);

        return [
            'data' => ApplicationProductListResource::collection($products->getCollection()),
            'total_records' => $products->total(),
            'last_page' => $products->lastPage(),
            'current_page' => $products->currentPage(),
            'per_page' => $products->perPage(),
        ];
    }

    public function getProductDetails(Request $request, int $productId, int $locationId): array
    {
        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($warehouseManager->employee_id);

        $productQueries = resolve(ProductQueries::class);
        $product = $productQueries->getProductDetailsForApplication($productId, $companyId);

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->getInventoriesByProductIds($locationId, [$productId]);

        return [
            'product_details' => (new ProductDetailsForApplicationResource($product)),
            'stock' => CommonFunctions::truncateDecimal((float) $inventory->first()?->stock),
        ];
    }
}
