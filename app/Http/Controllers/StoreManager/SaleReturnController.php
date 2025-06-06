<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\SaleReturn\Exports\SaleReturnExport;
use App\Domains\SaleReturn\Resources\SaleReturnItemsReportResource;
use App\Domains\SaleReturn\Resources\SaleReturnReportListResource;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleReturnController extends Controller
{
    public function __construct(
        protected SaleReturnQueries $saleReturnQueries
    ) {
    }

    public function index(Request $request): Response
    {
        $companyId = session('store_manager_selected_location_company_id');
        $cashierQueries = resolve(CashierQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $cashiers = $cashierQueries->getAllCashiersByCompany($companyId);
        $offlineSaleReturnId = $request->get('offline_sale_return_id');

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

        return Inertia::render('sales/sale_returns/Index', [
            'counters' => $counters,
            'cashiers' => $cashiers,
            'offlineSaleReturnId' => $offlineSaleReturnId ?? null,
            'exportPermission' => PermissionList::getExportPermissionName('sale_return'),
            'helpCenterMessages' => 'Generate detailed sale return reports by utilizing our comprehensive filter options, enabling precise data searches, and export your filter records effortlessly for further analysis.',
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchSaleReturns(Request $request): array
    {
        $locationId = session('store_manager_selected_location_id');
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
            'offline_sale_return_id' => $request->get('offline_sale_return_id'),
            'e_invoice_submitted' => null,
        ];

        if (null !== $request->get('offline_sale_return_id')) {
            $filterData['date_range'] = null;
        }

        $lengthAwarePaginator = $this->saleReturnQueries->getPaginatedSaleReturnsWithRelationsForStoreManager(
            $filterData,
            [$locationId],
            session('store_manager_selected_location_company_id')
        );

        $consolidatedSaleReturns = $this->saleReturnQueries->getFilteredTotalsForReport(
            $filterData,
            session('store_manager_selected_location_company_id'),
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => SaleReturnReportListResource::collection($lengthAwarePaginator->getCollection()),
            /* @phpstan-ignore-next-line */
            'total_units_returned' => $consolidatedSaleReturns->total_units_returned,
            /* @phpstan-ignore-next-line */
            'total_return_sales' => $consolidatedSaleReturns->total_return_sales,
            /* @phpstan-ignore-next-line */
            'total_return_amount' => $consolidatedSaleReturns->total_return_amount,
        ];
    }

    public function exportSaleReturns(string $filename, Request $request): BinaryFileResponse
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
            'offline_sale_return_id' => $request->get('offline_sale_return_id'),
            'e_invoice_submitted' => null,
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        if (null !== $request->get('offline_sale_return_id')) {
            $filterData['date_range'] = null;
        }

        $saleReturns = $this->saleReturnQueries->getSaleReturnsWithRelationsForStoreManagerExport(
            $filterData,
            [session('store_manager_selected_location_id')],
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(new SaleReturnExport($saleReturns, $filteredColumns), $filename);
    }

    /**
     * @return array<string, SaleReturnItemsReportResource>
     */
    public function fetchSaleReturnItems(int $saleReturnId): array
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
}
