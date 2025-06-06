<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Inventory\Enums\Types;
use App\Domains\Inventory\Exports\InventoryExport;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Resources\InventoryReportResource;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\Enums\SellingTypes;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\Region\RegionQueries;
use App\Domains\StockTransfer\Services\StockTransferService;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InventoryReportController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = session('store_manager_selected_location_company_id');

        $stockTransferService = resolve(StockTransferService::class);
        [$stores, $warehouses] = $stockTransferService->getStoresAndWarehouses($companyId);

        $regionQueries = resolve(RegionQueries::class);
        $regions = $regionQueries->getRegionByCompanyId($companyId);

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollections = $productCollectionQueries->getProductCollections($companyId);

        if (config('app.product_variant')) {
            $attributeQueries = resolve(AttributeQueries::class);
            $attributes = $attributeQueries->getAttributes($companyId);
        }

        $locationId = (int) $request->get('location_id');
        $locationQueries = resolve(LocationQueries::class);
        $selectedLocations = null;
        if (0 !== $locationId) {
            $location = $locationQueries->getById($locationId, $companyId, (int) $request->get('location_type'));

            $selectedLocations = [
                'code' => $location->code,
                'id' => $location->id,
                'name' => $location->name,
            ];
        }

        return Inertia::render('reports/Inventory/Index', [
            'stores' => $stores,
            'warehouses' => $warehouses,
            'regions' => $regions,
            'productCollections' => $productCollections,
            'stockTypes' => Types::formattedForSelection(),
            'productStatuses' => ProductStatuses::getList(),
            'dashboardFilterData' => [
                'stock_type' => (int) $request->get('stock_type'),
                'status' => $request->get('status'),
                'location_id' => $locationId > 0 ? [$locationId] : null,
                'location_type' => (int) $request->get('location_type'),
                'selectedLocations' => $selectedLocations,
                'product_id' => (int) $request->get('product_id'),
                'selling_type' => (int) $request->get('selling_type'),
            ],
            'exportPermission' => PermissionList::getExportPermissionName('inventory'),
            'sellingTypes' => SellingTypes::formattedForSelection(),
            'helpCenterMessages' => 'Inventory reports display product current stock, reserved stock, available stock, inventory value and offering advanced filters, search options, and seamless export capabilities.',
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'locationTypes' => LocationTypes::getList(),
            'attributes' => $attributes ?? collect([]),
        ]);
    }

    public function fetchInventories(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'product_id' => $request->get('product_id'),
            'category_id' => $request->get('category_id'),
            'brand_id' => $request->get('brand_id'),
            'color_id' => $request->get('color_id'),
            'size_id' => $request->get('size_id'),
            'article_numbers' => $request->get('article_numbers'),
            'department_ids' => $request->get('department_ids'),
            'tag_ids' => $request->get('tag_ids'),
            'style_ids' => $request->get('style_ids'),
            'location_ids' => $request->get('location_ids'),
            'stock_type' => $request->get('stock_type'),
            'selling_type' => $request->get('selling_type'),
            'region_ids' => $request->get('region_ids'),
            'status' => $request->get('status'),
            'product_collection_id' => $request->get('product_collection_id'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        $inventoryQueries = resolve(InventoryQueries::class);

        $companyId = session('store_manager_selected_location_company_id');
        $lengthAwarePaginator = $inventoryQueries->inventoryReportsList($filterData, $companyId);

        $totalCount = $inventoryQueries->getFilteredTotalsForInventoryReport($filterData, $companyId);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => InventoryReportResource::collection($lengthAwarePaginator),
            'total_available_stock' => $totalCount['total_available_stock'],
            'total_current_stock' => $totalCount['total_current_stock'],
            'total_reserved_stock' => $totalCount['total_reserved_stock'],
            'total_transit_stock' => $totalCount['total_transit_stock'],
        ];
    }

    public function exportInventories(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getExportInventoriesFilterData($request);

        $inventoryQueries = resolve(InventoryQueries::class);

        $inventories = $inventoryQueries->inventoryListsForExport(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        return Excel::download(new InventoryExport($inventories, $filteredColumns), $filename);
    }

    public function checkInventoryExportLimit(Request $request): array
    {
        $filterData = $this->getExportInventoriesFilterData($request);

        $companyId = session('store_manager_selected_location_company_id');

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        /** @var StoreManager $storeManager */
        $storeManager = $request->user();

        $inventoryService = resolve(InventoryService::class);

        return $inventoryService->exportInventoriesWithJob($storeManager, $filterData, $companyId, $filteredColumns);
    }

    private function getExportInventoriesFilterData(Request $request): array
    {
        return [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'product_id' => $request->get('product_id'),
            'category_id' => $request->get('category_id'),
            'brand_id' => $request->get('brand_id'),
            'color_id' => $request->get('color_id'),
            'size_id' => $request->get('size_id'),
            'article_numbers' => $request->get('article_numbers'),
            'department_ids' => $request->get('department_ids'),
            'tag_ids' => $request->get('tag_ids'),
            'style_ids' => $request->get('style_ids'),
            'location_ids' => $request->get('location_ids'),
            'stock_type' => $request->get('stock_type'),
            'selling_type' => $request->get('selling_type'),
            'region_ids' => $request->get('region_ids'),
            'status' => $request->get('status'),
            'product_collection_id' => $request->get('product_collection_id'),
            'attributes' => $request->get('attributes') ?? [],
        ];
    }
}
