<?php

declare(strict_types=1);

use App\Domains\Admin\AdminQueries;
use App\Domains\Admin\DataObjects\AdminChangePasswordData;
use App\Domains\Admin\DataObjects\AdminData;
use App\Domains\Company\CompanyQueries;
use App\Domains\Role\RoleQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\SuperAdmin\AdminController;
use App\Models\Admin;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

test('It calls the admin queries class and returns proper response', function (): void {
    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $adminQueries = $this->mock(AdminQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $adminController = new AdminController($adminQueries);

    $response = $adminController->fetchAdmins(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls the Add admin method of admin queries class', function (): void {
    $adminData = new AdminData('ABC', 1, 'XYZ', [1], null, null, null);

    $adminQueries = $this->mock(AdminQueries::class, function ($mock) use ($adminData): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($adminData);
    });

    $adminController = new AdminController($adminQueries);
    $redirectResponse = $adminController->store($adminData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Admin successfully added.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/admins', $redirectResponse->getTargetUrl());
});

test('It calls the get by id method of the admin queries class and returns proper response', function (): void {
    $requestParameter = [
        'username' => 'ABC',
        'employee_id' => 1,
    ];

    $adminQueries = $this->mock(AdminQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getByIdWithEmployee')
            ->once()
            ->with(1)
            ->andReturn(new Admin($requestParameter));
    });

    $companies = [[
        'id' => '1',
        'name' => 'ABC',
    ]];

    $roles = [[
        'id' => '1',
        'name' => 'ABC',
        'guard_name' => 'admin',
    ]];

    $companyQueries = $this->mock(CompanyQueries::class, function ($mock) use ($companies): void {
        $mock->shouldReceive('getWithBasicColumns')
            ->once()
            ->andReturn($companies);
    });

    $this->mock(RoleQueries::class, function ($mock) use ($roles): void {
        $mock->shouldReceive('getRoles')
            ->once()
            ->andReturn(collect([$roles]));
    });

    $adminController = new AdminController($adminQueries);
    $response = $adminController->edit(1, $companyQueries);
    $response->rootView('super_admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));
    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has('admin', fn (Assert $admin): Assert => $admin->where('username', 'ABC')->where('employee_id', 1))
        ->has(
            'companies',
            fn (Assert $companies): Assert => $companies
            ->has('0', fn (Assert $company): Assert => $company->where('id', '1')->where('name', 'ABC'))
        )
        ->has('roles')
    );
});

test('It calls the update admin method of the admin queries class', function (): void {
    $adminData = new AdminData('XYZ', 1, '', [1], null, null, null);

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $admin->employee = $employee;

    $adminQueries = $this->mock(AdminQueries::class, function ($mock) use ($adminData, $admin): void {
        $mock->shouldReceive('getByIdWithEmployee')
            ->once()
            ->with(1)
            ->andReturn($admin);

        $mock->shouldReceive('update')
            ->once()
            ->with($adminData, 1);
    });

    $filterData = [
        'company_id' => 1,
    ];

    $request = new Request($filterData);

    $adminController = new AdminController($adminQueries);
    $redirectResponse = $adminController->update($request, $adminData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Admin updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/admins', $redirectResponse->getTargetUrl());
});

test('It throws exception if super admin change admin company id', function (): void {
    $adminData = new AdminData('XYZ', 1, '', [1], null, null, null);

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $admin->employee = $employee;

    $adminQueries = $this->mock(AdminQueries::class, function ($mock) use ($adminData, $admin): void {
        $mock->shouldReceive('getByIdWithEmployee')
            ->once()
            ->with(1)
            ->andReturn($admin);

        $mock->shouldNotReceive('update')
            ->with($adminData, 1);
    });

    $filterData = [
        'company_id' => 3,
    ];

    $request = new Request($filterData);

    $adminController = new AdminController($adminQueries);
    $adminController->update($request, $adminData, 1);
})->throws(RedirectBackWithErrorException::class);

test('It calls change password method of the admin queries class', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'designation_id' => 1,
        'company_id' => 1,
    ]);

    $admin = Admin::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $adminChangePasswordData = new AdminChangePasswordData('111111');

    $adminQueries = $this->mock(AdminQueries::class, function ($mock) use (
        $admin,
        $adminChangePasswordData
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1)
            ->andReturn($admin);

        $mock->shouldReceive('adminChangePassword')
            ->once()
            ->with($admin, $adminChangePasswordData);
    });

    $adminController = new AdminController($adminQueries);
    $redirectResponse = $adminController->updatePassword($adminChangePasswordData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Password updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/admins', $redirectResponse->getTargetUrl());
});
