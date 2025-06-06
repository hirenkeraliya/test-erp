<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\StoreManager;

use App\CommonFunctions;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Product\DataObjects\StoreManagerApiProductData;
use App\Domains\Product\DataObjects\StoreManagerApiUpdateProductPriceData;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\ApplicationProductListResource;
use App\Domains\Product\Resources\ProductDetailsForApplicationResource;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductController extends Controller
{
    public function getProducts(Request $request, StoreManagerApiProductData $storeManagerApiProductData): array
    {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $filterData = [
            'search_text' => $storeManagerApiProductData->search_text,
            'sort_by' => $storeManagerApiProductData->sort_by,
            'sort_direction' => $storeManagerApiProductData->sort_direction,
            'per_page' => $storeManagerApiProductData->per_page,
            'location_id' => $storeManagerApiProductData->store_id ?? $storeManagerApiProductData->location_id,
            'stock_product' => $storeManagerApiProductData->stock_product,
        ];

        $employeeQueries = resolve(EmployeeQueries::class);
        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

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
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $productQueries = resolve(ProductQueries::class);
        $product = $productQueries->getProductDetailsForApplication($productId, $companyId);

        $inventoryQueries = resolve(InventoryQueries::class);
        $inventory = $inventoryQueries->getInventoriesByProductIds($locationId, [$productId]);

        return [
            'product_details' => (new ProductDetailsForApplicationResource($product)),
            'stock' => $inventory->isNotEmpty() ? CommonFunctions::truncateDecimal(
                (float) $inventory->first()->stock
            ) : 0,
        ];
    }

    public function updateProductPrices(
        Request $request,
        int $productId,
        StoreManagerApiUpdateProductPriceData $priceData
    ): void {
        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);

        /** @var int $companyId */
        $companyId = $employeeQueries->getEmployeeCompanyId($storeManager->employee_id);

        $productQueries = resolve(ProductQueries::class);

        DB::beginTransaction();

        try {
            $priceData = $priceData->toArray();
            $productQueries->updateProductPrices($productId, $companyId, $priceData, $storeManager);

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('Store Manager Update Product Prices', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);
            DB::rollBack();

            abort(412, 'An error occurred. Please try again.');
        }
    }
}
