<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Domains\Company\CompanyQueries;
use App\Domains\EmployeeGroup\DataObjects\SuperAdminEmployeeGroupData;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\EmployeeGroup\Enums\LimitResetDays;
use App\Domains\EmployeeGroup\Enums\LimitResetTypes;
use App\Domains\EmployeeGroup\Enums\PurchaseLimitTypes;
use App\Domains\EmployeeGroup\Exports\SuperAdminEmployeeGroupExport;
use App\Domains\EmployeeGroup\Resources\SuperAdminEmployeeGroupListResource;
use App\Http\Controllers\Controller;
use App\Models\SuperAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeGroupController extends Controller
{
    public function __construct(
        protected EmployeeGroupQueries $employeeGroupQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('employee_groups/Index');
    }

    /**
     * @return array<string, AnonymousResourceCollection>|array<string, int>
     */
    public function fetchEmployeeGroups(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->employeeGroupQueries->listQueryForSuperAdmin($filterData);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => SuperAdminEmployeeGroupListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        $companyQueries = new CompanyQueries();

        return Inertia::render('employee_groups/Manage', [
            'purchaseLimitTypes' => PurchaseLimitTypes::formattedForSelection(),
            'limitResetTypes' => LimitResetTypes::formattedForSelection(),
            'companies' => $companyQueries->getWithBasicColumns(),
            'limitResetDays' => LimitResetDays::formattedForSelection(),
            'staticDetails' => $this->getStaticDetails(),
        ]);
    }

    public function store(SuperAdminEmployeeGroupData $employeeGroupData): RedirectResponse
    {
        /** @var SuperAdmin $superAdmin */
        $superAdmin = Auth::guard('super_admin')->user();

        $this->employeeGroupQueries->addForSuperAdmin($employeeGroupData, $superAdmin);

        return to_route('super_admin.employee_groups.index')->with('success', 'Employee group added successfully.');
    }

    public function edit(int $employeeGroupId): Response
    {
        $employeeGroup = $this->employeeGroupQueries->getByIdWithoutCompanyFilter($employeeGroupId);
        $companyQueries = new CompanyQueries();

        return Inertia::render('employee_groups/Manage', [
            'employeeGroup' => $employeeGroup,
            'purchaseLimitTypes' => PurchaseLimitTypes::formattedForSelection(),
            'limitResetTypes' => LimitResetTypes::formattedForSelection(),
            'companies' => $companyQueries->getWithBasicColumns(),
            'limitResetDays' => LimitResetDays::formattedForSelection(),
            'staticDetails' => $this->getStaticDetails(),
        ]);
    }

    public function update(SuperAdminEmployeeGroupData $employeeGroupData, int $employeeGroupId): RedirectResponse
    {
        $this->employeeGroupQueries->updateForSuperAdmin($employeeGroupData, $employeeGroupId);

        return to_route('super_admin.employee_groups.index')->with('success', 'Employee group updated successfully.');
    }

    public function exportEmployeeGroups(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $employeeGroups = $this->employeeGroupQueries->getSuperAdminEmployeeGroupsExport($filterData);

        return Excel::download(new SuperAdminEmployeeGroupExport($employeeGroups), $filename);
    }

    /**
     * @return array<string, Collection>
     */
    public function getEmployeeGroupByCompanyId(int $companyId): array
    {
        $employeeGroups = $this->employeeGroupQueries->getByCompanyId($companyId);

        $employeeGroups->transform(fn ($employeeGroup): array => [
            'id' => $employeeGroup->id,
            'name' => $employeeGroup->name,
        ]);

        return [
            'data' => $employeeGroups,
        ];
    }

    private function getStaticDetails(): array
    {
        return [
            'limit_reset_type_by_days' => LimitResetTypes::BY_DAYS,
            'limit_reset_type_by_week' => LimitResetTypes::BY_WEEK,
            'limit_reset_type_by_month' => LimitResetTypes::BY_MONTH,
            'purchase_limit_by_items' => PurchaseLimitTypes::BY_ITEMS,
            'purchase_limit_by_amount' => PurchaseLimitTypes::BY_AMOUNT,
            'purchase_limit_by_sale' => PurchaseLimitTypes::BY_SALE,
        ];
    }
}
