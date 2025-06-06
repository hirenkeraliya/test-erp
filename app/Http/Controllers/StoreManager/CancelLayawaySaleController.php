<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\CancelLayawaySaleExport;
use App\Domains\Sale\Resources\LayawaySaleItemsReportResource;
use App\Domains\Sale\Resources\LayawaySaleReportListResource;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\PrintCancelLayawaySaleReportService;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CancelLayawaySaleController extends Controller
{
    public function __construct(
        protected SaleQueries $saleQueries
    ) {
    }

    public function index(Request $request): Response
    {
        $companyId = session('store_manager_selected_location_company_id');
        $cashierQueries = resolve(CashierQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashiers = $cashierQueries->getAllCashiersByCompany($companyId);
        $counters = $counterQueries->getCounterListOfSelectedLocation(
            session('store_manager_selected_location_id'),
            $companyId
        );

        $offlineSaleId = $request->get('offline_sale_id');

        $cashiers->transform(function ($cashier): array {
            /** @var Employee $employee */
            $employee = $cashier->employee;

            return [
                'id' => $cashier->id,
                'name' => $employee->getFullName(),
            ];
        });

        return Inertia::render('sales/cancel_layaway_sales/Index', [
            'counters' => $counters,
            'cashiers' => $cashiers,
            'offlineSaleId' => $offlineSaleId,
            'exportPermission' => PermissionList::getExportPermissionName('cancel_layaway_sale'),
            'helpCenterMessages' => 'Cancel layaway sales offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchCancelLayawaySales(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'counter_ids' => $request->get('counter_ids'),
            'cashier_id' => $request->get('cashier_id'),
            'member_id' => $request->get('member_id'),
            'employee_id' => $request->get('employee_id'),
            'offline_sale_id' => $request->get('offline_sale_id'),
        ];

        $lengthAwarePaginator = $this->saleQueries->getPaginatedCancelLayawaySalesForStoreManager(
            $filterData,
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id'),
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => LayawaySaleReportListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportCancelLayawaySales(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'date_range' => $request->get('date_range'),
            'counter_ids' => $request->get('counter_ids'),
            'cashier_id' => $request->get('cashier_id'),
            'member_id' => $request->get('member_id'),
            'employee_id' => $request->get('employee_id'),
            'offline_sale_id' => $request->get('offline_sale_id'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $cancelLayawaySale = $this->saleQueries->getCancelLayawaySalesExportForStoreManager(
            $filterData,
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(new CancelLayawaySaleExport($cancelLayawaySale, $filteredColumns), $filename);
    }

    /**
     * @return array<string, LayawaySaleItemsReportResource>
     */
    public function fetchCancelLayawaySaleItemsBySaleId(Request $request, int $saleId): array
    {
        $cancelLayawaySaleDetails = $this->saleQueries->getCancelLayawaySaleItemsByForStoreManager(
            $saleId,
            session('store_manager_selected_location_id'),
            session('store_manager_selected_location_company_id'),
        );

        return [
            'cancel_layaway_sale_details' => new LayawaySaleItemsReportResource($cancelLayawaySaleDetails),
        ];
    }

    public function printCancelLayawaySale(int $saleId): string
    {
        $printLayawaySaleReportService = resolve(PrintCancelLayawaySaleReportService::class);

        return $printLayawaySaleReportService->print(
            $saleId,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );
    }
}
