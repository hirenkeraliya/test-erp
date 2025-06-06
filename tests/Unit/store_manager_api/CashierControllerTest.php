<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Http\Controllers\Api\StoreManager\CashierController;
use App\Models\Cashier;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Http\Request;

test('calls the getCashiers method and returns cashier record', function (): void {
    $companyId = 1;

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $cashier = Cashier::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
    ]);

    $filterData = [
        'location_id' => $location->id,
        'search_text' => null,
    ];

    $this->mock(CashierQueries::class, function ($mock) use ($cashier, $filterData): void {
        $mock->shouldReceive('getListForStoreManagerApp')
            ->once()
            ->with($filterData)
            ->andReturn(collect([$cashier]));
    });

    $filterData['store_id'] = $location->id;

    $request = $this->mock(Request::class);
    $request->shouldReceive('validate')->andReturn([
        'store_id' => $location->id,
    ]);

    $cashierController = new CashierController();
    $response = $cashierController->getCashiers($request);

    expect($response['cashiers']->resource)->toBeCollection();
});
