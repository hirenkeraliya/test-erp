<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\Exports\VoidSaleExport;
use App\Domains\Sale\Resources\VoidedSalesItemsReportResource;
use App\Domains\Sale\Resources\VoidedSalesReportListResource;
use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VoidSaleController extends Controller
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
        $voidSaleNumber = $request->get('void_sale_number');
        $voidSaleOfflineNumber = $request->get('offline_sale_id');

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

        return Inertia::render('sales/void_sales/Index', [
            'counters' => $counters,
            'cashiers' => $cashiers,
            'voidSaleNumber' => $voidSaleNumber ?? null,
            'voidSaleOfflineNumber' => $voidSaleOfflineNumber ?? null,
            'exportPermission' => PermissionList::getExportPermissionName('void_sale'),
            'helpCenterMessages' => 'Void sales data without exchange report offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchVoidSales(Request $request): array
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
        ];

        $filterData['search_text'] = $request->get('void_sale_offline_number') ?? null;
        $filterData['void_sale_number'] = $request->get('void_sale_number') ?? null;
        if (null !== $request->get('void_sale_offline_number') || null !== $request->get('void_sale_number')) {
            $filterData['date_range'] = null;
        }

        $lengthAwarePaginator = $this->saleQueries->getPaginatedVoidSalesWithRelationsForStoreManager(
            $filterData,
            session('store_manager_selected_location_id'),
            $companyId
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => VoidedSalesReportListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportVoidSale(string $filename, Request $request): BinaryFileResponse
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
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $filterData['search_text'] = $request->get('void_sale_offline_number') ?? null;
        $filterData['void_sale_number'] = $request->get('void_sale_number') ?? null;
        if (null !== $request->get('void_sale_offline_number') || null !== $request->get('void_sale_number')) {
            $filterData['date_range'] = null;
        }

        $voidSale = $this->saleQueries->getVoidSalesWithRelationsForExportInStoreManagerPanel(
            $filterData,
            session('store_manager_selected_location_id'),
            $companyId
        );

        return Excel::download(new VoidSaleExport($voidSale, $filteredColumns), $filename);
    }

    /**
     * @return array<string, VoidedSalesItemsReportResource>
     */
    public function fetchVoidSaleItemsBySaleId(int $saleId): array
    {
        $companyId = session('store_manager_selected_location_company_id');
        $voidSaleDetails = $this->saleQueries->getVoidSaleItemsForStoreManager(
            $saleId,
            session('store_manager_selected_location_id'),
            $companyId
        );

        return [
            'void_sale_details' => new VoidedSalesItemsReportResource($voidSaleDetails),
        ];
    }
}
