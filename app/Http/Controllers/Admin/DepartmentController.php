<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\Enums\CommissionTypes;
use App\Domains\Department\DataObjects\DepartmentData;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Department\Exports\DepartmentExport;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentQueries $departmentQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('departments/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('department'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchDepartments(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->departmentQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getByIdWithPromoterCommissionDetails(session('admin_company_id'));

        return Inertia::render('departments/Manage', [
            'company' => $company,
            'commissionTypes' => CommissionTypes::toArray(),
            'discountTypes' => DiscountTypes::getFormattedArrayForStaticUse(),
        ]);
    }

    public function store(DepartmentData $departmentData): RedirectResponse
    {
        $this->departmentQueries->addNew($departmentData, session('admin_company_id'));

        return to_route('admin.departments.index')->with('success', 'The department has been added successfully.');
    }

    public function storeAndReturn(DepartmentData $departmentData): array
    {
        $department = $this->departmentQueries->addNew($departmentData, session('admin_company_id'));

        return [
            'department' => $department,
        ];
    }

    public function edit(int $departmentId): Response
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getByIdWithPromoterCommissionDetails(session('admin_company_id'));
        $department = $this->departmentQueries->getById($departmentId, session('admin_company_id'));

        return Inertia::render('departments/Manage', [
            'department' => $department,
            'company' => $company,
            'commissionTypes' => CommissionTypes::toArray(),
            'discountTypes' => DiscountTypes::getFormattedArrayForStaticUse(),
        ]);
    }

    public function update(DepartmentData $departmentData, int $departmentId): RedirectResponse
    {
        $departmentData->discount_type === DiscountTypes::PERCENTAGE->value ? $departmentData->flat_commission = 0 : $departmentData->commission_percentage = 0;

        $this->departmentQueries->update($departmentData, $departmentId, session('admin_company_id'));

        return to_route('admin.departments.index')->with('success', 'Department updated successfully.');
    }

    /**
     * @return array<string, Collection>
     */
    public function getFilteredDepartments(Request $request): array
    {
        return [
            'departments' => $this->departmentQueries->getFilteredDepartmentsByCompanyId(
                $request->input('search_text'),
                session('admin_company_id')
            ),
        ];
    }

    /**
     * @return array<string, \Illuminate\Database\Eloquent\Collection>
     */
    public function getDepartmentsList(): array
    {
        return [
            'departments' => $this->departmentQueries->getWithBasicColumns(session('admin_company_id')),
        ];
    }

    public function exportDepartments(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $departments = $this->departmentQueries->getDepartmentsExport($filterData, session('admin_company_id'));

        return Excel::download(new DepartmentExport($departments), $filename);
    }

    public function getDepartmentSalesSummary(Request $request): array
    {
        $filterData = $request->all();
        $filterData['type'] = (int) $filterData['type'];
        $departments = $this->departmentQueries->getDepartmentSalesSummary($filterData, session('admin_company_id'));

        return [
            'departments' => $departments,
            'total_sales' => $departments->sum('total_sales'),
            'total_units_sold' => $departments->sum('total_units_sold'),
        ];
    }
}
