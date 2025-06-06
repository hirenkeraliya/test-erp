<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Role\RoleQueries;
use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->role = Role::factory()->create([
        'name' => 'first_role',
        'guard_name' => 'admin',
    ]);

    $this->roleQueries = new RoleQueries();
});

test('Paginated Roles can be fetched', function (): void {
    $filterData = [
        'search_text' => null,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'per_page' => '10',
    ];

    $response = $this->roleQueries->getPaginatedRoles($filterData, 'admin');

    $this->assertEquals(1, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('name', $this->role->name);
});

test('Roles can be store', function (): void {
    $newPermission = 'first_permission';

    $validatedData = [
        'name' => 'role_name',
        'permissions' => [$newPermission],
        'guard_name' => 'admin',
    ];

    $this->roleQueries->store($validatedData);

    $this->assertDatabaseHas('roles', [
        'name' => $validatedData['name'],
        'guard_name' => 'admin',
    ]);

    $this->assertDatabaseHas('permissions', [
        'name' => $newPermission,
        'guard_name' => 'admin',
    ]);

    $permissionTwo = Permission::where('name', $newPermission)->first();
    $role = Role::where('name', $validatedData['name'])->first();

    $this->assertDatabaseHas('role_has_permissions', [
        'permission_id' => $permissionTwo->id,
        'role_id' => $role->id,
    ]);
});

test('A Role can be fetched with permissions', function (): void {
    $permission = Permission::factory()->create();
    $this->role->syncPermissions($permission->name);

    $response = $this->roleQueries->getById($this->role->id);

    expect($response->toArray())
        ->toHaveKey('name', $this->role->name)
        ->toHaveKey('permissions');
});

test('A Role can be update with permissions', function (): void {
    $newPermission = 'first_permissions';
    $validatedData = [
        'name' => $this->role->name,
        'permissions' => [$newPermission],
        'guard_name' => 'admin',
    ];

    $permission = Permission::factory()->create([
        'name' => 'first_permission',
        'guard_name' => 'admin',
    ]);
    $this->role->syncPermissions($permission->name);

    $admin = Admin::factory()->create();

    DB::table('model_has_roles')->insert([
        'role_id' => $this->role->id,
        'model_type' => ModelMapping::ADMIN->name,
        'model_id' => $admin->id,
    ]);

    $this->roleQueries->update($validatedData, $this->role->id);

    $this->assertDatabaseHas('roles', [
        'id' => $this->role->id,
        'name' => $validatedData['name'],
        'guard_name' => 'admin',
    ]);

    $this->assertDatabaseHas('permissions', [
        'name' => $newPermission,
        'guard_name' => 'admin',
    ]);

    $permissionTwo = Permission::where('name', $newPermission)->first();
    $this->assertDatabaseHas('role_has_permissions', [
        'permission_id' => $permissionTwo->id,
        'role_id' => $this->role->id,
    ]);
});

test('Roles can be fetched', function (): void {
    $response = $this->roleQueries->getRoles('admin');

    expect($response->first()->toArray())
        ->toHaveKey('name', $this->role->name)
        ->toHaveKey('guard_name', $this->role->guard_name);
});

test('getIdAndNameByNames method returns roles by name', function (): void {
    $role = Role::factory()->create([
        'name' => 'first_role',
        'guard_name' => 'store_manager',
    ]);

    $response = $this->roleQueries->getIdAndNameByNames([$role->name], 'store_manager');

    expect($response->first()->toArray())
        ->toHaveKey('id', $role->id)
        ->toHaveKey('name', $role->name);
});
