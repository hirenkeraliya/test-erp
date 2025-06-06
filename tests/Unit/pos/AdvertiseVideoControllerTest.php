<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\PosAdvertisement\PosAdvertisementQueries;
use App\Http\Controllers\Api\Pos\AdvertiseVideoController;
use App\Models\Cashier;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PosAdvertisement;
use Illuminate\Http\Request;

test('it calls the getList method and returns advertisement list records', function (): void {
    $companyId = 1;

    $posAdvertisement = PosAdvertisement::factory()->make([
        'company_id' => $companyId,
        'status' => true,
    ]);

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $cashier = Cashier::factory()->make([
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $filterData = [
        'after_updated_at' => null,
    ];

    $request = new Request($filterData);

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->andReturn($location);
    });

    $this->mock(PosAdvertisementQueries::class, function ($mock) use (
        $companyId,
        $posAdvertisement,
        $filterData
    ): void {
        $mock->shouldReceive('getList')
            ->once()
            ->with($companyId, 1, $filterData)
            ->andReturn(collect($posAdvertisement));
    });

    $advertiseVideoController = new AdvertiseVideoController();
    $response = $advertiseVideoController->getList($request);

    expect($response['advertisements']->resource->toArray())
        ->toHaveKeys(['name', 'type_id']);
});
