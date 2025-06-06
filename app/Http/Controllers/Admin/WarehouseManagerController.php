<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Permission\Enums\PermissionList;
use App\Domains\Role\RoleQueries;
use App\Domains\WarehouseManager\DataObjects\ChangePasswordData;
use App\Domains\WarehouseManager\DataObjects\WarehouseManagerData;
use App\Domains\WarehouseManager\Exports\WarehouseManagerExport;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WarehouseManagerController extends Controller
{
    public function __construct(
        protected WarehouseManagerQueries $warehouseManagerQueries
    ) {
    }

    public function index(): Response
    {
        $companyId = session('admin_company_id');

        $locationQueries = resolve(LocationQueries::class);

        $locations = $locationQueries->getWithBasicColumnsOfWarehouse($companyId);

        return Inertia::render('warehouse_managers/Index', [
            'locations' => $locations,
            'exportPermission' => PermissionList::getExportPermissionName('warehouse_manager'),
        ]);
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchWarehouseManagers(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
        ];

        $lengthAwarePaginator = $this->warehouseManagerQueries->listQuery($filterData, session('admin_company_id'));

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        [$locations, $employees] = $this->fetchCommonRecords(session('admin_company_id'));
        $roleQueries = resolve(RoleQueries::class);

        return Inertia::render('warehouse_managers/Manage', [
            'locations' => $locations,
            'employees' => $employees,
            'roles' => $roleQueries->getRoles('warehouse_manager'),
        ]);
    }

    public function store(WarehouseManagerData $warehouseManagerData): RedirectResponse
    {
        $this->validateSelectedRecordsWithCompany(session('admin_company_id'), $warehouseManagerData);

        $this->warehouseManagerQueries->addNew($warehouseManagerData);

        return to_route('admin.warehouse_managers.index')
            ->with('success', 'Warehouse Manager added successfully.');
    }

    public function edit(int $warehouseManagerId): Response
    {
        [$locations, $employees] = $this->fetchCommonRecords(session('admin_company_id'));
        $roleQueries = resolve(RoleQueries::class);

        return Inertia::render('warehouse_managers/Manage', [
            'warehouseManager' => $this->warehouseManagerQueries->getByIdWithWarehouses(
                $warehouseManagerId,
                session('admin_company_id')
            ),
            'locations' => $locations,
            'employees' => $employees,
            'roles' => $roleQueries->getRoles('warehouse_manager'),
        ]);
    }

    public function update(WarehouseManagerData $warehouseManagerData, int $warehouseManagerId): RedirectResponse
    {
        $this->validateSelectedRecordsWithCompany(session('admin_company_id'), $warehouseManagerData);

        $this->warehouseManagerQueries->update($warehouseManagerData, $warehouseManagerId, session('admin_company_id'));

        return to_route('admin.warehouse_managers.index')
            ->with('success', 'Warehouse Manager updated successfully.');
    }

    public function changePassword(int $warehouseManagerId): Response
    {
        return Inertia::render('warehouse_managers/ChangePassword', [
            'warehouseManagerId' => $warehouseManagerId,
        ]);
    }

    public function updatePassword(ChangePasswordData $changePasswordData, int $warehouseManagerId): RedirectResponse
    {
        $warehouseManager = $this->warehouseManagerQueries->getById($warehouseManagerId, session('admin_company_id'));

        $this->warehouseManagerQueries->changePassword($warehouseManager, $changePasswordData);

        return to_route('admin.warehouse_managers.index')
            ->with('success', 'Password updated successfully.');
    }

    public function exportWarehouseManagers(string $filename, Request $request): BinaryFileResponse
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
            'location_ids' => $request->get('location_ids'),
        ];

        $warehouseManagers = $this->warehouseManagerQueries->getWarehouseManagersExport(
            $filterData,
            session('admin_company_id')
        );

        return Excel::download(new WarehouseManagerExport($warehouseManagers), $filename);
    }

    /**
     * @return mixed[]
     */
    private function fetchCommonRecords(int $companyId): array
    {
        $locationQueries = resolve(LocationQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);

        $locations = $locationQueries->getWithBasicColumnsOfWarehouse($companyId);

        $employees = $employeeQueries->getFormattedEmployeesOf($companyId);

        return [$locations, $employees];
    }

    private function validateSelectedRecordsWithCompany(
        int $companyId,
        WarehouseManagerData $warehouseManagerData
    ): void {
        $locationQueries = resolve(LocationQueries::class);

        $allWarehousesExist = $locationQueries->doAllWarehousesExist($companyId, $warehouseManagerData->location_ids);

        if (! $allWarehousesExist) {
            throw new RedirectWithErrorException(
                'admin.warehouse_managers.index',
                'One of the selected locations does not match the current company.'
            );
        }
    }
}
