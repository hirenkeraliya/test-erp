<?php

declare(strict_types=1);

namespace App\Domains\Role;

use App\Domains\Permission\PermissionQueries;
use App\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RoleQueries
{
    public function getPaginatedRoles(array $filterData, string $guardName): LengthAwarePaginator
    {
        return Role::select('id', 'name')
            ->where('guard_name', $guardName)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('name', 'like', '%' . $filterData['search_text'] . '%');
                });
            })
            ->paginate($filterData['per_page']);
    }

    public function store(array $validatedData): void
    {
        $role = Role::create([
            'name' => $validatedData['name'],
            'guard_name' => $validatedData['guard_name'],
        ]);

        /** @var array $permissions */
        $permissions = $validatedData['permissions'];

        $permissionQueries = resolve(PermissionQueries::class);
        $permissionQueries->addNew(collect($permissions), $validatedData['guard_name']);

        $role->syncPermissions(collect($permissions));
    }

    public function getById(int $roleId): Role
    {
        $permissionQueries = resolve(PermissionQueries::class);

        return Role::with('permissions:' . $permissionQueries->getBasicColumns())
            ->findOrFail($roleId);
    }

    public function update(array $validatedData, int $roleId): void
    {
        /** @var array $permissions */
        $permissions = $validatedData['permissions'];
        $role = Role::select('id', 'name', 'guard_name')->findOrFail($roleId);
        $role->name = $validatedData['name'];
        $role->save();

        $permissionQueries = resolve(PermissionQueries::class);
        $permissionQueries->addNew(collect($permissions), $validatedData['guard_name']);

        $role->syncPermissions($permissions);
    }

    public function getRoles(string $guardName): Collection
    {
        return Role::select('id', 'name', 'guard_name')
            ->where('guard_name', $guardName)
            ->get();
    }

    public function getBasicColumns(): string
    {
        return 'id,name';
    }

    public function getIdAndNameByNames(array $roleNames, string $guardName): Collection
    {
        return Role::select('id', 'name', 'guard_name')
            ->whereInCaseSensitive('name', $roleNames)
            ->where('guard_name', $guardName)
            ->get();
    }

    public function doRoleNamesExists(array $roleNames, string $guardName): bool
    {
        if ([] === $roleNames) {
            return false;
        }

        $filteredRoleNames = array_unique(array_filter($roleNames));
        if ([] === $filteredRoleNames) {
            return false;
        }

        $totalRecords = Role::whereInCaseSensitive('name', $filteredRoleNames)
            ->where('guard_name', $guardName)
            ->count();

        return count($filteredRoleNames) === $totalRecords;
    }
}
