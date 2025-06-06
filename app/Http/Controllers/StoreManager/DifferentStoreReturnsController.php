<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\SaleReturn\Exports\DifferentStoreReturnsExport;
use App\Domains\SaleReturn\Resources\DifferentStoreReturnsReportListResource;
use App\Domains\SaleReturn\Resources\SaleReturnItemsReportResource;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DifferentStoreReturnsController extends Controller
{
    public function __construct(
        protected SaleReturnQueries $saleReturnQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('store_manager_selected_location_company_id');
        $cashierQueries = resolve(CashierQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashiers = $cashierQueries->getAllCashiersByCompany($companyId);

        $counters = $counterQueries->getCounterListOfSelectedLocation(
            session('store_manager_selected_location_id'),
            $companyId
        );

        $cashiers->transform(function ($cashier): array {
            /** @var Employee $employee */
            $employee = $cashier->employee;

            return [
                'id' => $cashier->id,
                'name' => $employee->getFullName(),
            ];
        });

        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        return Inertia::render('sales/sale_returns/DifferentStoreReturns', [
            'locations' => $locations,
            'counters' => $counters,
            'cashiers' => $cashiers,
            'exportPermission' => PermissionList::getExportPermissionName('different_store_return'),
            'helpCenterMessages' => 'Different location sale returns data offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    public function fetchDifferentStoreReturns(Request $request): array
    {
        $locationId = session('store_manager_selected_location_id');
        $filterData = $this->getFilters($request, $locationId);

        $lengthAwarePaginator = $this->saleReturnQueries->getPaginatedDifferentStoresReturnsForStoreManager(
            $filterData,
            [$locationId],
            session('store_manager_selected_location_company_id')
        );

        $consolidatedSaleReturns = $this->saleReturnQueries->getFilteredTotalsDifferentStoreForReport(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => DifferentStoreReturnsReportListResource::collection($lengthAwarePaginator->getCollection()),
            /* @phpstan-ignore-next-line */
            'total_units_returned' => $consolidatedSaleReturns->total_units_returned,
            /* @phpstan-ignore-next-line */
            'total_return_sales' => $consolidatedSaleReturns->total_return_sales,
            /* @phpstan-ignore-next-line */
            'total_return_amount' => $consolidatedSaleReturns->total_return_amount,
        ];
    }

    public function exportDifferentStoreReturns(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = $this->getFilters($request, session('store_manager_selected_location_id'));

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $saleReturns = $this->saleReturnQueries->getDifferentStoresReturnsForStoreManagerExport(
            $filterData,
            [session('store_manager_selected_location_id')],
            session('store_manager_selected_location_company_id')
        );

        /** @var array $saleReturnsArray */
        $saleReturnsArray = DifferentStoreReturnsReportListResource::collection($saleReturns)->toArray($request);

        return Excel::download(
            new DifferentStoreReturnsExport(collect($saleReturnsArray), $filteredColumns),
            $filename
        );
    }

    /**
     * @return array<string, SaleReturnItemsReportResource>
     */
    public function fetchSaleReturnItemsForDifferentStore(int $saleReturnId): array
    {
        $saleReturnDetails = $this->saleReturnQueries->getSaleReturnItemsForStoreManager(
            $saleReturnId,
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id')
        );

        return [
            'sale_return_details' => new SaleReturnItemsReportResource($saleReturnDetails),
        ];
    }

    private function getFilters(Request $request, int $locationId): array
    {
        return [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'counter_ids' => $request->get('counter_ids'),
            'cashier_id' => $request->get('cashier_id'),
            'member_id' => $request->get('member_id'),
            'employee_id' => $request->get('employee_id'),
            'original_sale_location_ids' => (array) $request->get('original_sale_location_ids'),
            'original_sale_counter_ids' => (array) $request->get('original_sale_counter_ids'),
            'original_sale_cashier_id' => $request->get('original_sale_cashier_id'),
            'location_ids' => [$locationId],
            'e_invoice_submitted' => null,
        ];
    }
}
