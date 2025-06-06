<?php

namespace App\Http\Controllers\Admin;

use App\Domains\GenuineReceiptVerification\Exports\ReceiptVerificationReportExport;
use App\Domains\GenuineReceiptVerification\GenuineReceiptVerificationQueries;
use App\Domains\GenuineReceiptVerification\Recourses\ReceiptVerificationReportListResource;
use App\Domains\GenuineReceiptVerification\Services\ReceiptVerificationReportService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GenuineReceiptVerificationReportController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = session('admin_company_id');
        $locationQueries = resolve(LocationQueries::class);

        $locationId = (int) $request->get('location_id');

        $dateRange = [now()->format('Y-m-d 00:00:00'), now()->format('Y-m-d 23:59:59')];
        $locationQueries = resolve(LocationQueries::class);
        $selectedLocations = [];
        if ($locationId > 0) {
            $location = $locationQueries->getById($locationId, $companyId, LocationTypes::STORE->value);
            $selectedLocations = [
                'code' => $location->code,
                'id' => $location->id,
                'name' => $location->name,
            ];

            $dateRange = [];
        }

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $filterData = [
            'locationIds' => $locationId > 0 ? [$locationId] : null,
            'selectedLocations' => $selectedLocations,
            'dateRange' => $dateRange,
        ];

        return Inertia::render('reports/genuine_receipt_report/Index', [
            'locations' => $locations,
            'filterData' => $filterData,
            'exportPermission' => PermissionList::getExportPermissionName('genuine_receipt_verification'),
        ]);
    }

    public function fetchReceiptVerificationReports(Request $request): array
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        $genuineReceiptVerificationQueries = resolve(GenuineReceiptVerificationQueries::class);
        $lengthAwarePaginator = $genuineReceiptVerificationQueries->getPaginatedReceiptVerificationReport(
            $filterData,
            $companyId
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => ReceiptVerificationReportListResource::collection($lengthAwarePaginator),
        ];
    }

    public function printReceiptVerifications(Request $request): string
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $receiptVerificationService = resolve(ReceiptVerificationReportService::class);

        return $receiptVerificationService->print($filterData, $filteredColumns, $companyId);
    }

    public function exportReceiptsVerificationReport(string $filename, Request $request): BinaryFileResponse
    {
        $companyId = session('admin_company_id');
        $filterData = $this->getFilterData($request);

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $genuineReceiptVerificationQueries = resolve(GenuineReceiptVerificationQueries::class);
        $genuineReceiptVerification = $genuineReceiptVerificationQueries->getReceiptVerificationReportDataForExport(
            $filterData,
            $companyId
        );

        return Excel::download(
            new ReceiptVerificationReportExport($genuineReceiptVerification, $filteredColumns),
            $filename
        );
    }

    private function getFilterData(Request $request): array
    {
        return [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
            'date_range' => $request->get('date_range'),
            'is_genuine' => $request->get('is_genuine'),
            'export_columns' => $request->get('export_columns'),
        ];
    }
}
