<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Director\DirectorQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Http\Controllers\Api\Pos\DirectorController;
use App\Models\Cashier;
use App\Models\Director;
use App\Models\Location;
use Illuminate\Http\Request;

test('it calls the getList method and returns directors records', function (): void {
    $companyId = 1;

    $cashierAndEmployeeData = makeCashierAndEmployeeForPosWithoutCounterUpdateId();
    $employee = $cashierAndEmployeeData['employee'];
    $cashier = $cashierAndEmployeeData['cashier'];

    $cashier->counter_update_id = 1;

    $director = Director::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $director->employee = collect([$employee]);

    $request = new Request();

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

    $this->mock(DirectorQueries::class, function ($mock) use ($companyId, $location, $director): void {
        $mock->shouldReceive('getList')
            ->once()
            ->with($location->id, $companyId, null)
            ->andReturn(collect($director));
    });

    $directorController = new DirectorController();
    $response = $directorController->getList($request);

    expect($response)->toBeArray();
});
