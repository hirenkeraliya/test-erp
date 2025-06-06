<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\Enums\CreditAndLayawaySaleStatuses;
use App\Domains\Sale\Exports\CreditSaleExport;
use App\Domains\Sale\Resources\CreditSaleItemsReportResource;
use App\Domains\Sale\Resources\CreditSaleReportListResource;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\PrintCreditSaleReportService;
use App\Domains\Sale\Services\PrintCreditSaleTaxInvoiceService;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CreditSaleController extends Controller
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

        return Inertia::render('sales/credit_sales/Index', [
            'statuses' => CreditAndLayawaySaleStatuses::getList(),
            'offlineSaleId' => $offlineSaleId,
            'creditSaleStatusPending' => $request->get('status_id') ? (int) $request->get(
                'status_id'
            ) : CreditAndLayawaySaleStatuses::PENDING->value,
            'counters' => $counters,
            'cashiers' => $cashiers,
            'exportPermission' => PermissionList::getExportPermissionName('credit_sale'),
            'helpCenterMessages' => 'Pending or Complete credit sales data without exchange report offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchPendingCreditSales(Request $request): array
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

        $lengthAwarePaginator = $this->saleQueries->getPaginatedPendingCreditSalesWithRelationsForStoreManager(
            $filterData,
            session('store_manager_selected_location_id'),
            $companyId
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => CreditSaleReportListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportCreditSales(string $filename, Request $request): BinaryFileResponse
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

        $creditSale = $this->saleQueries->getPendingCreditSalesWithRelationsForExportInStoreManagerPanel(
            $filterData,
            session('store_manager_selected_location_id'),
            $companyId
        );

        return Excel::download(new CreditSaleExport($creditSale, $filteredColumns), $filename);
    }

    /**
     * @return array<string, CreditSaleItemsReportResource>
     */
    public function fetchCreditSaleItemsBySaleId(int $saleId): array
    {
        $companyId = session('store_manager_selected_location_company_id');
        $creditSaleDetails = $this->saleQueries->getCreditSaleItemsForStoreManager(
            $saleId,
            session('store_manager_selected_location_id'),
            $companyId
        );

        return [
            'credit_sale_details' => new CreditSaleItemsReportResource($creditSaleDetails),
        ];
    }

    public function printCreditSale(int $saleId): string
    {
        $printCreditSaleReportService = resolve(PrintCreditSaleReportService::class);

        return $printCreditSaleReportService->print(
            $saleId,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );
    }

    public function printCreditSaleTaxInvoice(int $saleId): string
    {
        $printCreditSaleReportService = resolve(PrintCreditSaleTaxInvoiceService::class);

        return $printCreditSaleReportService->print(
            $saleId,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );
    }
}
