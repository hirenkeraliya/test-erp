<?php

declare(strict_types=1);

use App\Domains\Cashback\CashbackQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Http\Controllers\Api\Pos\CashbackController;
use App\Models\Cashback;
use App\Models\Cashier;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('it calls the getList method and returns cashback list records with related data.', function (): void {
    $companyId = 1;

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

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $cashback = Cashback::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(LocationQueries::class, function ($mock) use ($location, $cashier): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->with($cashier->counter_update_id)
            ->andReturn($location);
    });

    $this->mock(CashbackQueries::class, function ($mock) use ($location, $cashback): void {
        $mock->shouldReceive('getListForPosWithRelatedData')
            ->once()
            ->with($location, null)
            ->andReturn(collect($cashback));
    });

    $cashbackController = new CashbackController();
    $response = $cashbackController->getList($request);

    expect($response)->toBeArray();
});

test(
    'it throws exception when try to get cashback list without open counter.',
    function (): void {
        $companyId = 1;
        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'designation_id' => 1,
        ]);
        $cashier = Cashier::factory()->make([
            'employee_id' => $employee->id,
            'cashier_group_id' => 1,
        ]);
        $request = new Request();
        $request->setUserResolver(fn (): Cashier => $cashier);

        $cashbackController = new CashbackController();
        $cashbackController->getList($request);
    }
)->throws(HttpException::class, 'The counter has not been opened yet.');
