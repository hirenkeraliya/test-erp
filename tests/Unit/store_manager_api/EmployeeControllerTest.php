<?php

declare(strict_types=1);

use App\Domains\Designation\DesignationQueries;
use App\Domains\Employee\DataObjects\StoreManagerApiEmployeeData;
use App\Domains\Employee\DataObjects\StoreManagerEmployeeData;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Employee\Enums\JobTypes;
use App\Domains\Employee\Resources\StoreManagerEmployeeResource;
use App\Domains\EmployeeGroup\EmployeeGroupQueries;
use App\Domains\Member\Services\MemberService;
use App\Http\Controllers\Api\StoreManager\EmployeeController;
use App\Models\Employee;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

test('it calls the getPaginatedList method and returns the paginated list of employees', function (): void {
    $companyId = 1;

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $filteredData = [
        'page' => 1,
        'per_page' => 10,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'search_text' => 'test',
    ];

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $storeManagerApiEmployeeData = new StoreManagerApiEmployeeData(...$filteredData);

    unset($filteredData['page']);

    $this->mock(EmployeeQueries::class, function ($mock) use ($filteredData, $companyId): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->with(1)
            ->andReturn($companyId);

        $mock->shouldReceive('getPaginatedListForStoreManagerApp')
            ->once()
            ->with($filteredData, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $employeeController = new EmployeeController();
    $response = $employeeController->getPaginatedList($request, $storeManagerApiEmployeeData);

    expect($response['employees'])->toBeInstanceOf(AnonymousResourceCollection::class);
});

test('it calls the store method and adds a new employee', function (): void {
    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $employeeData = new StoreManagerEmployeeData(
        1,
        1,
        'John',
        'Doe',
        'john.doe@example.com',
        '6664589789',
        '1',
        'Test Address',
        '1',
        'Rajkot',
        null,
        Carbon::now()->format('Y-m-d'),
        'Test',
        '6664589789',
        (string) random_int(4, 5),
        null,
        JobTypes::FULL_TIME->value,
        true,
        null,
        null
    );

    $request = $this->mock(Request::class, function ($mock) use ($storeManager, $employeeData): void {
        $mock->shouldReceive('merge')
            ->once();

        $mock->shouldReceive('validate')
            ->once();

        $mock->shouldReceive('user')
            ->once()
            ->andReturn($storeManager);

        $mock->shouldReceive('all')
            ->andReturn($employeeData->all());

        $mock->shouldReceive('route');
    });

    $employeeId = $storeManager->employee_id;
    $this->mock(EmployeeQueries::class, function ($mock) use ($employeeId): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->with($employeeId)
            ->andReturn(1);

        $mock->shouldReceive('addNew')
            ->once()
            ->andReturn(new Employee());
    });

    $this->mock(MemberService::class, function ($mock): void {
        $mock->shouldReceive('addNewEmployeeMember')
            ->once();
    });

    $employeeController = new EmployeeController();
    $response = $employeeController->store($request);

    expect($response['employee'])->toBeInstanceOf(StoreManagerEmployeeResource::class);
});

test('it calls the getEmployeeDetails method and returns the employee details', function (): void {
    $employeeId = 1;

    $this->mock(EmployeeQueries::class, function ($mock) use ($employeeId): void {
        $mock->shouldReceive('getByIdWithMedia')
            ->once()
            ->with($employeeId)
            ->andReturn(new Employee());
    });

    $employeeController = new EmployeeController();
    $response = $employeeController->getEmployeeDetails($employeeId);

    expect($response['employee'])->toBeInstanceOf(StoreManagerEmployeeResource::class);
});

test('it calls the update method and updates the employee details', function (): void {
    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $employeeData = new StoreManagerEmployeeData(
        1,
        1,
        'John',
        'Doe',
        'john.doe@example.com',
        '6664589789',
        '1',
        'Test Address',
        '1',
        'Rajkot',
        null,
        Carbon::now()->format('Y-m-d'),
        'Test',
        '6664589789',
        (string) random_int(4, 5),
        null,
        JobTypes::FULL_TIME->value,
        true,
        null,
        null
    );

    $request = $this->mock(Request::class, function ($mock) use ($storeManager, $employeeData): void {
        $mock->shouldReceive('merge')
            ->once();

        $mock->shouldReceive('validate')
            ->once();

        $mock->shouldReceive('user')
            ->once()
            ->andReturn($storeManager);

        $mock->shouldReceive('all')
            ->andReturn($employeeData->all());

        $mock->shouldReceive('route');
    });

    $employeeId = 1;

    $this->mock(EmployeeQueries::class, function ($mock) use ($storeManager, $employeeId): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->with($storeManager->employee_id)
            ->andReturn(1);

        $mock->shouldReceive('update')
            ->once();

        $mock->shouldReceive('getByIdWithMedia')
            ->once()
            ->with($employeeId)
            ->andReturn(new Employee());
    });

    $employeeController = new EmployeeController();
    $response = $employeeController->update($employeeId, $request);

    expect($response['employee'])->toBeInstanceOf(StoreManagerEmployeeResource::class);
});

test('it calls the getEmployeeGroupList method and returns the list of employee groups', function (): void {
    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);
    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $this->mock(EmployeeQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->with($storeManager->employee_id)
            ->andReturn(1);
    });

    $this->mock(EmployeeGroupQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(new Collection());
    });

    $employeeController = new EmployeeController();
    $response = $employeeController->getEmployeeGroupList($request);

    expect($response)->toHaveKey('employeeGroups');
});

test('it calls the getJobTypeList method and returns the list of job types', function (): void {
    $employeeController = new EmployeeController();
    $response = $employeeController->getJobTypeList();

    expect($response)->toHaveKey('jobTypes');
});

test('it calls the getDesignationList method and returns the list of designations', function (): void {
    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);
    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $this->mock(EmployeeQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->with($storeManager->employee_id)
            ->andReturn(1);
    });

    $this->mock(DesignationQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(new Collection());
    });

    $employeeController = new EmployeeController();
    $response = $employeeController->getDesignationList($request);

    expect($response)->toHaveKey('designations');
});
