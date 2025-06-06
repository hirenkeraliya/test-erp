<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\EmployeeGroup\DataObjects\EmployeeGroupData;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\EmployeeGroup\Enums\LimitResetDays;
use App\Domains\EmployeeGroup\Enums\LimitResetTypes;
use App\Domains\EmployeeGroup\Enums\PurchaseLimitTypes;
use App\Domains\EmployeeGroup\Exports\EmployeeGroupExport;
use App\Domains\EmployeeGroup\Resources\EmployeeGroupListResource;
use App\Domains\Permission\Enums\PermissionList;
use App\Http\Controllers\Controller;
use App\Models\StoreManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
        return Inertia::render('employee_groups/Index', [
            'exportPermission' => PermissionList::getExportPermissionName('employee_group'),
        ]);
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

        $lengthAwarePaginator = $this->employeeGroupQueries->listQuery(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => EmployeeGroupListResource::collection($lengthAwarePaginator->getCollection()),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('employee_groups/Manage', [
            'purchaseLimitTypes' => PurchaseLimitTypes::formattedForSelection(),
            'limitResetTypes' => LimitResetTypes::formattedForSelection(),
            'limitResetDays' => LimitResetDays::formattedForSelection(),
            'staticDetails' => $this->getStaticDetails(),
        ]);
    }

    public function store(EmployeeGroupData $employeeGroupData, Request $request): RedirectResponse
    {
        /** @var StoreManager $user */
        $user = $request->user();

        $this->employeeGroupQueries->addNew(
            $employeeGroupData,
            session('store_manager_selected_location_company_id'),
            $user
        );

        return to_route('store_manager.employee_groups.index')->with('success', 'Employee group added successfully.');
    }

    public function edit(int $employeeGroupId): Response
    {
        $employeeGroup = $this->employeeGroupQueries->getById(
            $employeeGroupId,
            session('store_manager_selected_location_company_id')
        );

        return Inertia::render('employee_groups/Manage', [
            'employeeGroup' => $employeeGroup,
            'purchaseLimitTypes' => PurchaseLimitTypes::formattedForSelection(),
            'limitResetTypes' => LimitResetTypes::formattedForSelection(),
            'limitResetDays' => LimitResetDays::formattedForSelection(),
            'staticDetails' => $this->getStaticDetails(),
        ]);
    }

    public function update(EmployeeGroupData $employeeGroupData, int $employeeGroupId): RedirectResponse
    {
        $this->employeeGroupQueries->update(
            $employeeGroupData,
            $employeeGroupId,
            session('store_manager_selected_location_company_id')
        );

        return to_route('store_manager.employee_groups.index')->with('success', 'Employee group updated successfully.');
    }

    public function exportEmployeeGroups(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $employeeGroups = $this->employeeGroupQueries->getEmployeeGroupsExport(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return Excel::download(new EmployeeGroupExport($employeeGroups), $filename);
    }

    /**
     * @return array<string, mixed>
     */
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
