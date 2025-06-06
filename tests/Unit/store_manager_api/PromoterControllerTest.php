<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Http\Controllers\Api\StoreManager\PromoterController;
use App\Models\Employee;
use App\Models\Promoter;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('calls the getLists method and returns promoter record', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $filterData = [
        'location_id' => 1,
        'search_text' => null,
    ];

    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($storeManager);
    $request->shouldReceive('validate')->once()->andReturn([
        'location_id' => 1,
    ]);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(PromoterQueries::class, function ($mock) use ($promoter, $filterData): void {
        $mock->shouldReceive('getPromoterListWithLocationsForStoreManagerAPI')
            ->once()
            ->with(1, $filterData)
            ->andReturn(collect([$promoter]));
    });

    $promoterController = new PromoterController();
    $response = $promoterController->getLists($request);

    expect($response['promoters']->resource)->toBeCollection();
});

test('calls the getTopPromoters method and returns top ten promoter record', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'first_name' => 'abc',
        'last_name' => 'def',
        'designation_id' => 1,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
        'group_id' => null,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $filterData = [
        'store_id' => 1,
        'start_date' => '2023-01-01',
        'end_date' => '2023-01-01',
        'date' => null,
    ];

    $promoter->employee = $employee;

    $request = new Request($filterData);

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(PromoterQueries::class, function ($mock) use ($promoter, $filterData): void {
        $mock->shouldReceive('getSalesByPromotersForDashboard')
            ->once()
            ->with(1, 1, null, $filterData['start_date'], $filterData['end_date'], false)
            ->andReturn(collect([$promoter]));
    });

    $promoterController = new PromoterController();
    $response = $promoterController->getTopPromoters($request);

    expect($response)->toBeArray();
});

test('calls the updateStatus method and returns with proper response', function (): void {
    [$promoter, $storeManager] = commonUpdateStatusSeederData();

    $data = [
        'promoter_id' => $promoter->id,
        'status' => true,
    ];

    $request = $this->mock(Request::class, function ($mock) use ($storeManager, $data): void {
        $mock->shouldReceive('validate')
            ->once()
            ->andReturn($data);
        $mock->shouldReceive('user')
            ->once()
            ->andReturn($storeManager);
    });

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
        $mock->shouldReceive('statusChange')
            ->once();
    });

    $this->mock(PromoterQueries::class, function ($mock) use ($promoter): void {
        $mock->shouldReceive('getPromoterById')
            ->once()
            ->andReturn($promoter);
    });

    $promoterController = new PromoterController();
    $response = $promoterController->updateStatus($request);
    expect($response)->toBeNull();
});

test('calls the updateStatus method and returns exception', function (): void {
    [$promoter, $storeManager] = commonUpdateStatusSeederData();

    $data = [
        'promoter_id' => $promoter->id,
        'status' => false,
    ];

    $request = $this->mock(Request::class, function ($mock) use ($storeManager, $data): void {
        $mock->shouldReceive('validate')
            ->once()
            ->andReturn($data);
        $mock->shouldReceive('user')
            ->once()
            ->andReturn($storeManager);
    });

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(PromoterQueries::class, function ($mock): void {
        $mock->shouldReceive('getPromoterById')
            ->once()
            ->andReturn(null);
    });

    $promoterController = new PromoterController();
    $promoterController->updateStatus($request);
})->throws(HttpException::class, 'Specified Promoter ID Status is already deactivated.');

function commonUpdateStatusSeederData(): array
{
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
        'status' => false,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);
    $promoter->employee = $employee;
    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    return [$promoter, $storeManager];
}
