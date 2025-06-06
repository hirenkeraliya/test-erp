<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\InventoryUpdate\Exports\StockMovementLedgerExport;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\InventoryUpdate\Resources\AdminStockMovementLedgerReportListResource;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockMovementLedgerReportController extends Controller
{
    public function __construct(
        protected InventoryUpdateQueries $inventoryUpdateQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('admin_company_id');

        [$stores, $warehouses] = $this->getStoresAndWarehouses($companyId);

        return Inertia::render('reports/stock_movement_ledger_report/Index', [
            'stores' => $stores,
            'warehouses' => $warehouses,
            'exportPermission' => PermissionList::getExportPermissionName('stock_movement_ledger'),
            'helpCenterMessages' => 'Display the stock movement ledger report, detailing the movement of stock from one location to another. Include information such as the number of closing stock, reference number, and location details. Advanced filters, search options, and seamless export capabilities are provided for detailed analysis and insights.',
            'staticLocationTypes' => LocationTypes::getFormattedArrayForStaticUse(),
            'locationTypes' => LocationTypes::getList(),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchStockMovementLedgerReport(Request $request): array
    {
        $filterData = [
            'location_ids' => $request->get('location_ids') ?? [],
            'product_id' => $request->get('product_id'),
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->inventoryUpdateQueries->getPaginatedStockMovementsOfAProductForALocation(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => AdminStockMovementLedgerReportListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportStockMovementLedger(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'location_ids' => $request->get('location_ids'),
            'product_id' => $request->get('product_id'),
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $stockMovementLedgers = $this->inventoryUpdateQueries->getStockMovementsOfAProductForALocationForExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new StockMovementLedgerExport($stockMovementLedgers, $filteredColumns), $filename);
    }

    /**
     * @return mixed[]
     */
    private function getStoresAndWarehouses(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);

        $locations = $locationQueries->getWithBasicColumns($companyId);
        $stores = $locations->where('type_id', LocationTypes::STORE->value)->values()->all();
        $warehouses = $locations->where('type_id', LocationTypes::WAREHOUSE->value)->values()->all();

        return [$stores, $warehouses];
    }
}
