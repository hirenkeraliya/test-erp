<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Domains\Permission\Services\PermissionModuleService;
use App\Domains\Role\RoleQueries;
use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(
        protected RoleQueries $roleQueries
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('roles/Index');
    }

    public function fetch(Request $request): array
    {
        $filterData = [
            'search_text' => $request->get('search_text'),
            'sort_by' => $request->get('sort_by'),
            'sort_direction' => $request->get('sort_direction'),
            'per_page' => $request->get('per_page'),
        ];

        $guardName = 'admin';
        $lengthAwarePaginator = $this->roleQueries->getPaginatedRoles($filterData, $guardName);

        return [
            'total_records' => $lengthAwarePaginator->total(),
            'data' => $lengthAwarePaginator->getCollection(),
        ];
    }

    public function create(): Response
    {
        return Inertia::render('roles/Manage', [
            'permissions' => PermissionModuleService::preparedPermissionModules(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'unique:roles,name,NULL,id,guard_name,admin'],
            'permissions' => ['required', 'array'],
        ]);

        $validatedData['guard_name'] = 'admin';
        $this->roleQueries->store($validatedData);

        return to_route('super_admin.roles.index')->with('success', 'Roles & Permissions added successfully.');
    }

    public function edit(int $roleId): Response
    {
        /** @var Role $role */
        $role = $this->roleQueries->getById($roleId);

        return Inertia::render('roles/Manage', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => PermissionModuleService::preparedEditRecord($role),
            ],
            'permissions' => PermissionModuleService::preparedPermissionModules(),
        ]);
    }

    public function update(Request $request, int $roleId): RedirectResponse
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'unique:roles,name,' . $roleId . ',id,guard_name,admin'],
            'permissions' => ['required', 'array'],
        ]);

        $validatedData['guard_name'] = 'admin';
        $this->roleQueries->update($validatedData, $roleId);

        return to_route('super_admin.roles.index')->with('success', 'Roles & Permissions updated successfully.');
    }

    public function clone(int $roleId): Response
    {
        /** @var Role $cloneRole */
        $cloneRole = $this->roleQueries->getById($roleId);

        return Inertia::render('roles/Manage', [
            'cloneRole' => [
                'id' => $cloneRole->id,
                'name' => $cloneRole->name,
                'permissions' => PermissionModuleService::preparedEditRecord($cloneRole),
            ],
            'permissions' => PermissionModuleService::preparedPermissionModules(),
        ]);
    }
}
