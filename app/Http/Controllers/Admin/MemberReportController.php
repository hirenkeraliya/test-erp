<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\CommonFunctions;
use App\Domains\Common\Services\PrintPdfHeaderFilterService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\Exports\MemberReportExport;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Resources\MemberDetailsReportResource;
use App\Domains\Member\Resources\MemberReportResource;
use App\Domains\Member\Services\MemberService;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MemberReportController extends Controller
{
    public function __construct(
        protected MemberQueries $memberQueries
    ) {
    }

    public function index(): Response
    {
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns(session('admin_company_id'));

        return Inertia::render('reports/members_report/Index', [
            'locations' => $locations,
            'exportPermission' => PermissionList::getExportPermissionName('member_report'),
            'helpCenterMessages' => 'The Daily New Member Count Report displays the number of new members added to each location every day, providing a clear overview of daily membership growth across all stores with advanced filters, search options, and seamless export capabilities for in-depth analysis.',
        ]);
    }

    public function fetchMembersReport(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
            'date_range' => $request->get('date_range'),
        ];
        $companyId = session('admin_company_id');

        $lengthAwarePaginator = $this->memberQueries->getPaginatedMemberReport($filterData, $companyId);

        return [
            'total_records' => $lengthAwarePaginator['member_data']->total(),
            'data' => MemberReportResource::collection($lengthAwarePaginator['member_data']->getCollection()),
            'total_members' => CommonFunctions::currencyFormatInteger($lengthAwarePaginator['total_members']),
        ];
    }

    public function fetchMembersDetails(Request $request): array
    {
        $filterData = [
            'select_date' => $request->get('select_date'),
            'location_id' => $request->get('select_location_id'),
        ];

        session('admin_company_id');

        $lengthAwarePaginator = $this->memberQueries->fetchMemberDetails($filterData, session('admin_company_id'));

        return [
            'data' => MemberDetailsReportResource::collection($lengthAwarePaginator),
        ];
    }

    public function exportMembersReport(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'location_ids' => $request->get('location_ids'),
            'date_range' => $request->get('date_range'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $members = $this->memberQueries->getMembersReportForExport($filterData, session('admin_company_id'));

        return Excel::download(new MemberReportExport($members, $filteredColumns), $filename);
    }

    public function printMembers(Request $request): string
    {
        $membersData = [];
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'location_ids' => $request->get('location_ids'),
            'date_range' => $request->get('date_range'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $companyId = session('admin_company_id');
        $members = $this->memberQueries->getMembersReportForExport($filterData, $companyId);

        $printPdfHeaderFilterService = resolve(PrintPdfHeaderFilterService::class);
        $filterHeaderData = $printPdfHeaderFilterService->buildFilterData($filterData);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $memberService = resolve(MemberService::class);
        $membersData['details'] = $memberService->preparedMemberReportRecords($members, $filteredColumns);

        $dateRangeFrom = '';
        $dateRangeTo = '';

        if (null !== $filterData['date_range']) {
            /** @var Carbon $dateRangeFromFormat */
            $dateRangeFromFormat = Carbon::createFromFormat('Y-m-d', $filterData['date_range'][0]);
            $dateRangeFrom = $dateRangeFromFormat->format('d-m-Y');

            /** @var Carbon $dateRangeToFormat */
            $dateRangeToFormat = Carbon::createFromFormat('Y-m-d', $filterData['date_range'][1]);
            $dateRangeTo = $dateRangeToFormat->format('d-m-Y');
        }

        return view('prints.member_report', [
            'memberDetails' => $membersData['details'],
            'company' => $company,
            'columns' => $filteredColumns,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'dateRangeFrom' => $dateRangeFrom,
            'dateRangeTo' => $dateRangeTo,
            'filter_header_data' => $filterHeaderData,
        ])->render();
    }
}
