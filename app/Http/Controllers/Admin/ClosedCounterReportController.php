<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CounterUpdate\Exports\ClosedCounterExport;
use App\Domains\CounterUpdate\Resources\ClosedCounterDetailsResource;
use App\Domains\CounterUpdate\Resources\ClosedCounterPrintDetailsResource;
use App\Domains\CounterUpdate\Resources\ClosedCounterReportListResource;
use App\Domains\CounterUpdate\Services\PrintClosedCounterDetailsService;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClosedCounterReportController extends Controller
{
    public function __construct(
        protected CounterUpdateQueries $counterUpdateQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('admin_company_id');
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        return Inertia::render('reports/closed_counters/ClosedCountersReports', [
            'locations' => $locations,
            'exportPermission' => PermissionList::getExportPermissionName('shift_close'),
            'helpCenterMessage' => 'Show all the closed counter report with counter information offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    public function fetchClosedCounters(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
            'counter_ids' => $request->get('counter_ids'),
            'cashier_id' => $request->get('cashier_id'),
            'date_range' => $request->get('date_range'),
            'closed_at' => $request->get('closed_at'),
        ];
        $companyId = session('admin_company_id');

        $lengthAwarePaginator = $this->counterUpdateQueries->closedCounterQueryList($filterData, $companyId);
        $totalSalesCollection = $this->counterUpdateQueries->closedCounterTotalSalesCollection($filterData, $companyId);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => ClosedCounterReportListResource::collection($lengthAwarePaginator->getCollection()),
            'total_sales_collection' => $totalSalesCollection,
        ];
    }

    /**
     * @return array<string, ClosedCounterDetailsResource>
     */
    public function fetchClosedCounterDetails(int $counterUpdateId): array
    {
        $counterUpdateDetails = $this->counterUpdateQueries->getByIdFilterByCompany(
            $counterUpdateId,
            session('admin_company_id')
        );

        return [
            'closed_counter_update_details' => new ClosedCounterDetailsResource($counterUpdateDetails),
        ];
    }

    public function fetchClosedCounterPrintDetails(int $counterUpdateId): array
    {
        $counterUpdateDetails = $this->counterUpdateQueries->getByIdFilterByCompanyForPrint(
            $counterUpdateId,
            session('admin_company_id')
        );

        return [
            'closed_counter_update_print_details' => new ClosedCounterPrintDetailsResource($counterUpdateDetails),
        ];
    }

    public function exportClosedCounters(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'location_ids' => $request->get('location_ids'),
            'counter_ids' => $request->get('counter_ids'),
            'cashier_id' => $request->get('cashier_id'),
            'date_range' => $request->get('date_range'),
            'closed_at' => $request->get('closed_at'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $closedCounters = $this->counterUpdateQueries->closedCounterListForExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new ClosedCounterExport($closedCounters, $filteredColumns), $filename);
    }

    public function exportClosedCounterAttempts(int $counterUpdateId): string
    {
        $counterUpdateDetails = $this->counterUpdateQueries->getCounterUpdateAttemptDetailsByIdAndFilterByCompany(
            $counterUpdateId,
            session('admin_company_id')
        );

        $printClosedCounterDetailsService = resolve(PrintClosedCounterDetailsService::class);

        return $printClosedCounterDetailsService->printCloseCounterAttempts($counterUpdateDetails);
    }

    public function exportClosedCounterTills(int $counterUpdateId): string
    {
        $counterUpdateDetails = $this->counterUpdateQueries->getCounterUpdateTillDetailsByIdAndFilterByCompany(
            $counterUpdateId,
            session('admin_company_id')
        );

        $printClosedCounterDetailsService = resolve(PrintClosedCounterDetailsService::class);

        return $printClosedCounterDetailsService->printCloseCounterTills($counterUpdateDetails);
    }

    public function exportClosedCounterTakeBreak(int $counterUpdateId): string
    {
        $counterUpdateDetails = $this->counterUpdateQueries->getCounterUpdateTillDetailsByIdAndFilterByCompany(
            $counterUpdateId,
            session('admin_company_id')
        );

        $printClosedCounterDetailsService = resolve(PrintClosedCounterDetailsService::class);

        return $printClosedCounterDetailsService->printCloseCounterTakeBreak($counterUpdateDetails);
    }

    public function exportClosedCounterDrawerDetails(int $counterUpdateId): string
    {
        $counterUpdateDetails = $this->counterUpdateQueries->getCounterUpdateTillDetailsByIdAndFilterByCompany(
            $counterUpdateId,
            session('admin_company_id')
        );

        $printClosedCounterDetailsService = resolve(PrintClosedCounterDetailsService::class);

        return $printClosedCounterDetailsService->printCloseCounterDrawerDetails($counterUpdateDetails);
    }

    public function fetchClosedCounterById(int $closeCounterId): array
    {
        $closedCounterData = $this->counterUpdateQueries->getByIdFilterByCompany(
            $closeCounterId,
            session('admin_company_id')
        );

        return [
            'close_counter_details' => new ClosedCounterDetailsResource($closedCounterData),
        ];
    }

    public function exportTrackOfflineMode(int $counterUpdateId): string
    {
        $counterUpdateDetails = $this->counterUpdateQueries->getCounterUpdateTillDetailsByIdAndFilterByCompany(
            $counterUpdateId,
            session('admin_company_id')
        );

        $printClosedCounterDetailsService = resolve(PrintClosedCounterDetailsService::class);

        return $printClosedCounterDetailsService->printCloseCounterTrackOfflineMode($counterUpdateDetails);
    }
}
