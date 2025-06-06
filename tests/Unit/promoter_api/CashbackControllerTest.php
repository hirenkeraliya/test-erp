<?php

declare(strict_types=1);

use App\Domains\Cashback\CashbackQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Http\Controllers\Api\Promoter\CashbackController;
use App\Models\Cashback;
use App\Models\Promoter;
use Illuminate\Http\Request;

test('calls the getStoreWiseCashbacks method and returns cashbacks record', function (): void {
    $cashback = Cashback::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);
    $request = new Request();
    $request->setUserResolver(fn (): Promoter => $promoter);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(CashbackQueries::class, function ($mock) use ($cashback): void {
        $mock->shouldReceive('getCashbacksStoreWiseForApplication')
            ->once()
            ->andReturn(collect($cashback));
    });

    $cashbackController = new CashbackController();
    $response = $cashbackController->getStoreWiseCashbacks($request, 1);

    expect($response['data']->resource)->toBeCollection();
});
