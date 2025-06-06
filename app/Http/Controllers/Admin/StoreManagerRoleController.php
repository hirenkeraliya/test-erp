<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domains\Permission\Services\StoreManagerPermissionModuleService;
use App\Domains\Role\RoleQueries;
use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreManagerRoleController extends Controller
{
    public function __construct(
        protected RoleQueries $roleQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('store_manager_roles/Index');
    }

    public function fetch(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];
        $guardName = 'store_manager';
        $lengthAwarePaginator = $this->roleQueries->getPaginatedRoles($filterData, $guardName);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('store_manager_roles/Manage', [
            'permissions' => StoreManagerPermissionModuleService::preparedPermissionModules(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'unique:roles,name,NULL,id,guard_name,store_manager'],
            'permissions' => ['required', 'array'],
        ]);

        $validatedData['guard_name'] = 'store_manager';
        $this->roleQueries->store($validatedData);

        return to_route('admin.store_manager_roles.index')->with('success', 'Roles & Permissions added successfully.');
    }

    public function edit(int $roleId): Response
    {
        /** @var Role $role */
        $role = $this->roleQueries->getById($roleId);

        return Inertia::render('store_manager_roles/Manage', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => StoreManagerPermissionModuleService::preparedEditRecord($role),
            ],
            'permissions' => StoreManagerPermissionModuleService::preparedPermissionModules(),
        ]);
    }

    public function update(Request $request, int $roleId): RedirectResponse
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'unique:roles,name,' . $roleId . ',id,guard_name,store_manager'],
            'permissions' => ['required', 'array'],
        ]);

        $validatedData['guard_name'] = 'store_manager';
        $this->roleQueries->update($validatedData, $roleId);

        return to_route('admin.store_manager_roles.index')->with(
            'success',
            'Roles & Permissions updated successfully.'
        );
    }

    public function clone(int $roleId): Response
    {
        /** @var Role $cloneRole */
        $cloneRole = $this->roleQueries->getById($roleId);

        return Inertia::render('store_manager_roles/Manage', [
            'cloneRole' => [
                'id' => $cloneRole->id,
                'name' => $cloneRole->name,
                'permissions' => StoreManagerPermissionModuleService::preparedEditRecord($cloneRole),
            ],
            'permissions' => StoreManagerPermissionModuleService::preparedPermissionModules(),
        ]);
    }
}
