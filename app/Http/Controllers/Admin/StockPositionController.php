<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Inventory\Enums\Types;
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
use App\Domains\StockPosition\Exports\StockPositionExport;
use App\Domains\StockTransfer\Services\StockTransferService;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockPositionController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = session('admin_company_id');
        $stockTransferService = resolve(StockTransferService::class);
        [$stores, $warehouses] = $stockTransferService->getStoresAndWarehouses($companyId);
        $locationId = (int) $request->get('location_id');
        $locationQueries = resolve(LocationQueries::class);
        $selectedLocations = null;

        $regionQueries = resolve(RegionQueries::class);
        $regions = $regionQueries->getRegionByCompanyId($companyId);

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollections = $productCollectionQueries->getProductCollections($companyId);

        if (config('app.product_variant')) {
            $attributeQueries = resolve(AttributeQueries::class);
            $attributes = $attributeQueries->getAttributes(session('admin_company_id'));
        }

        if (0 !== $locationId) {
            $location = $locationQueries->getById($locationId, $companyId, LocationTypes::STORE->value);

            $selectedLocations = [
                'code' => $location->code,
                'id' => $location->id,
                'name' => $location->name,
            ];
        }

        return Inertia::render('reports/stock_position/Index', [
            'stores' => $stores,
            'regions' => $regions,
            'warehouses' => $warehouses,
            'productCollections' => $productCollections,
            'stockTypes' => Types::formattedForSelection(),
            'dashboardFilterData' => [
                'stock_type' => (int) $request->get('stock_type'),
                'location_id' => $locationId > 0 ? [$locationId] : null,
                'status' => $request->get('status'),
                'selectedLocations' => $selectedLocations,
            ],
            'productStatuses' => ProductStatuses::getList(),
            'exportPermission' => PermissionList::getExportPermissionName('inventory'),
            'sellingTypes' => SellingTypes::formattedForSelection(),
            'helpCenterMessages' => 'Inventory reports display product current stock, reserved stock, available stock, total inventory value and offering advanced filters, search options, and seamless export capabilities.',
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'locationTypes' => LocationTypes::getList(),
            'attributes' => $attributes ?? collect([]),
        ]);
    }

    public function fetchStockPositions(Request $request): array
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
            'location_ids' => $request->get('location_ids'),
            'article_numbers' => $request->get('article_numbers'),
            'department_ids' => $request->get('department_ids'),
            'tag_ids' => $request->get('tag_ids'),
            'stock_type' => $request->get('stock_type'),
            'selling_type' => $request->get('selling_type'),
            'style_ids' => $request->get('style_ids'),
            'region_ids' => $request->get('region_ids'),
            'status' => $request->get('status'),
            'product_collection_id' => $request->get('product_collection_id'),
            'attributes' => $request->get('attributes') ?? [],
        ];

        $inventoryQueries = resolve(InventoryQueries::class);
        $companyId = session('admin_company_id');

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

    public function exportStockPositions(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getExportStockPositionsFilterData($request);

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $inventoryQueries = resolve(InventoryQueries::class);

        $inventories = $inventoryQueries->inventoryListsForExport($filterData, session('admin_company_id'));

        return Excel::download(new StockPositionExport($inventories, $filteredColumns), $filename);
    }

    public function checkStockPositionExportLimit(Request $request): array
    {
        $filterData = $this->getExportStockPositionsFilterData($request);

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $companyId = session('admin_company_id');

        /** @var Admin $admin */
        $admin = $request->user();

        $inventoryService = resolve(InventoryService::class);

        return $inventoryService->exportInventoriesWithJob($admin, $filterData, $companyId, $filteredColumns);
    }

    private function getExportStockPositionsFilterData(Request $request): array
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
            'location_ids' => $request->get('location_ids'),
            'article_numbers' => $request->get('article_numbers'),
            'department_ids' => $request->get('department_ids'),
            'tag_ids' => $request->get('tag_ids'),
            'stock_type' => $request->get('stock_type'),
            'selling_type' => $request->get('selling_type'),
            'style_ids' => $request->get('style_ids'),
            'region_ids' => $request->get('region_ids'),
            'status' => $request->get('status'),
            'product_collection_id' => $request->get('product_collection_id'),
            'attributes' => $request->get('attributes') ?? [],
        ];
    }
}
