<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Location\LocationQueries;
use App\Http\Controllers\Api\Pos\CashierController;
use App\Models\Cashier;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

test('it calls the getList method and returns cashiers records', function (): void {
    $companyId = 1;

    $cashierAndEmployeeData = makeCashierAndEmployeeForPosWithoutCounterUpdateId();
    $employee = $cashierAndEmployeeData['employee'];
    $cashier = $cashierAndEmployeeData['cashier'];
    $cashier->counter_update_id = 1;

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
    ]);

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->andReturn($location);
    });

    $this->mock(CashierQueries::class, function ($mock) use ($companyId, $cashier): void {
        $mock->shouldReceive('getList')
            ->once()
            ->with($companyId, null)
            ->andReturn(new Collection([$cashier]));
    });

    $cashierController = new CashierController();
    $response = $cashierController->getList($request);

    expect($response)->toBeArray();
});
