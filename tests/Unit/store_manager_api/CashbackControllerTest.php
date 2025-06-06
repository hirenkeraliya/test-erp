<?php

declare(strict_types=1);

use App\Domains\Cashback\CashbackQueries;
use App\Domains\Cashback\DataObjects\StoreManagerApiCashbackData;
use App\Domains\Employee\EmployeeQueries;
use App\Http\Controllers\Api\StoreManager\CashbackController;
use App\Models\Cashback;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('calls the getCashbacks method and returns cashbacks record', function (): void {
    $cashback = Cashback::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $filterData = [
        'page' => 1,
        'per_page' => 1,
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'store_ids' => '1,2',
        'selected_date' => now()->subMonth()->format('Y-m-d'),
    ];

    $request = new Request($filterData);
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $storeManagerApiCashbackData = new StoreManagerApiCashbackData(...$filterData);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(CashbackQueries::class, function ($mock) use ($cashback): void {
        $mock->shouldReceive('getCashbacksForApplication')
            ->once()
            ->andReturn(new LengthAwarePaginator($cashback, 1, 15));
    });

    $cashbackController = new CashbackController();
    $response = $cashbackController->getCashbacks($request, $storeManagerApiCashbackData);

    expect($response['data']->resource)->toBeCollection();
    expect($response['total_records'])->toBe(1);
});

test('calls the getStoreWiseCashbacks method and returns cashbacks record', function (): void {
    $cashback = Cashback::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $storeManager);

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
