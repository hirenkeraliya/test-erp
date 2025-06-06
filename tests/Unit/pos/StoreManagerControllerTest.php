<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Api\Pos\StoreManagerController;
use App\Models\Cashier;
use App\Models\Location;
use App\Models\StoreManager;
use Illuminate\Http\Request;

test('it calls the getList method and returns store manager record', function (): void {
    $companyId = 1;

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $cashier = makeCashierForPosWithoutCounterUpdateId();
    $cashier->counter_update_id = 1;

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->andReturn($location);
    });

    $this->mock(StoreManagerQueries::class, function ($mock) use ($companyId, $storeManager): void {
        $mock->shouldReceive('getStoreManagerListForPos')
            ->once()
            ->with($companyId, null)
            ->andReturn(collect($storeManager));
    });

    $storeManagerController = new StoreManagerController();
    $response = $storeManagerController->getList($request);

    expect($response['store_managers']->resource)->toBeCollection();
});
