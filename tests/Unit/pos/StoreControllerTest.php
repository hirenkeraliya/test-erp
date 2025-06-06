<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Http\Controllers\Api\Pos\StoreController;
use App\Models\Cashier;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

test('calls the cashierStores method and returns stores record', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $cashier = makeCashierForPosWithoutCounterUpdateId();

    $cashier->locations = collect([$location]);

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $cashierQueries = $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('loadLocationsAndGetWithBasicColumns')
            ->once()
            ->with($cashier, null)
            ->andReturn(new Collection([$cashier->locations]));
    });

    $adminController = new StoreController();
    $response = $adminController->cashierStores($request, $cashierQueries);

    expect($response['stores']->resource)->toBeCollection();
    expect($response['locations']->resource)->toBeCollection();
});
