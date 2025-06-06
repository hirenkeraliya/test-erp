<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Employee\DataObjects\EmployeeData;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Member\Services\MemberService;
use App\Http\Controllers\SuperAdmin\EmployeeController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\SuperAdmin;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

test('It calls the list query method of the employee queries class and returns proper response', function (): void {
    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('superAdminListQuery')
            ->once()
            ->with($requestParameter)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $employeeController = new EmployeeController($employeeQueries);

    $response = $employeeController->fetchEmployees(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls the addNew method of the employee queries class and returns proper response', function (): void {
    Storage::fake('public');

    $company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $employee = Employee::factory()->make([
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $employeeRecord = $employee->toArray();
    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $employeeRecord['photo'] = $uploadedFile;
    unset($employeeRecord['card_number']);

    $superAdmin = SuperAdmin::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): SuperAdmin => $superAdmin);

    $employeeData = new EmployeeData(...$employeeRecord);

    $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock) use (
        $employeeData,
        $superAdmin,
        $employee
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($employeeData, $superAdmin)
            ->andReturn($employee);
    });

    $this->mock(MemberService::class, function ($mock): void {
        $mock->shouldReceive('addNewEmployeeMember')
            ->once();
    });

    $employeeController = new EmployeeController($employeeQueries);
    $redirectResponse = $employeeController->store($employeeData, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Employee added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/employees', $redirectResponse->getTargetUrl());
});

test('It calls the get by id method of the employee queries class and returns proper response', function (): void {
    $employeeRecord = Employee::factory()->make([
        'company_id' => 1,
        'designation_id' => 1,
    ])->toArray();

    $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock) use ($employeeRecord): void {
        $mock->shouldReceive('getByIdWithMedia')
            ->once()
            ->with(1)
            ->andReturn(new Employee($employeeRecord));
    });

    $companies = [[
        'id' => '1',
        'name' => 'ABC',
    ]];

    $companyQueries = $this->mock(CompanyQueries::class, function ($mock) use ($companies): void {
        $mock->shouldReceive('getWithBasicColumns')
            ->once()
            ->andReturn($companies);
    });

    $employeeController = new EmployeeController($employeeQueries);
    $response = $employeeController->edit(1, $companyQueries);
    $response->rootView('super_admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
            ->has(
                'employee',
                fn (Assert $employee): Assert => $employee
                    ->where('company_id', $employeeRecord['company_id'])
                    ->where('first_name', $employeeRecord['first_name'])
                    ->where('last_name', $employeeRecord['last_name'])
                    ->where('email', $employeeRecord['email'])
                    ->etc()
            )
            ->has(
                'jobTypes',
                fn (Assert $jobTypes): Assert => $jobTypes
                    ->has('0', fn (Assert $jobType): Assert => $jobType->where('name', 'Full Time')->etc())
                    ->etc()
            )
            ->has(
                'companies',
                fn (Assert $companies): Assert => $companies
                    ->has('0', fn (Assert $company): Assert => $company->where('id', '1')->where('name', 'ABC'))
            ),
    );
});

test('It calls the update method of the employee queries class and returns proper response', function (): void {
    $employeeRecord = Employee::factory()->make([
        'company_id' => 1,
        'designation_id' => 1,
    ])->toArray();
    $employeeRecord['photo'] = null;

    unset($employeeRecord['card_number']);

    $superAdmin = SuperAdmin::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): SuperAdmin => $superAdmin);

    $employeeData = new EmployeeData(...$employeeRecord);

    $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock) use ($employeeData, $superAdmin): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($employeeData, $superAdmin, 1);
    });

    $employeeController = new EmployeeController($employeeQueries);
    $redirectResponse = $employeeController->update($employeeData, 1, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('The employee was updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/employees', $redirectResponse->getTargetUrl());
});

test(
    'It calls the getFormattedEmployeesOf method of the employee queries class and returns proper response',
    function (): void {
        $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock): void {
            $mock->shouldReceive('getFormattedEmployeesOf')
                ->once()
                ->with(1)
                ->andReturn(collect([]));
        });

        $employeeController = new EmployeeController($employeeQueries);
        $response = $employeeController->getByCompanyId(1);
        $this->assertEquals(collect([]), $response['data']);
    }
);

test('it calls the superAdminSetStatus method of employeeQueries class', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $superAdmin = SuperAdmin::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): SuperAdmin => $superAdmin);

    $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock) use ($employee, $superAdmin): void {
        $mock->shouldReceive('superAdminSetStatus')
            ->once()
            ->with($employee->id, false, $superAdmin);
    });

    $employeeController = new EmployeeController($employeeQueries);
    $response = $employeeController->setStatus($employee->id, false, $request);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Status changed successfully.', $response->getSession()->all()['success']);
    $this->assertStringContainsString('super-admin/employees', $response->getTargetUrl());
});

test(
    'It calls the addNew method of the employee queries class and if the email and mobile number exists in member then update member',
    function (): void {
        Storage::fake('public');
        $employee = Employee::factory()->make([
            'company_id' => 1,
            'designation_id' => 1,
        ]);

        $employeeRecord = $employee->toArray();
        $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
        $employeeRecord['photo'] = $uploadedFile;
        unset($employeeRecord['card_number']);

        $superAdmin = SuperAdmin::factory()->make([
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): SuperAdmin => $superAdmin);

        $employeeData = new EmployeeData(...$employeeRecord);

        $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock) use (
            $employeeData,
            $superAdmin,
            $employee
        ): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->with($employeeData, $superAdmin)
                ->andReturn($employee);
        });

        $this->mock(MemberService::class, function ($mock): void {
            $mock->shouldReceive('addNewEmployeeMember')
                ->once();
        });

        $employeeController = new EmployeeController($employeeQueries);
        $redirectResponse = $employeeController->store($employeeData, $request);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('Employee added successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString('super-admin/employees', $redirectResponse->getTargetUrl());
    }
);
