<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Activity\ActivityLogQueries;
use App\Domains\Activity\Exports\ActivityExport;
use App\Domains\Activity\Resources\ActivityListResource;
use App\Domains\Activity\Services\ActivityService;
use App\Domains\Common\Enums\ModelMappingTypes;
use App\Domains\Common\Services\PrintPdfHeaderFilterService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivityReportController extends Controller
{
    public function __construct(
        protected ActivityLogQueries $activityLogQueries
    ) {
    }

    public function index(): Response
    {
        $modelMapping = ModelMappingTypes::getList();

        return Inertia::render('reports/activity/Index', [
            'modules' => $modelMapping,
            'defaultModuleType' => ModelMappingTypes::BASE_MODULES->value,
            'exportPermission' => PermissionList::getExportPermissionName('activities'),
        ]);
    }

    /**
     * @return array<string, int>|array<string, AnonymousResourceCollection>
     */
    public function fetchActivities(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'employee_id' => $request->get('employee_id'),
            'module_type' => $request->get('module_type'),
        ];
        $lengthAwarePaginator = $this->activityLogQueries->getPaginatedActivityList(
            $filterData,
            session('admin_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => ActivityListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function printActivities(Request $request): string
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'employee_id' => $request->get('employee_id'),
            'module_type' => $request->get('module_type'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $companyId = session('admin_company_id');
        $activities = $this->activityLogQueries->getActivitiesWithRelationsForPrint($filterData, $companyId);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $activityService = resolve(ActivityService::class);
        $activityData = $activityService->activityDataPrint(
            $activities,
            (int) $filterData['module_type'],
            $filteredColumns
        );

        $printPdfHeaderFilterService = resolve(PrintPdfHeaderFilterService::class);
        $filterHeaderData = $printPdfHeaderFilterService->buildFilterData($filterData);

        return view('prints.activities_report', [
            'activities' => $activityData,
            'company' => $company,
            'columns' => $filteredColumns,
            'filter_header_data' => $filterHeaderData,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
        ])->render();
    }

    public function exportActivities(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'date_range' => $request->get('date_range'),
            'employee_id' => $request->get('employee_id'),
            'module_type' => $request->get('module_type'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $activities = $this->activityLogQueries->getActivitiesForExport($filterData, session('admin_company_id'));

        return Excel::download(
            new ActivityExport($activities, (int) $filterData['module_type'], $filteredColumns),
            $filename
        );
    }
}
