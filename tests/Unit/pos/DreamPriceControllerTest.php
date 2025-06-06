<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Http\Controllers\Api\Pos\DreamPriceController;
use App\Models\Cashier;
use App\Models\DreamPrice;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('it calls the getList method and returns dream price list records with products', function (): void {
    $companyId = 1;

    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'designation_id' => 1,
    ]);

    $dreamPrice = DreamPrice::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $cashier = Cashier::factory()->make([
        'employee_id' => $employee->id,
        'cashier_group_id' => 1,
        'counter_update_id' => 1,
    ]);

    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });
    $this->mock(LocationQueries::class, function ($mock) use ($cashier, $location): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
             ->once()
            ->with($cashier->counter_update_id)
            ->andReturn($location);
    });

    $this->mock(DreamPriceQueries::class, function ($mock) use ($companyId, $dreamPrice, $location): void {
        $mock->shouldReceive('getListWithProducts')
            ->once()
            ->with($companyId, $location->id, null)
            ->andReturn(collect($dreamPrice));
    });

    $dreamPriceController = new DreamPriceController();
    $response = $dreamPriceController->getList($request);

    expect($response)->toBeArray();
});

test(
    'it throws an exception if counter is not open But, try to get dream prices list with products',
    function (): void {
        $companyId = 1;

        $employee = Employee::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'designation_id' => 1,
        ]);

        $dreamPrice = DreamPrice::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
        ]);

        $cashier = Cashier::factory()->make([
            'employee_id' => $employee->id,
            'cashier_group_id' => 1,
        ]);

        $request = new Request();

        $request->setUserResolver(fn (): Cashier => $cashier);

        $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
            $mock->shouldReceive('getCashierCompanyId')
            ->times(0)
            ->with($cashier)
            ->andReturn(1);
        });

        $this->mock(DreamPriceQueries::class, function ($mock) use ($companyId, $dreamPrice): void {
            $mock->shouldReceive('getListWithProducts')
            ->times(0)
            ->with($companyId)
            ->andReturn(collect($dreamPrice));
        });

        $dreamPriceController = new DreamPriceController();
        $dreamPriceController->getList($request);
    }
)->throws(HttpException::class, 'The counter has not been opened yet.');
