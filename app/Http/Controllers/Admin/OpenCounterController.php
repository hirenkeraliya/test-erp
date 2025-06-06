<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CounterUpdate\Exports\OpenCounterExport;
use App\Domains\CounterUpdate\Resources\OpenCounterReportResource;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Sale\Resources\OpenCounterSalesReportListResource;
use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OpenCounterController extends Controller
{
    public function index(): Response
    {
        $companyId = session('admin_company_id');
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        return Inertia::render('reports/open_counters/Index', [
            'locations' => $locations,
            'exportPermission' => PermissionList::getExportPermissionName('open_counter'),
            'helpCenterMessages' => 'Display only open counter reports with location, cashier, counter name, and opening balance. Additionally, show the sales associated with each counter. Advanced filters, search options, and seamless export capabilities are provided for detailed analysis and insights.',
        ]);
    }

    public function fetchOpenCounters(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
            'counter_ids' => $request->get('counter_ids'),
            'cashier_id' => $request->get('cashier_ids'),
        ];
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $companyId = session('admin_company_id');

        $lengthAwarePaginator = $counterUpdateQueries->getOpenCounterDetailsForReportsList($filterData, $companyId);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => OpenCounterReportResource::collection($lengthAwarePaginator),
        ];
    }

    public function exportOpenCounters(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
            'counter_ids' => $request->get('counter_ids'),
            'cashier_id' => $request->get('cashier_ids'),
        ];

        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $companyId = session('admin_company_id');

        $openCounters = $counterUpdateQueries->getOpenCounterDetailsExport($filterData, $companyId);

        return Excel::download(new OpenCounterExport($openCounters), $filename);
    }

    public function fetchOpenCounterSales(Request $request, int $counterUpdateId): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];
        $saleQueries = resolve(SaleQueries::class);
        $companyId = session('admin_company_id');

        $lengthAwarePaginator = $saleQueries->getOpenCounterSalesDetailsForReportsList(
            $filterData,
            $counterUpdateId,
            $companyId
        );

        $consolidatedSales = $saleQueries->getFilteredTotalsForOpenCountersReport(
            $filterData,
            session('admin_company_id'),
            $counterUpdateId
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => OpenCounterSalesReportListResource::collection($lengthAwarePaginator->getCollection()),
            /* @phpstan-ignore-next-line */
            'total_units_sold' => $consolidatedSales->total_units_sold,
            /* @phpstan-ignore-next-line */
            'total_sales' => $consolidatedSales->total_sales,
            /* @phpstan-ignore-next-line */
            'total_sales_amount' => $consolidatedSales->total_sales_amount,
        ];
    }
}
