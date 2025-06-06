<?php

declare(strict_types=1);

use App\Domains\Counter\CounterQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Http\Controllers\Api\StoreManager\CounterController;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StoreManager;
use Illuminate\Http\Request;

test('calls the getCounters method and returns counter record', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counterDetails = [
        'name' => 'abc',
        'location_id' => $location->id,
        'is_locked' => false,
    ];

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($storeManager);
    $request->shouldReceive('validate')->once()->andReturn([
        'location_id' => $location->id,
        'search_text' => null,
    ]);
    $request->shouldReceive('all')->once()->andReturn([
        'location_id' => $location->id,
        'search_text' => null,
    ]);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(CounterQueries::class, function ($mock) use ($counterDetails, $request): void {
        $mock->shouldReceive('getCounterListOfSelectedLocation')
            ->once()
            ->with($request->location_id, 1, null)
            ->andReturn(collect([$counterDetails]));
    });

    $counterController = new CounterController();
    $response = $counterController->getCounters($request);

    expect($response['counters']->resource)->toBeCollection();
});
