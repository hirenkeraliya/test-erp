<?php

declare(strict_types=1);

use App\Domains\SuperAdmin\DataObjects\ChangePasswordData;
use App\Domains\SuperAdmin\DataObjects\SuperAdminChangePasswordData;
use App\Domains\SuperAdmin\DataObjects\SuperAdminData;
use App\Domains\SuperAdmin\SuperAdminQueries;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->superAdmin = SuperAdmin::factory()->create();
    loginSuperAdmin($this->superAdmin);
    $this->superAdminQueries = new SuperAdminQueries();
});

test('super admin can change password', function (): void {
    $authenticatable = Auth::guard('super_admin')->user();

    $this->superAdminQueries->changePassword($authenticatable, new ChangePasswordData('123456', '1234567'));

    $this->superAdmin->refresh();
    $this->assertTrue(Hash::check('1234567', $this->superAdmin->password));
});

test('getByUsername method return super admin', function (): void {
    $response = $this->superAdminQueries->getByUsername($this->superAdmin->username);
    expect($response->toArray())
        ->toHaveKey('id', $this->superAdmin->id)
        ->toHaveKey('username', $this->superAdmin->username);

    $response = $this->superAdminQueries->getByUsername('13154');
    $this->assertNull($response);
});

test('getAll method return all super admin', function (): void {
    $response = $this->superAdminQueries->getAll();
    expect($response->first()->toArray())
        ->toHaveKey('id', $this->superAdmin->id)
        ->toHaveKey('email', $this->superAdmin->email)
        ->toHaveKey('name', $this->superAdmin->name);
});

test('super admin can be fetch', function (): void {
    $response = $this->superAdminQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => 'asc',
        'per_page' => 15,
    ]);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('username', $this->superAdmin->username);
});

test('New super-admin can be added', function (): void {
    $this->superAdminQueries->addNew(
        new SuperAdminData('superAdminXYZ', 'ABC123XYZ', '12345678', 'abcd@gmail.com', null, null, null)
    );

    $this->assertDatabaseHas('super_admins', [
        'username' => 'superAdminXYZ',
    ]);
});

test('A super admin can update password', function (): void {
    $requestParameter = [
        'new_password' => '123456789',
    ];

    $superAdmin = SuperAdmin::factory()->create([
        'password' => bcrypt('123456'),
    ]);

    $this->superAdminQueries->superAdminChangePassword(
        $superAdmin,
        new SuperAdminChangePasswordData(...$requestParameter)
    );

    $superAdmin->refresh();
    $this->assertTrue(Hash::check($requestParameter['new_password'], $superAdmin->password));
});

test('A super admin can be updated', function (): void {
    $this->superAdminQueries->update(
        new SuperAdminData('SuperAdmin', 'abcde', 'abcdef', 'abcde@gmail.com', null, null, null),
        $this->superAdmin
    );

    $this->assertDatabaseHas('super_admins', [
        'username' => 'SuperAdmin',
    ]);
    $this->superAdmin->refresh();
});
