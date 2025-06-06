<?php

declare(strict_types=1);

namespace App\Http\Controllers\WarehouseManager;

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Domains\ReservedStock\Exports\ReservedInventoryExport;
use App\Domains\ReservedStock\ReservedStockQueries;
use App\Domains\ReservedStock\Resources\ReservedInventoryReportListResource;
use App\Domains\ReservedStock\Services\ReservedInventoryReportService;
use App\Http\Controllers\Controller;
use App\Models\ReservedStock;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReservedInventoryReportController extends Controller
{
    public function __construct(
        protected ReservedStockQueries $reservedStockQueries
    ) {
    }

    public function index(Request $request): Response
    {
        $companyId = session('warehouse_manager_selected_location_company_id');

        $reservedInventoryReportService = resolve(ReservedInventoryReportService::class);

        [$stores, $warehouses] = $reservedInventoryReportService->getStoresAndWarehouses($companyId);

        $selectedProduct = null;

        if ($request->has('product_id')) {
            $productQueries = resolve(ProductQueries::class);
            $product = $productQueries->getByIdOnlyName((int) $request->get('product_id'), $companyId);

            $selectedProduct = [
                'id' => $product->id,
                'name' => $product->name,
            ];
        }

        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollections = $productCollectionQueries->getProductCollections($companyId);

        return Inertia::render('reports/reserved_inventory_report/Index', [
            'stores' => $stores,
            'warehouses' => $warehouses,
            'productCollections' => $productCollections,
            'filterData' => [
                'product_id' => $request->product_id,
                'location_id' => $request->location_id,
                'type_id' => $request->type_id,
                'selectedProduct' => $selectedProduct,
            ],
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'locationTypes' => LocationTypes::getList(),
            'exportPermission' => PermissionList::getExportPermissionName('reserved_inventory'),
            'helpCenterMessages' => 'Reserved inventory report display reserved stock value by various modules and offer advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    public function fetchReservedInventoryReport(Request $request): array
    {
        $filterData = [
            'location_id' => $request->get('location_id'),
            'product_id' => $request->get('product_id'),
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'product_collection_id' => $request->get('product_collection_id'),
        ];

        $lengthAwarePaginator = $this->reservedStockQueries->getPaginatedReservedInventoryForLocation(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        $consolidatedData = $this->reservedStockQueries->getConsolidatedData(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => ReservedInventoryReportListResource::collection($lengthAwarePaginator->getCollection()),
            'total_stock' => $consolidatedData instanceof ReservedStock ? $consolidatedData->quantity : 0,
        ];
    }

    public function exportReservedInventory(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'location_id' => $request->get('location_id'),
            'product_id' => $request->get('product_id'),
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'product_collection_id' => $request->get('product_collection_id'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $reservedInventories = $this->reservedStockQueries->getReservedInventoryLocationForExport(
            $filterData,
            session('warehouse_manager_selected_location_company_id')
        );

        return Excel::download(new ReservedInventoryExport($reservedInventories, $filteredColumns), $filename);
    }
}
