<?php

declare(strict_types=1);

use App\Domains\Admin\AdminQueries;
use App\Domains\Admin\DataObjects\AdminChangePasswordData;
use App\Domains\Admin\DataObjects\AdminData;
use App\Domains\Common\Enums\ModelMapping;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create([
        'name' => 'COMPANYA',
    ]);

    $this->companyB = Company::factory()->create([
        'name' => 'COMPANYB',
        'deleted_at' => now()->format('Y-m-d h:i:s'),
    ]);

    $this->employeeA = Employee::factory()->create([
        'company_id' => $this->companyA->id,
        'first_name' => 'DEF',
        'last_name' => 'JKL',
        'email' => 'abc@gmail.com',
    ]);

    $this->employeeB = Employee::factory()->create([
        'company_id' => $this->companyB->id,
        'first_name' => 'GHI',
        'last_name' => 'MNO',
        'email' => 'xyz@gmail.com',
    ]);

    $this->adminA = Admin::factory()->create([
        'id' => 1,
        'username' => 'ABCD',
        'employee_id' => $this->employeeA->id,
    ]);

    $this->adminB = Admin::factory()->create([
        'username' => 'XYZ',
        'employee_id' => $this->employeeB->id,
    ]);

    $this->adminQueries = new AdminQueries();
});

test('Admins can be searched', function (): void {
    $response = $this->adminQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('username', $this->adminA->username)
        ->toHaveKey('employee_id', $this->adminA->employee_id)
        ->toHaveKey('employee.first_name', $this->adminA->employee->first_name)
        ->toHaveKey('employee.company.name', $this->adminA->employee->company->name);
});

test('Admins can be searched but exclude deleted company', function (): void {
    $response = $this->adminQueries->listQuery([
        'search_text' => 'XYZ',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ]);

    $this->assertEmpty($response);
});

test('Admins can be sorted by username', function (): void {
    $response = $this->adminQueries->listQuery([
        'search_text' => null,
        'sort_by' => 'username',
        'sort_direction' => 'asc',
        'per_page' => 15,
    ]);

    $this->assertEquals(1, $response->total());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('username', $this->adminA->username)
        ->toHaveKey('employee_id', $this->adminA->employee_id);
});

test('Admins are returned as per page', function (): void {
    $response = $this->adminQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
    ]);

    $this->assertEquals(1, $response->count());
    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('username', $this->adminA->username)
        ->toHaveKey('employee_id', $this->adminA->employee_id);
});

test('New admin can be added', function (): void {
    $employeeC = Employee::factory()->create();
    $role = Role::factory()->create();

    $this->adminQueries->addNew(new AdminData('adminXYZ', $employeeC->id, 'ABC123XYZ', [$role->id], null, null, null));

    $this->assertDatabaseHas('admins', [
        'username' => 'adminXYZ',
        'employee_id' => $employeeC->id,
    ]);

    $this->assertDatabaseHas('model_has_roles', [
        'role_id' => $role->id,
        'model_type' => ModelMapping::ADMIN->name,
    ]);

    $admin = Admin::query()->whereCaseSensitive('username', 'adminXYZ')->first();
    $this->assertTrue(Hash::check('ABC123XYZ', $admin->password));
});

test('A admin can be fetched', function (): void {
    $response = $this->adminQueries->getByIdWithEmployee($this->adminA->id);
    expect($response->toArray())
        ->toHaveKey('username', $this->adminA->username)
        ->toHaveKey('employee_id', $this->employeeA->id)
        ->toHaveKey('employee.company_id', $this->companyA->id);
});

test('A admin get Id', function (): void {
    $response = $this->adminQueries->getById($this->adminA->id);
    expect($response->toArray())
        ->toHaveKey('id', $this->adminA->id);
});

test('A admin can be updated', function (): void {
    $employeeC = Employee::factory()->create();
    $role = Role::factory()->create();

    $this->adminQueries->update(
        new AdminData('ADMINABC', $employeeC->id, '', [$role->id], null, null, null),
        $this->adminA->id
    );

    $this->assertDatabaseHas('admins', [
        'username' => 'ADMINABC',
        'employee_id' => $employeeC->id,
    ]);

    $this->assertDatabaseHas('model_has_roles', [
        'role_id' => $role->id,
        'model_type' => ModelMapping::ADMIN->name,
        'model_id' => $this->adminA->id,
    ]);

    $this->adminA->refresh();
    $this->assertTrue(Hash::check('123456', $this->adminA->password));
});

test('Admin can request forgot password email', function (): void {
    $this->assertDatabaseHas('admins', [
        'id' => $this->adminA->id,
        'forgot_password_token' => null,
        'forgot_password_token_expiration_at' => null,
    ]);

    $response = $this->adminQueries->fetchAdminByUsername($this->adminA->username);

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'username', 'employee_id', 'forgot_password_token_expiration_at']);
});

test('Admin can reset password', function (): void {
    $token = md5('adminTest@gmail.com' . now());

    $admin = Admin::factory()->create([
        'username' => 'AdminTest',
        'forgot_password_token' => $token,
        'forgot_password_token_expiration_at' => now()->addHour(),
    ]);

    $this->adminQueries->resetPassword($admin, 'ABCDEFGH');

    $this->assertDatabaseHas('admins', [
        'id' => $admin->id,
        'forgot_password_token' => null,
        'forgot_password_token_expiration_at' => null,
    ]);

    $admin->refresh();
    $this->assertTrue(Hash::check('ABCDEFGH', $admin->password));
});

test('Exception is thrown if reset password token is expired', function (): void {
    $token = md5('adminTest@gmail.com' . now());

    Admin::factory()->create([
        'username' => 'AdminTest',
        'forgot_password_token' => $token,
        'forgot_password_token_expiration_at' => now()->subHour(),
    ]);

    $admin = $this->adminQueries->checkResetPasswordToken($token);
})->throws(ModelNotFoundException::class);

test('Reset password token fetches the correct admin record', function (): void {
    $token = md5('adminTest@gmail.com' . now());

    Admin::factory()->create([
        'username' => 'AdminTest',
        'forgot_password_token' => $token,
        'forgot_password_token_expiration_at' => now()->addHour(),
    ]);

    $admin = $this->adminQueries->checkResetPasswordToken($token);

    expect($admin)
        ->username->toEqual('AdminTest')
        ->forgot_password_token->toEqual($token);
});

test('getAdminListByCompanyId method call then return admin lists by company id', function (): void {
    $response = $this->adminQueries->getAdminListByCompanyId($this->companyA->id);
    expect($response->first()->toArray())
        ->toHaveKey('employee_id', $this->adminA->employee_id)
        ->toHaveKey('employee.first_name', $this->adminA->employee->first_name);
});

test('A admin can update password', function (): void {
    $requestParameter = [
        'new_password' => '123456789',
    ];

    $admin = Admin::factory()->create([
        'password' => bcrypt('123456'),
    ]);

    $this->adminQueries->adminChangePassword($admin, new AdminChangePasswordData(...$requestParameter));

    $admin->refresh();
    $this->assertTrue(Hash::check($requestParameter['new_password'], $admin->password));
});

test('updateExternalLoginToken method set login token', function (): void {
    $this->adminQueries->updateExternalLoginToken($this->adminA->id, $this->companyA->id, $token = 'test1234');

    $this->assertDatabaseHas('admins', [
        'external_login_token' => $token,
    ]);
});

test('getByStaffIdAndCompanyId return admin', function (): void {
    $response = $this->adminQueries->getByStaffIdAndCompanyId($this->employeeA->staff_id, $this->companyA->id);

    expect($response->toArray())
        ->toHaveKey('username', $this->adminA->username)
        ->toHaveKey('employee_id', $this->adminA->employee_id);
});

test('getByIdAndExternalLoginToken return admin', function (): void {
    $this->adminA->external_login_token = '123456';
    $this->adminA->save();

    $response = $this->adminQueries->getByIdAndExternalLoginToken($this->adminA->id, '123456');

    expect($response->toArray())
        ->toHaveKey('username', $this->adminA->username)
        ->toHaveKey('employee_id', $this->adminA->employee_id)
        ->toHaveKey('employee');
});

test('getByCompanyIdOnlyId return admin ids', function (): void {
    $response = $this->adminQueries->getByCompanyIdOnlyId($this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('employee_id', $this->adminA->employee_id);
});

test('getAdminData return admin ids', function (): void {
    $response = $this->adminQueries->getAdminData($this->adminA->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->adminA->id);
});
