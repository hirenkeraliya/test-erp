<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\Enums\CreditAndLayawaySaleStatuses;
use App\Domains\Sale\Exports\LayawaySaleExport;
use App\Domains\Sale\Resources\LayawaySaleItemsReportResource;
use App\Domains\Sale\Resources\LayawaySaleReportListResource;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\PrintLayawaySaleReportService;
use App\Domains\Sale\Services\PrintLayawaySaleTaxInvoiceService;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LayawaySaleController extends Controller
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

        return Inertia::render('sales/layaway_sales/Index', [
            'statuses' => CreditAndLayawaySaleStatuses::getList(),
            'offlineSaleId' => $offlineSaleId,
            'layawaySaleStatusPending' => $request->get('status_id') ? (int) $request->get(
                'status_id'
            ) : CreditAndLayawaySaleStatuses::PENDING->value,
            'counters' => $counters,
            'cashiers' => $cashiers,
            'exportPermission' => PermissionList::getExportPermissionName('layaway_sale'),
            'helpCenterMessages' => 'Pending or Complete layaway sales data without exchange report offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchPendingLayawaySales(Request $request): array
    {
        $companyId = session('store_manager_selected_location_company_id');
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
            'status_id' => (int) $request->get('status_id'),
            'offline_sale_id' => $request->get('offline_sale_id'),
        ];

        $lengthAwarePaginator = $this->saleQueries->getPaginatedPendingLayawaySalesWithRelationsForStoreManager(
            $filterData,
            session('store_manager_selected_location_id'),
            $companyId
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => LayawaySaleReportListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportLayawaySales(string $filename, Request $request): BinaryFileResponse
    {
        $companyId = session('store_manager_selected_location_company_id');
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'date_range' => $request->get('date_range'),
            'counter_ids' => $request->get('counter_ids'),
            'cashier_id' => $request->get('cashier_id'),
            'member_id' => $request->get('member_id'),
            'employee_id' => $request->get('employee_id'),
            'status_id' => (int) $request->get('status_id'),
            'offline_sale_id' => $request->get('offline_sale_id'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $layawaySale = $this->saleQueries->getPendingLayawaySalesWithRelationsForExportInStoreManagerPanel(
            $filterData,
            session('store_manager_selected_location_id'),
            $companyId
        );

        return Excel::download(new LayawaySaleExport($layawaySale, $filteredColumns), $filename);
    }

    /**
     * @return array<string, LayawaySaleItemsReportResource>
     */
    public function fetchLayawaySaleItemsBySaleId(int $saleId): array
    {
        $companyId = session('store_manager_selected_location_company_id');
        $layawaySaleDetails = $this->saleQueries->getLayawaySaleItemsForStoreManager(
            $saleId,
            session('store_manager_selected_location_id'),
            $companyId
        );

        return [
            'layaway_sale_details' => new LayawaySaleItemsReportResource($layawaySaleDetails),
        ];
    }

    public function printLayawaySale(int $saleId): string
    {
        $printLayawaySaleReportService = resolve(PrintLayawaySaleReportService::class);

        return $printLayawaySaleReportService->print(
            $saleId,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );
    }

    public function printSaleTaxInvoice(int $saleId): string
    {
        $printSaleTaxInvoiceService = resolve(PrintLayawaySaleTaxInvoiceService::class);

        return $printSaleTaxInvoiceService->print(
            $saleId,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );
    }
}
