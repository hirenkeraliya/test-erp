<?php

declare(strict_types=1);

use App\Domains\CompanyOwner\DataObjects\CompanyOwnerApplicationLoginData;
use App\Domains\User\DataObjects\ChangePasswordData;
use App\Domains\User\DataObjects\UserChangePasswordData;
use App\Domains\User\DataObjects\UserData;
use App\Domains\User\Enums\UserTypes;
use App\Domains\User\UserQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->companyId = $this->company->id;

    $this->employeeA = Employee::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $this->userA = User::factory()->create([
        'employee_id' => $this->employeeA->id,
        'username' => 'ABCD',
        'password' => bcrypt('12345678'),
        'type_id' => UserTypes::COMPANY_OWNER->value,
    ]);

    $this->userQueries = new UserQueries();
});

test('User can be searched', function (): void {
    $response = $this->userQueries->listQuery([
        'search_text' => 'ABCD',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'employee_ids' => null,
    ], $this->companyId);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('username', $this->userA->username);
});

test('new users can be added', function (): void {
    $employee = Employee::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $this->userQueries->addNew(new UserData('asdf4', 2, $employee->id, ''), $this->companyId);

    $this->assertDatabaseHas('users', [
        'username' => 'asdf4',
        'employee_id' => $employee->id,
    ]);
});

test('A users can be fetched', function (): void {
    $response = $this->userQueries->getById($this->userA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('username', $this->userA->username);
});

test('A user can be updated', function (): void {
    $employee = Employee::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $user = User::factory()->create([
        'employee_id' => $employee->id,
        'username' => 'ABCDE',
    ]);
    $this->userQueries->update(new UserData('zxcvb', 1, $employee->id, ''), $user);

    $this->assertDatabaseHas('users', [
        'username' => 'zxcvb',
        'employee_id' => $employee->id,
    ]);
});

test('getUsersExport method returns users as expected', function (): void {
    $employee = Employee::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $user = User::factory()->create([
        'employee_id' => $employee->id,
        'username' => 'QWER',
    ]);

    $response = $this->userQueries->getUsersExport([
        'search_text' => 'QWER',
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
        'employee_ids' => null,
    ], $this->companyId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $user->id)
        ->toHaveKey('username', $user->username)
        ->toHaveKey('employee_id', $user->employee_id);
});

test('A user can change password', function (): void {
    $requestParameter = [
        'new_password' => '123456789',
    ];

    $user = User::factory()->create([
        'password' => bcrypt('123456'),
    ]);

    $this->userQueries->userChangePassword($user, new UserChangePasswordData(...$requestParameter));

    $user->refresh();
    $this->assertTrue(Hash::check($requestParameter['new_password'], $user->password));
});

test('A user can update profile', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('123456'),
        'username' => 'test',
    ]);

    $this->userQueries->updateUsername($user, 'test1');

    $this->assertDatabaseHas('users', [
        'username' => 'test1',
    ]);
});

test('A user can update password', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('123456'),
        'username' => 'test',
    ]);

    $this->userQueries->updatePassword($user, new ChangePasswordData('123456', '123456789'));

    $this->assertTrue(Hash::check('123456789', $user->password));
});

test('getCompanyOwnerByUsernameAndPassword method returns user as expected', function (): void {
    $response = $this->userQueries->getCompanyOwnerByUsernameAndPassword(
        new CompanyOwnerApplicationLoginData('ABCD', '12345678')
    );

    expect($response->toArray())
        ->toHaveKey('id', $this->userA->id)
        ->toHaveKey('employee_id', $this->userA->employee_id);
});

test('getCompanyOwnerByUsernameAndPassword method return null', function (): void {
    $response = $this->userQueries->getCompanyOwnerByUsernameAndPassword(
        new CompanyOwnerApplicationLoginData('ABCDE', '12345678')
    );

    expect($response)
        ->toBeNull();
});

test('createToken method returns user token as expected', function (): void {
    $response = $this->userQueries->createToken($this->userA);

    expect($response)
        ->toBeString();
});

test('User can request forgot password email', function (): void {
    $this->assertDatabaseHas('users', [
        'id' => $this->userA->id,
        'forgot_password_token' => null,
        'forgot_password_token_expiration_at' => null,
    ]);

    $response = $this->userQueries->fetchUserByUsername($this->userA->username);

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'username', 'employee_id', 'forgot_password_token_expiration_at']);
});

test('Exception is thrown if reset password token is expired', function (): void {
    $token = md5('adminTest@gmail.com' . now());

    User::factory()->create([
        'username' => 'UserTest',
        'forgot_password_token' => $token,
        'forgot_password_token_expiration_at' => now()->subHour(),
    ]);

    $this->userQueries->checkResetPasswordToken($token);
})->throws(ModelNotFoundException::class);

test('Reset password token fetches the correct admin record', function (): void {
    $token = md5('adminTest@gmail.com' . now());

    User::factory()->create([
        'username' => 'UserTest',
        'forgot_password_token' => $token,
        'forgot_password_token_expiration_at' => now()->addHour(),
    ]);

    $user = $this->userQueries->checkResetPasswordToken($token);

    expect($user)
        ->username->toEqual('UserTest')
        ->forgot_password_token->toEqual($token);
});

test('User can reset password', function (): void {
    $token = md5('adminTest@gmail.com' . now());

    $user = User::factory()->create([
        'username' => 'UserTest',
        'forgot_password_token' => $token,
        'forgot_password_token_expiration_at' => now()->addHour(),
    ]);

    $this->userQueries->resetPassword($user, 'ABCDEFGH');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'forgot_password_token' => null,
        'forgot_password_token_expiration_at' => null,
    ]);

    $user->refresh();
    $this->assertTrue(Hash::check('ABCDEFGH', $user->password));
});

test('A User can be fetched with employee', function (): void {
    $response = $this->userQueries->getByIdWithEmployeeAndMedia($this->userA->id, $this->companyId);
    expect($response->toArray())
        ->toHaveKey('username', $this->userA->username)
        ->toHaveKey('employee_id', $this->userA->employee_id)
        ->toHaveKey('employee.staff_id', $this->employeeA->staff_id);
});
