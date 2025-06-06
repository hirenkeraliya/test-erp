<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Cashier\CashierQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\Exports\SaleExchangesExport;
use App\Domains\Sale\Resources\SaleExchangesListResource;
use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleExchangesReportController extends Controller
{
    public function __construct(
        protected SaleQueries $saleQueries
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

        return Inertia::render('reports/sale_exchanges/Index', [
            'counters' => $counters,
            'cashiers' => $cashiers,
            'exportPermission' => PermissionList::getExportPermissionName('sale_exchange'),
            'helpCenterMessages' => 'Sales exchanges report offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchSaleExchanges(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'location_ids' => [session('store_manager_selected_location_id')],
            'counter_ids' => $request->get('counter_ids'),
            'cashier_id' => $request->get('cashier_id'),
            'member_id' => $request->get('member_id'),
            'employee_id' => $request->get('employee_id'),
        ];

        $lengthAwarePaginator = $this->saleQueries->getPaginatedSaleExchangesWithRelations(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => SaleExchangesListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function export(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'date_range' => $request->get('date_range'),
            'location_ids' => [session('store_manager_selected_location_id')],
            'counter_ids' => $request->get('counter_ids'),
            'cashier_id' => $request->get('cashier_id'),
            'member_id' => $request->get('member_id'),
            'employee_id' => $request->get('employee_id'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $sales = $this->saleQueries->getSaleExchangesWithRelationsForExport(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(new SaleExchangesExport($sales, $filteredColumns), $filename);
    }
}
