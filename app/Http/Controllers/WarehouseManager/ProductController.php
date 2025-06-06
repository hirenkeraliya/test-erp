<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\ExportRecord\Enums\ExportRecordTypes;
use App\Domains\ExportRecord\ExportRecordQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\DataObjects\ProductArticleData;
use App\Domains\Product\DataObjects\ProductWithLocationStockData;
use App\Domains\Product\Enums\ProductBatches;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\Enums\ProductSyncTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\Exports\BoxProductExport;
use App\Domains\Product\Exports\BulkUpdateProductExport;
use App\Domains\Product\Exports\LoyaltyPointProductExport;
use App\Domains\Product\Exports\ProductExport;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\ProductMatchingUpcInventoryResource;
use App\Domains\Product\Resources\ProductMatchingUpcResource;
use App\Domains\Product\Resources\WarehouseManagerProductListResource;
use App\Domains\Product\Services\ProductService;
use App\Domains\ProductLoyaltyPoint\ProductLoyaltyPointQueries;
use App\Http\Controllers\Controller;
use App\Models\WarehouseManager;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    public function __construct(
        protected ProductQueries $productQueries
    ) {
    }

    public function index(): Response
    {
        $exportRecordQueries = resolve(ExportRecordQueries::class);
        $exportRecordCount = $exportRecordQueries->exportRecordCountForProductHistory(
            session('warehouse_manager_selected_location_company_id')
        );

        if (config('app.product_variant')) {
            $attributeQueries = resolve(AttributeQueries::class);
            $attributes = $attributeQueries->getAttributes(session('warehouse_manager_selected_location_company_id'));
        }

        return Inertia::render('products/Index', [
            'productStatuses' => ProductStatuses::getList(),
            'productBatches' => ProductBatches::getList(),
            'allStatus' => ProductStatuses::ACTIVE,
            'allBatch' => ProductBatches::ALL,
            'productTypes' => ProductTypes::getList(),
            'exportPermission' => PermissionList::getExportPermissionName('product'),
            'exportType' => ExportRecordTypes::PRODUCTS->value,
            'exportRecordCount' => $exportRecordCount,
            'productSyncTypes' => ProductSyncTypes::getList(),
            'allProductSyncType' => ProductSyncTypes::ALL_PRODUCT->value,
            'attributes' => $attributes ?? collect([]),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchProducts(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'status' => $request->get('status'),
            'batch' => $request->get('batch'),
            'date_range' => $request->get('date_range'),
            'product_type_id' => $request->get('product_type_id'),
            'category_ids' => $request->get('category_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'color_ids' => $request->get('color_ids'),
            'size_ids' => $request->get('size_ids'),
            'department_ids' => $request->get('department_ids'),
            'article_numbers' => $request->get('article_numbers'),
            'tag_ids' => $request->get('tag_ids'),
            'style_ids' => $request->get('style_ids'),
            'product_collection_ids' => $request->get('product_collection_ids'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        $lengthAwarePaginator = $this->productQueries->listQueryForWarehouseManager(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => WarehouseManagerProductListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function getMatchingUpcProducts(Request $request): array
    {
        $validatedData = $request->validate([
            'import_products' => ['required', 'array'],
            'import_products.*' => ['required'],
        ]);

        $products = $this->productQueries->getActiveProductsByUpc(
            $validatedData['import_products'],
            session('warehouse_manager_selected_location_company_id')
        );

        return [
            'products' => ProductMatchingUpcResource::collection($products),
            'products_count' => $products->count(),
        ];
    }

    public function getActiveInventoryProductsByUpcs(Request $request): array
    {
        $validatedData = $request->validate([
            'import_products' => ['required', 'array'],
            'import_products.*' => ['required'],
        ]);

        $products = $this->productQueries->getActiveInventoryProductsByUpcs(
            $validatedData['import_products'],
            session('warehouse_manager_selected_location_company_id')
        );

        return [
            'products' => ProductMatchingUpcInventoryResource::collection($products),
            'products_count' => $products->count(),
        ];
    }

    public function exportProducts(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'status' => $request->get('status'),
            'batch' => $request->get('batch'),
            'date_range' => $request->get('date_range'),
            'product_type_id' => $request->get('product_type_id'),
            'category_ids' => $request->get('category_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'color_ids' => $request->get('color_ids'),
            'size_ids' => $request->get('size_ids'),
            'department_ids' => $request->get('department_ids'),
            'article_numbers' => $request->get('article_numbers'),
            'tag_ids' => $request->get('tag_ids'),
            'style_ids' => $request->get('style_ids'),
            'product_collection_ids' => $request->get('product_collection_ids'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        $products = $this->productQueries->getProductsWithRelationsForExport(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        return Excel::download(new ProductExport($products, $filteredColumns), $filename);
    }

    /**
     * @return array<string, Collection<int|string, array{id: mixed, has_batch: mixed, color: mixed, size: mixed, stock: null, combination: string}>>|array<string, mixed[]>
     */
    public function searchByArticleNumber(ProductArticleData $productArticleData): array
    {
        DB::beginTransaction();

        try {
            $productService = resolve(ProductService::class);

            $result = $productService->getActiveInventoryProductDetailsForArticleNumber(
                $productArticleData,
                session('warehouse_manager_selected_location_company_id')
            );
            DB::commit();

            return $result;
        } catch (Exception $exception) {
            DB::rollBack();

            Log::error('Warehouse Manager Search By Article Number', [
                'error_message' => $exception->getMessage(),
                'error_code' => 'Error code: ' . $exception->getCode(),
                'file' => 'File: ' . $exception->getFile(),
                'line' => 'Line: ' . $exception->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($exception->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$exception],
            ]);

            abort(412, 'An error occurred. Please try again.');
        }
    }

    /**
     * @return array<string, LazyCollection>
     */
    public function getFilteredArticleNumber(Request $request): array
    {
        return [
            'articleNumbers' => $this->productQueries->getFilteredArticleNumberByCompanyId(
                $request->input('search_text'),
                session('warehouse_manager_selected_location_company_id')
            ),
        ];
    }

    public function printProducts(Request $request): string
    {
        $productsData = [];
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'status' => $request->get('status'),
            'batch' => $request->get('batch'),
            'date_range' => $request->get('date_range'),
            'product_type_id' => $request->get('product_type_id'),
            'category_ids' => $request->get('category_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'color_ids' => $request->get('color_ids'),
            'size_ids' => $request->get('size_ids'),
            'department_ids' => $request->get('department_ids'),
            'article_numbers' => $request->get('article_numbers'),
            'tag_ids' => $request->get('tag_ids'),
            'style_ids' => $request->get('style_ids'),
            'product_collection_ids' => $request->get('product_collection_ids'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        $companyId = session('warehouse_manager_selected_location_company_id');

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $productService = resolve(ProductService::class);
        $filterColumns = $productService->filterColumnsForPdf($filteredColumns);

        $products = $this->productQueries->getProductsWithRelationsForPrint($filterData, $companyId);
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getByIdWithPromoterCommissionDetails($companyId);

        $productService = resolve(ProductService::class);
        $productsData['details'] = $productService->productDataPrint($products, $filteredColumns);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        return view('prints.product_details', [
            'productDetails' => $productsData['details'],
            'company' => $company,
            'columns' => $filterColumns,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'currencySymbol' => $currency->getSymbol(),
        ])->render();
    }

    public function searchProductsByOnlyArticleNumber(Request $request): array
    {
        $request->validate([
            'article_number' => ['required', 'string'],
        ]);

        $filterData = [
            'article_number' => $request->get('article_number'),
        ];

        $productService = resolve(ProductService::class);

        return $productService->getProductDetailsByArticleNumber(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );
    }

    public function searchByArticleNumberWithStock(ProductWithLocationStockData $productWithLocationStockData): array
    {
        $productService = resolve(ProductService::class);

        return $productService->getProductArticleNumberWithLocationStock(
            $productWithLocationStockData,
            session('warehouse_manager_selected_location_company_id')
        );
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function getActiveInventoryProductsByUpcsWithDerivatives(Request $request): array
    {
        $validatedData = $request->validate([
            'import_products' => ['required', 'array'],
            'import_products.*' => ['required'],
        ]);

        $products = $this->productQueries->getActiveInventoryProductsByUpcsWithDerivatives(
            $validatedData['import_products'],
            session('warehouse_manager_selected_location_company_id')
        );

        return [
            'products' => $products,
            'products_count' => $products->count(),
        ];
    }

    public function checkProductExportLimit(Request $request): array
    {
        $filterData = $this->getExportProductsFilterData($request);

        $companyId = session('warehouse_manager_selected_location_company_id');

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $allPermissionLists = $warehouseManager->roles->pluck('permissions')->collapse()->pluck('name')->toArray();
        $filterData['all_permission_lists'] = $allPermissionLists;

        $productService = resolve(ProductService::class);

        return $productService->exportProductWithJob($warehouseManager, $filterData, $companyId, $filteredColumns);
    }

    public function exportLoyaltyPointProducts(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getExportProductsFilterData($request);
        $productLoyaltyPointQueries = resolve(ProductLoyaltyPointQueries::class);

        $loyaltyPointProducts = $productLoyaltyPointQueries->getLoyaltyPointProducts(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        return Excel::download(new LoyaltyPointProductExport($loyaltyPointProducts), $filename);
    }

    public function exportBoxProducts(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getExportProductsFilterData($request);
        $boxProductQueries = resolve(BoxProductQueries::class);

        $boxProducts = $boxProductQueries->getBoxProducts(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        return Excel::download(new BoxProductExport($boxProducts), $filename);
    }

    public function checkProductLoyaltyPointExportLimit(Request $request): array
    {
        $filterData = $this->getExportProductsFilterData($request);

        $companyId = session('warehouse_manager_selected_location_company_id');

        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $allPermissionLists = $warehouseManager->roles->pluck('permissions')->collapse()->pluck('name')->toArray();
        $filterData['all_permission_lists'] = $allPermissionLists;

        $productService = resolve(ProductService::class);

        return $productService->exportProductLoyaltyPointWithJob($warehouseManager, $filterData, $companyId);
    }

    public function checkBoxProductExportLimit(Request $request): array
    {
        $filterData = $this->getExportProductsFilterData($request);

        $companyId = session('warehouse_manager_selected_location_company_id');

        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $allPermissionLists = $warehouseManager->roles->pluck('permissions')->collapse()->pluck('name')->toArray();
        $filterData['all_permission_lists'] = $allPermissionLists;

        $productService = resolve(ProductService::class);

        return $productService->exportBoxProductWithJob($warehouseManager, $filterData, $companyId);
    }

    public function exportProductsForImportBulkUpdate(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'status' => $request->get('status'),
            'batch' => $request->get('batch'),
            'date_range' => $request->get('date_range'),
            'product_type_id' => $request->get('product_type_id'),
            'category_ids' => $request->get('category_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'color_ids' => $request->get('color_ids'),
            'size_ids' => $request->get('size_ids'),
            'department_ids' => $request->get('department_ids'),
            'article_numbers' => $request->get('article_numbers'),
            'tag_ids' => $request->get('tag_ids'),
            'style_ids' => $request->get('style_ids'),
            'product_collection_ids' => $request->get('product_collection_ids'),
        ];

        $products = $this->productQueries->getProductsWithRelationsForExport(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        return Excel::download(new BulkUpdateProductExport($products), $filename);
    }

    public function checkProductExportLimitForImportBulkUpdate(Request $request): array
    {
        $filterData = $this->getExportProductsFilterData($request);

        $companyId = session('warehouse_manager_selected_location_company_id');

        /** @var WarehouseManager $warehouseManager */
        $warehouseManager = $request->user();

        $allPermissionLists = $warehouseManager->roles->pluck('permissions')->collapse()->pluck('name')->toArray();
        $filterData['all_permission_lists'] = $allPermissionLists;

        $productService = resolve(ProductService::class);

        return $productService->exportProductWithJobForImportBulkUpdate($warehouseManager, $filterData, $companyId);
    }

    private function getExportProductsFilterData(Request $request): array
    {
        return [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'status' => $request->get('status'),
            'batch' => $request->get('batch'),
            'date_range' => $request->get('date_range'),
            'product_type_id' => $request->get('product_type_id'),
            'category_ids' => $request->get('category_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'color_ids' => $request->get('color_ids'),
            'size_ids' => $request->get('size_ids'),
            'department_ids' => $request->get('department_ids'),
            'article_numbers' => $request->get('article_numbers'),
            'tag_ids' => $request->get('tag_ids'),
            'style_ids' => $request->get('style_ids'),
            'product_collection_ids' => $request->get('product_collection_ids'),
            'attributes' => $request->get('attributes') ?? [],
        ];
    }
}
