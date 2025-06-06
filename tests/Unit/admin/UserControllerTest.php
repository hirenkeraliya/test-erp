<?php

declare(strict_types=1);

use App\Domains\User\DataObjects\UserChangePasswordData;
use App\Domains\User\DataObjects\UserData;
use App\Domains\User\UserQueries;
use App\Http\Controllers\Admin\UserController;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the list query method of the user queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'employee_ids' => null,
    ];

    $colorQueries = $this->mock(UserQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $userController = new UserController($colorQueries);

    $response = $userController->fetchUsers(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']->resource);
});

test('It calls addNew method of the user queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $userData = User::factory()->make([
        'employee_id' => $employee->id,
    ])->toArray();

    $userData['password'] = '12345678';

    $userRecords = new UserData(...$userData);

    $userQueries = $this->mock(UserQueries::class, function ($mock) use ($userRecords): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($userRecords);
    });

    $userController = new UserController($userQueries);
    $redirectResponse = $userController->store($userRecords);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('User added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/users', $redirectResponse->getTargetUrl());
});

test('It calls update method of the user queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $user = User::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $userData = $user->toArray();
    unset($userData['id']);

    $userData['password'] = null;

    $userRecords = new UserData(...$userData);

    $userQueries = $this->mock(UserQueries::class, function ($mock) use ($user, $companyId, $userRecords): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with($user->id, $companyId)
            ->andReturn($user);

        $mock->shouldReceive('update')
            ->once()
            ->with($userRecords, $user);
    });

    $userController = new UserController($userQueries);
    $redirectResponse = $userController->update($userRecords, $companyId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('The user updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/users', $redirectResponse->getTargetUrl());
});

test('It calls the exportUsers method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'employee_ids' => null,
    ];

    $userQueries = $this->mock(UserQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getUsersExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new User()));
    });

    $userController = new UserController($userQueries);

    $response = $userController->exportUsers('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls change password method of the super admin queries class', function (): void {
    $userChangePasswordData = new UserChangePasswordData('111111');

    $companyId = 1;

    setCompanyIdInSession($companyId);

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $user = User::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $userQueries = $this->mock(UserQueries::class, function ($mock) use (
        $user,
        $userChangePasswordData,
        $companyId
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with($user->id, $companyId)
            ->andReturn($user);

        $mock->shouldReceive('userChangePassword')
            ->once()
            ->with($user, $userChangePasswordData);
    });

    $userController = new UserController($userQueries);
    $redirectResponse = $userController->updatePassword($userChangePasswordData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Password updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/users', $redirectResponse->getTargetUrl());
});
