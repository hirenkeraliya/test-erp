<?php

declare(strict_types=1);

use App\Domains\DreamPrice\DataObjects\StoreManagerApiDreamPriceData;
use App\Domains\DreamPrice\DreamPriceQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Http\Controllers\Api\StoreManager\DreamPriceController;
use App\Models\DreamPrice;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('calls the getDreamPrices method and returns dreamPrices record', function (): void {
    $dreamPrice = DreamPrice::factory()->make([
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
        'selected_date' => now()->subMonth()->format('Y-m-d'),
    ];

    $request = new Request($filterData);
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $storeManagerApiDreamPriceData = new StoreManagerApiDreamPriceData(...$filterData);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(DreamPriceQueries::class, function ($mock) use ($dreamPrice): void {
        $mock->shouldReceive('getDreamPricesForApplication')
            ->once()
            ->andReturn(new LengthAwarePaginator($dreamPrice, 1, 15));
    });

    $dreamPriceController = new DreamPriceController();
    $response = $dreamPriceController->getDreamPrices($request, $storeManagerApiDreamPriceData);

    expect($response['data']->resource)->toBeCollection();
    expect($response['total_records'])->toBe(1);
});
