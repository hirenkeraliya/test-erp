<?php

declare(strict_types=1);

use App\Domains\Designation\DesignationQueries;
use App\Domains\Employee\DataObjects\EmployeeData;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\Member\Services\MemberService;
use App\Exceptions\RedirectWithErrorException;
use App\Http\Controllers\StoreManager\EmployeeController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the getFilteredEmployees method and returns a proper response', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'abc',
    ];

    $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('searchEmployeesForFilter')
            ->once()
            ->with($requestParameter['search_text'], $companyId)
            ->andReturn(collect(new Employee()));
    });

    $employeeController = new EmployeeController($employeeQueries);
    $response = $employeeController->getFilteredEmployees(new Request($requestParameter));
    $this->assertEquals(collect([]), $response['employees']->resource);
});

test('It calls the list query method of the employee queries class and returns proper response', function (): void {
    $companyId = 1;
    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('adminListQuery')
            ->once()
            ->with($requestParameter, $companyId)
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
        'company_id' => $company->id,
        'designation_id' => 1,
    ]);

    $employeeRecord = $employee->toArray();
    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $employeeRecord['photo'] = $uploadedFile;
    unset($employeeRecord['card_number']);
    $employeeData = new EmployeeData(...$employeeRecord);

    setStoreManagerStoreCompanyIdInSession($company->id);

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock) use (
        $employeeData,
        $storeManager,
        $employee
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($employeeData, $storeManager)
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
    $this->assertStringContainsString('store-manager/employees', $redirectResponse->getTargetUrl());
});

test('It calls the get by id method of the employee queries class and returns proper response', function (): void {
    $companyId = 1;
    setStoreManagerStoreCompanyIdInSession($companyId);

    $newEmployeeRecord = Employee::factory()->make([
        'company_id' => $companyId,
        'designation_id' => 1,
        'group_id' => 1,
    ])->toArray();

    $employeeSecond = Employee::factory()->make([
        'company_id' => 2,
        'designation_id' => 1,
        'group_id' => 2,
    ])->toArray();

    setCompanyIdInSession($companyId);

    $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock) use ($newEmployeeRecord): void {
        $mock->shouldReceive('getByIdWithMedia')
            ->once()
            ->with(1)
            ->andReturn(new Employee($newEmployeeRecord));
    });

    $this->mock(DesignationQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(collect([]));
    });

    $this->mock(EmployeeGroupQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(collect([]));
    });

    $employeeController = new EmployeeController($employeeQueries);
    $response = $employeeController->edit(1);

    $response->rootView('store_manager.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has(
            'employee',
            fn (Assert $employee): Assert => $employee
            ->where('company_id', $newEmployeeRecord['company_id'])
            ->where('first_name', $newEmployeeRecord['first_name'])
            ->where('last_name', $newEmployeeRecord['last_name'])
            ->where('email', $newEmployeeRecord['email'])
            ->where('mobile_number', $newEmployeeRecord['mobile_number'])
            ->where('first_name', fn ($value): bool => $value !== $employeeSecond['first_name'])
            ->etc()
        )
        ->has(
            'jobTypes',
            fn (Assert $jobTypes): Assert => $jobTypes
            ->has('0', fn (Assert $jobType): Assert => $jobType->where('name', 'Full Time')->etc())
            ->etc()
        ),
    );
});

test('It calls the update method of the employee queries class and returns proper response', function (): void {
    $companyId = 1;

    $employeeRecord = Employee::factory()->make([
        'company_id' => $companyId,
        'designation_id' => 1,
    ])->toArray();
    $employeeRecord['photo'] = null;
    unset($employeeRecord['card_number']);

    $employeeData = new EmployeeData(...$employeeRecord);

    setStoreManagerStoreCompanyIdInSession($companyId);

    $request = new Request();
    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock) use ($employeeData, $storeManager): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($employeeData, $storeManager, 1000);
    });

    $employeeController = new EmployeeController($employeeQueries);
    $redirectResponse = $employeeController->update($employeeData, 1000, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('The employee was updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('store-manager/employees', $redirectResponse->getTargetUrl());
});

test('admin cannot update own record', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $employeeRecord = Employee::factory()->make([
        'company_id' => $companyId,
        'designation_id' => 1,
    ])->toArray();
    $employeeRecord['photo'] = null;
    unset($employeeRecord['card_number']);

    $employeeData = new EmployeeData(...$employeeRecord);

    setCompanyIdInSession($companyId);

    $request = new Request();

    $request->setUserResolver(fn (): StoreManager => new StoreManager([
        'employee_id' => 1,
    ]));

    $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock) use ($employeeData): void {
        $mock->shouldNotReceive('update')
            ->with($employeeData, 1);
    });

    $employeeController = new EmployeeController($employeeQueries);
    $redirectResponse = $employeeController->update($employeeData, 1, $request);
})->throws(RedirectWithErrorException::class);

test('admin cannot add employee with a different company id', function (): void {
    Storage::fake('public');

    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $employeeRecord = Employee::factory()->make([
        'company_id' => 1,
        'designation_id' => 1,
    ])->toArray();
    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
    $employeeRecord['photo'] = $uploadedFile;
    unset($employeeRecord['card_number']);
    $employeeData = new EmployeeData(...$employeeRecord);

    setStoreManagerStoreCompanyIdInSession(2);

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock) use ($employeeData, $storeManager): void {
        $mock->shouldNotReceive('addNew')
            ->with($employeeData, $storeManager);
    });

    $employeeController = new EmployeeController($employeeQueries);
    $employeeController->store($employeeData, $request);
})->throws(RedirectWithErrorException::class);

test('it calls the adminSetStatus method of employeeQueries class', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock) use ($employee, $storeManager): void {
        $mock->shouldReceive('adminSetStatus')
            ->once()
            ->with($employee->id, 1, false, $storeManager);
    });

    $employeeController = new EmployeeController($employeeQueries);
    $response = $employeeController->setStatus($employee->id, false, $request);

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('Status changed successfully.', $response->getSession()->all()['success']);
    $this->assertStringContainsString('store-manager/employees', $response->getTargetUrl());
});

test('It calls the exportEmployees method and returns a proper response', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getAdminEmployeesExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Employee()));
    });

    $employeeController = new EmployeeController($employeeQueries);

    $response = $employeeController->exportEmployees('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the addNew method of the employee queries class and if the email and mobile number exists in member then update member',
    function (): void {
        Storage::fake('public');

        $company = Company::factory()->make([
            'id' => 1,
            'default_country_id' => 1,
        ]);

        $employee = Employee::factory()->make([
            'company_id' => $company->id,
            'designation_id' => 1,
        ]);

        $employeeRecord = $employee->toArray();
        $uploadedFile = UploadedFile::fake()->image('avatar.jpg');
        $employeeRecord['photo'] = $uploadedFile;
        unset($employeeRecord['card_number']);
        $employeeData = new EmployeeData(...$employeeRecord);

        setStoreManagerStoreCompanyIdInSession($company->id);

        $storeManager = StoreManager::factory()->make([
            'employee_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): StoreManager => $storeManager);

        $employeeQueries = $this->mock(EmployeeQueries::class, function ($mock) use (
            $employeeData,
            $storeManager,
            $employee
        ): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->with($employeeData, $storeManager)
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
        $this->assertStringContainsString('store-manager/employees', $redirectResponse->getTargetUrl());
    }
);
