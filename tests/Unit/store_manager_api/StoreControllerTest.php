<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Inventory\DataObjects\StoreManagerApiStoreStockData;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Api\StoreManager\StoreController;
use App\Models\Location;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('calls the getStores method and returns stores record', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $storeManager->locations = collect([$location]);

    $request = new Request();

    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $this->mock(StoreManagerQueries::class, function ($mock) use ($storeManager): void {
        $mock->shouldReceive('loadStoresWithSearch')
            ->once()
            ->andReturn($storeManager);
    });

    $storeController = new StoreController();
    $response = $storeController->getStores($request);

    expect($response['stores']->resource)->toBeCollection();
});

test('calls the getStoreStock method and returns stores stock record', function (): void {
    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $filteredData = [
        'page' => 1,
        'per_page' => 1,
        'sort_by' => 'id',
        'sort_direction' => 'desc',
    ];

    $request = new Request($filteredData);
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $storeManagerApiStoreStockData = new StoreManagerApiStoreStockData(...$filteredData);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(InventoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getInventoryStocksForApplication')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $storeController = new StoreController();
    $response = $storeController->getStoreStock($request, $storeManagerApiStoreStockData, 1);

    expect($response['store_stock']);
});
