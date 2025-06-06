<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Domains\Admin\AdminQueries;
use App\Domains\Admin\DataObjects\AdminChangePasswordData;
use App\Domains\Admin\DataObjects\AdminData;
use App\Domains\Company\CompanyQueries;
use App\Domains\Role\RoleQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function __construct(
        protected AdminQueries $adminQueries
    ) {
    }

    /**
     * @return array<string, Collection>|array<string, int>
     */
    public function fetchAdmins(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $lengthAwarePaginator = $this->adminQueries->listQuery($filterData);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        $companyQueries = new CompanyQueries();
        $roleQueries = new RoleQueries();

        return Inertia::render('admins/Manage', [
            'companies' => $companyQueries->getWithBasicColumns(),
            'roles' => $roleQueries->getRoles('admin'),
        ]);
    }

    public function store(AdminData $adminData): RedirectResponse
    {
        $this->adminQueries->addNew($adminData);

        return to_route('super_admin.admins.index')->with('success', 'Admin successfully added.');
    }

    public function edit(int $adminId, CompanyQueries $companyQueries): Response
    {
        $admin = $this->adminQueries->getByIdWithEmployee($adminId);
        $roleQueries = resolve(RoleQueries::class);

        return Inertia::render('admins/Manage', [
            'admin' => $admin,
            'companies' => $companyQueries->getWithBasicColumns(),
            'roles' => $roleQueries->getRoles('admin'),
        ]);
    }

    public function update(Request $request, AdminData $adminData, int $adminId): RedirectResponse
    {
        $admin = $this->adminQueries->getByIdWithEmployee($adminId);

        /** @var Employee $employee */
        $employee = $admin->employee;

        if ($employee->getCompanyId() !== $request->company_id) {
            throw new RedirectBackWithErrorException('Company of an existing admin cannot be changed.');
        }

        $this->adminQueries->update($adminData, $adminId);

        return to_route('super_admin.admins.index')->with('success', 'Admin updated successfully.');
    }

    public function changePassword(int $adminId): Response
    {
        return Inertia::render('admins/ChangePassword', [
            'adminId' => $adminId,
        ]);
    }

    public function updatePassword(AdminChangePasswordData $adminChangePasswordData, int $adminId): RedirectResponse
    {
        $admin = $this->adminQueries->getById($adminId);

        $this->adminQueries->adminChangePassword($admin, $adminChangePasswordData);

        return to_route('super_admin.admins.index')
            ->with('success', 'Password updated successfully.');
    }
}
