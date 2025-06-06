<?php

declare(strict_types=1);

use App\Domains\Cashier\CashierQueries;
use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Http\Controllers\Api\Pos\PromoterController;
use App\Models\Cashier;
use App\Models\Location;
use App\Models\Promoter;
use Illuminate\Http\Request;

test('it calls the getList method and returns employee and store records', function (): void {
    $companyId = 1;

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $cashierAndEmployee = makeCashierAndEmployeeForPosWithoutCounterUpdateId();

    $employee = $cashierAndEmployee['employee'];

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $cashier = $cashierAndEmployee['cashier'];
    $promoter->employee = collect([$employee]);
    $cashier->counter_update_id = 1;

    $request = new Request();
    $request->setUserResolver(fn (): Cashier => $cashier);

    $this->mock(CashierQueries::class, function ($mock) use ($cashier): void {
        $mock->shouldReceive('getCashierCompanyId')
            ->once()
            ->with($cashier)
            ->andReturn(1);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getLocationByCountersCounterUpdateId')
            ->once()
            ->andReturn($location);
    });

    $this->mock(PromoterQueries::class, function ($mock) use ($companyId, $location, $promoter): void {
        $mock->shouldReceive('getPromoterListForPosAndOrders')
            ->once()
            ->with($location->id, $companyId, SaleReturnOrVoidSaleReasonTypes::POS->value, null)
            ->andReturn(collect($promoter));
    });

    $promoterController = new PromoterController();
    $response = $promoterController->getList($request);

    expect($response['promoters']->resource)->toBeCollection();
});
