<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\Exports\SaleExport;
use App\Domains\Sale\Resources\SaleItemsReportResource;
use App\Domains\Sale\Resources\SaleReportListResource;
use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleController extends Controller
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
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $cashiers->transform(function ($cashier): array {
            /** @var Employee $employee */
            $employee = $cashier->employee;

            return [
                'id' => $cashier->id,
                'name' => $employee->getFullName(),
            ];
        });

        return Inertia::render('sales/Index', [
            'cashiers' => $cashiers,
            'counters' => $counters,
            'offlineSaleId' => $offlineSaleId ?? null,
            'startDate' => $startDate ?? null,
            'endDate' => $endDate ?? null,
            'exportPermission' => PermissionList::getExportPermissionName('sale'),
            'helpCenterMessages' => 'Only regular, complete credit and complete layaway sales without exchanges report offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchRegularSales(Request $request): array
    {
        $locationId = session('store_manager_selected_location_id');
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
            'location_ids' => [$locationId],
            'offline_sale_id' => $request->get('offline_sale_id'),
            'e_invoice_submitted' => null,
        ];

        if (null !== $request->get('offline_sale_id')) {
            $filterData['date_range'] = null;
        }

        $lengthAwarePaginator = $this->saleQueries->getPaginatedRegularSalesAndCompleteWithRelationsForStoreManager(
            $filterData,
            [$locationId],
            $companyId,
        );

        $consolidatedSales = $this->saleQueries->getFilteredTotalsForReport(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => SaleReportListResource::collection($lengthAwarePaginator->getCollection()),
            /* @phpstan-ignore-next-line */
            'total_units_sold' => $consolidatedSales->total_units_sold,
            /* @phpstan-ignore-next-line */
            'total_sales' => $consolidatedSales->total_sales,
            /* @phpstan-ignore-next-line */
            'total_sales_amount' => $consolidatedSales->total_sales_amount,
        ];
    }

    public function exportSales(string $filename, Request $request): BinaryFileResponse
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
            'e_invoice_submitted' => null,
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        if (null !== $request->get('offline_sale_id')) {
            $filterData['date_range'] = null;
        }

        $sales = $this->saleQueries->getRegularAndLayawaySalesWithRelationsForExportInStoreManagerPanel(
            $filterData,
            [session('store_manager_selected_location_id')]
        );

        return Excel::download(new SaleExport($sales, $filteredColumns), $filename);
    }

    /**
     * @return array<string, SaleItemsReportResource>
     */
    public function fetchSaleItemsBySaleId(int $saleId): array
    {
        $companyId = session('store_manager_selected_location_company_id');
        $saleDetails = $this->saleQueries->getSaleItemsForStoreManager(
            $saleId,
            session('store_manager_selected_location_id'),
            $companyId
        );

        return [
            'sale_details' => new SaleItemsReportResource($saleDetails),
        ];
    }
}
