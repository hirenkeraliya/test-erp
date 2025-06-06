<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterCommission\Exports\PromoterCommissionDetailsExport;
use App\Domains\PromoterCommission\Exports\PromoterCommissionExport;
use App\Domains\PromoterCommission\PromoterCommissionQueries;
use App\Domains\PromoterCommission\Resources\PromoterCommissionResource;
use App\Domains\PromoterCommission\Services\PromoterCommisionPrintService;
use App\Domains\PromoterCommissionUpdate\PromoterCommissionUpdateQueries;
use App\Domains\PromoterCommissionUpdate\Resources\PromoterCommissionDetailsListResource;
use App\Domains\PromoterGroup\PromoterGroupQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PromoterCommissionController extends Controller
{
    public function __construct(
        protected PromoterCommissionQueries $promoterCommissionQueries,
        protected PromoterQueries $promoterQueries,
        protected CompanyQueries $companyQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('admin_company_id');

        $promoterGroupQueries = resolve(PromoterGroupQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        return Inertia::render('reports/promoter_commission/Index', [
            'locations' => $locations,
            'commissionTypes' => CommissionTypes::toArray(),
            'company' => $this->companyQueries->getByIdWithPromoterCommissionDetails($companyId),
            'promoterGroups' => $promoterGroupQueries->getPromoterGroupByCompanyId(session('admin_company_id')),
            'exportPermission' => PermissionList::getExportPermissionName('commission'),
            'helpCenterMessages' => 'Only regular, complete credit and complete layaway sales are considered for the Promoters commission report with  sales and commission offering advanced filters, search options, and seamless export capabilities for detailed analysis and insights.',
        ]);
    }

    public function fetCommissionsByPromoters(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'month_range' => $request->get('month_range'),
            'promoter_ids' => $request->get('promoter_ids'),
            'location_ids' => $request->get('location_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'department_ids' => $request->get('department_ids'),
            'group_ids' => $request->get('group_ids'),
        ];

        [$promoterCommissions, $totalAmount, $totalCommissionAmount] = $this->promoterCommissionQueries->getPaginatedCommissionByPromotersForMonth(
            $filterData,
            session('admin_company_id'),
        );

        return [
            'total_records' => $promoterCommissions->total(),
            'data' => PromoterCommissionResource::collection($promoterCommissions->getCollection()),
            'total_sales_amount' => $totalAmount ?? 0,
            'commission_amount' => $totalCommissionAmount ?? 0,
        ];
    }

    public function exportCommissionByPromoters(string $filename, Request $request): BinaryFileResponse
    {
        $companyId = session('admin_company_id');

        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'month_range' => $request->get('month_range'),
            'promoter_ids' => $request->get('promoter_ids'),
            'location_ids' => $request->get('location_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'department_ids' => $request->get('department_ids'),
            'group_ids' => $request->get('group_ids'),
            'export_columns' => $request->get('export_columns'),
        ];

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $promoterCommissions = $this->promoterCommissionQueries->getPaginatedCommissionByPromotersForMonthForExport(
            $filterData,
            $companyId,
        );

        $company = $this->companyQueries->getByIdWithPromoterCommissionDetails($companyId);

        return Excel::download(
            new PromoterCommissionExport($promoterCommissions, $company, $filteredColumns),
            $filename
        );
    }

    /**
     * @return array<string, Collection>
     */
    public function getLocationWisePromoters(Request $request): array
    {
        $locationIds = $request->get('location_ids');

        $promoters = $this->promoterQueries->getPromoterByLocations($locationIds);

        $promoters->transform(function ($promoter): array {
            /** @var Employee $employee */
            $employee = $promoter->employee;

            return [
                'id' => $promoter->id,
                'name' => $employee->getFullName(),
            ];
        });

        return [
            'promoters' => $promoters,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchPromoterCommissionDetails(Request $request, int $promoterCommissionId): array
    {
        $filterData = [
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'search_text' => $request->get('search_text'),
            'promoter_ids' => $request->get('promoter_ids'),
            'location_ids' => $request->get('location_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'department_ids' => $request->get('department_ids'),
        ];

        $promoterCommissionUpdateQueries = resolve(PromoterCommissionUpdateQueries::class);

        $lengthAwarePaginator = $promoterCommissionUpdateQueries->getPaginatedCommissionDetailsByPromoter(
            $filterData,
            $promoterCommissionId
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => PromoterCommissionDetailsListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function exportPromoterCommissionDetails(
        int $promoterCommissionId,
        string $filename,
        Request $request
    ): BinaryFileResponse {
        $promoterCommissionUpdateQueries = resolve(PromoterCommissionUpdateQueries::class);

        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'department_ids' => $request->get('department_ids'),
        ];

        $commissionDetails = $promoterCommissionUpdateQueries->getPromoterCommissionDetailsForExport(
            $filterData,
            $promoterCommissionId
        );

        return Excel::download(new PromoterCommissionDetailsExport($commissionDetails), $filename);
    }

    public function printPromoterCommissionDetails(int $promoterCommissionId, Request $request): string
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'department_ids' => $request->get('department_ids'),
        ];
        $promoterCommisionPrintService = resolve(PromoterCommisionPrintService::class);

        return $promoterCommisionPrintService->printPromoterCommissionDetails($filterData, $promoterCommissionId);
    }

    public function printPromoterCommission(Request $request): string
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'month_range' => $request->get('month_range'),
            'promoter_ids' => $request->get('promoter_ids'),
            'location_ids' => $request->get('location_ids'),
            'brand_ids' => $request->get('brand_ids'),
            'department_ids' => $request->get('department_ids'),
            'group_ids' => $request->get('group_ids'),
        ];

        $filterData['export_columns'] = $request->get('export_columns');

        /** @var array $exportColumns */
        $exportColumns = $filterData['export_columns'];

        $filteredColumns = collect($exportColumns)->pluck('key');

        $promoterCommisionPrintService = resolve(PromoterCommisionPrintService::class);

        return $promoterCommisionPrintService->printPromoterCommission(
            $filterData,
            session('admin_company_id'),
            $filteredColumns
        );
    }
}
