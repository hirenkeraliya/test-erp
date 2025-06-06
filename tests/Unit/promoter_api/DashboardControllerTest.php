<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterCommissionUpdate\PromoterCommissionUpdateQueries;
use App\Http\Controllers\Api\Promoter\DashboardController;
use App\Models\Location;
use App\Models\Promoter;
use Illuminate\Http\Request;

test('It calls the getDashboardData method of the SaleQueriesQueries class', function (): void {
    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $promoter = $this->mock(Promoter::class);
    $promoter->shouldReceive('getKey')->andReturn(1);

    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($promoter);
    $request->shouldReceive('validate')->once()->andReturn([
        'store_id' => 1,
    ]);
    $request->shouldReceive('all')->andReturn([
        'store_id' => $location->id,
    ]);

    $this->mock(PromoterQueries::class, function ($mock): void {
        $mock->shouldReceive('getItemSoldCountForTheGivenPromoter')
            ->times(2);
    });

    $this->mock(PromoterCommissionUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getItemsSoldCountAndCommissionAmountTotal')
            ->once();
    });

    $dashboardController = new DashboardController();
    $dashboardController->getDashboardData($request);
});
