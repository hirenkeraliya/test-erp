<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\User\DataObjects\ChangePasswordData;
use App\Domains\User\DataObjects\UserUpdateData;
use App\Domains\User\UserQueries;
use App\Http\Controllers\Api\User\UserController;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

test('it can update user profile successfully', function (): void {
    $user = User::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): User => $user);

    $userRecords = new UserUpdateData(
        username: 'test',
        first_name: 'test',
        last_name: 'test',
        email: 'test@gmail.com',
        mobile_number : '1234567890',
        home_contact: '12345667890',
        address_line_1: 'test',
        address_line_2: 'test',
        city: 'test',
        area_code: 'test',
        primary_contact_name: 'test',
        primary_contact_phone: '234567890',
        photo: null,
    );

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('updateProfile')
            ->once();
    });

    $this->mock(UserQueries::class, function ($mock): void {
        $mock->shouldReceive('updateUsername')
            ->once();
    });

    $userController = resolve(UserController::class);

    $response = $userController->updateProfile($userRecords, $request);

    expect($response)->toHaveKey('message');
    expect($response)->toHaveKey('status_code');
});

test('It calls the getProfileDetails method to fetch profile data', function (): void {
    $user = User::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): User => $user);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(UserQueries::class, function ($mock) use ($user): void {
        $mock->shouldReceive('getByIdWithEmployeeAndMedia')
            ->andReturn($user)
            ->once();
    });

    $userController = resolve(UserController::class);

    $response = $userController->getProfileDetails($request);

    $this->assertEquals($user->username, $response['user_details']->username);
});

test('It calls change password method of the user queries class', function (): void {
    $userChangePasswordData = new ChangePasswordData('111111', '222222');

    $companyId = 1;

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $user = User::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
        'password' => Hash::make('111111'),
    ]);
    $request = new Request();
    $request->setUserResolver(fn (): User => $user);

    $userQueries = $this->mock(UserQueries::class, function ($mock): void {
        $mock->shouldReceive('updatePassword')
            ->once();
    });

    $userController = new UserController($userQueries);
    $response = $userController->updatePassword($userChangePasswordData, $request);
    expect($response)->toBeInstanceOf(JsonResponse::class);
});
