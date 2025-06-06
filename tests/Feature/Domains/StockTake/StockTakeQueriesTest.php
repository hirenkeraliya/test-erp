<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StockTake\StockTakeQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StockTake;
use App\Models\StoreManager;
use App\Models\WarehouseManager;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->company = Company::factory()->create();

    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->store = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->storeManager = StoreManager::factory()->create([
        'employee_id' => $this->employee->id,
    ]);

    $this->warehouse = Location::factory()->create([
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::WAREHOUSE->value,
    ]);

    $this->warehouseManager = WarehouseManager::factory()->create([
        'employee_id' => $this->employee->id,
    ]);

    $this->stockTakeA = StockTake::factory()->create([
        'company_id' => $this->company->id,
        'requested_by_id' => $this->storeManager->id,
        'location_id' => $this->store->id,
    ]);

    $this->stockTakeB = StockTake::factory()->create([
        'company_id' => $this->company->id,
        'requested_by_id' => $this->warehouseManager->id,
        'location_id' => $this->warehouse->id,
    ]);

    $this->stockTakeQueries = new StockTakeQueries();
});

test('stock takes can be fetched', function (): void {
    $response = $this->stockTakeQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->store->id, $this->company->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKeys(
            [
                'id',
                'company_id',
                'requested_by_id',
                'requested_by_type',
                'location_id',
                'submitted_by_id',
                'submitted_by_type',
                'submitted_at',
            ]
        );
});

test('a new stock take can be added', function (): void {
    $record = [
        'stock_record_date' => Carbon::now()->format('Y-m-d'),
        'notes' => 'test',
        'requested_by_id' => $this->storeManager->id,
        'requested_by_type' => ModelMapping::STORE_MANAGER->name,
        'location_id' => $this->store->id,
        'company_id' => $this->company->id,
    ];

    $this->stockTakeQueries->addNew($record);

    $this->assertDatabaseHas('stock_takes', $record);
});

test('submit the stock take', function (): void {
    $currentTime = Carbon::now();
    $currenDate = $currentTime->format('Y-m-d');
    $this->stockTakeQueries->submit(
        $this->stockTakeA->id,
        $this->storeManager->id,
        $this->store->id,
        ModelMapping::STORE_MANAGER->name,
        $currenDate,
        $this->company->id,
    );

    $this->assertDatabaseHas('stock_takes', [
        'id' => $this->stockTakeA->id,
        'compare_stock_date' => $currenDate,
        'submitted_by_id' => $this->storeManager->id,
        'submitted_at' => $currentTime->format('Y-m-d H:i:s'),
    ]);
});

test('a submitted stock takes can be fetched', function (): void {
    StockTake::factory()->submitted()->create([
        'company_id' => $this->company->id,
        'requested_by_id' => $this->storeManager->id,
        'location_id' => $this->store->id,
    ]);

    $response = $this->stockTakeQueries->getAdminListQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->company->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKeys(['id', 'requested_by_id', 'location_id', 'submitted_by_id', 'submitted_at']);
});

test('the anyPendingStockTakeByManager method calls and boolean as expected', function (): void {
    $response = $this->stockTakeQueries->anyPendingStockTakeByManager($this->store->id, $this->company->id);

    $this->assertTrue($response);
});

test('the isStockTakePending method calls and boolean as expected', function (): void {
    $stockTake = StockTake::factory()->submitted()->create();
    $response = $this->stockTakeQueries->isStockTakePending($stockTake->id);

    $this->assertTrue($response);
});

test('stock takes can be fetched for warehouse manager panel', function (): void {
    $response = $this->stockTakeQueries->listQuery([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->warehouse->id, $this->company->id);

    $this->assertEquals(1, $response->total());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKeys(
            [
                'id',
                'requested_by_id',
                'requested_by_type',
                'location_id',
                'submitted_by_id',
                'submitted_by_type',
                'submitted_at',
            ]
        );
});

test('a new stock take can be added for warehouse manager panel', function (): void {
    $record = [
        'stock_record_date' => Carbon::now()->format('Y-m-d'),
        'notes' => 'test',
        'requested_by_id' => $this->warehouseManager->id,
        'requested_by_type' => ModelMapping::WAREHOUSE_MANAGER->name,
        'location_id' => $this->warehouse->id,
        'company_id' => $this->company->id,
    ];

    $this->stockTakeQueries->addNew($record);

    $this->assertDatabaseHas('stock_takes', $record);
});

test('submit the stock take for warehouse manage panel', function (): void {
    $currenDate = Carbon::now()->format('Y-m-d');
    $this->stockTakeQueries->submit(
        $this->stockTakeB->id,
        $this->warehouseManager->id,
        $this->warehouse->id,
        ModelMapping::WAREHOUSE_MANAGER->name,
        $currenDate,
        $this->company->id,
    );

    $this->assertDatabaseHas('stock_takes', [
        'id' => $this->stockTakeB->id,
        'company_id' => $this->company->id,
        'compare_stock_date' => $currenDate,
        'submitted_by_id' => $this->warehouseManager->id,
        'submitted_at' => Carbon::now()->format('Y-m-d H:i:s'),
    ]);
});

test(
    'the anyPendingStockTakeByManager method calls and boolean as expected for warehouse manager panel',
    function (): void {
        StockTake::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $response = $this->stockTakeQueries->anyPendingStockTakeByManager($this->warehouse->id, $this->company->id);

        $this->assertTrue($response);
    }
);

test('getStockTakesExport method returns stock take as expected', function (): void {
    StockTake::factory()->submitted()->create([
        'requested_by_id' => $this->storeManager->id,
        'location_id' => $this->store->id,
        'company_id' => $this->company->id,
    ]);

    $response = $this->stockTakeQueries->getStockTakesExport([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'requested_by_id', 'location_id', 'submitted_at']);
});

test('getStoreAndWarehouseMangerStockTakesExport method returns stock take as expected', function (): void {
    StockTake::factory()->create([
        'requested_by_id' => $this->storeManager->id,
        'location_id' => $this->store->id,
        'company_id' => $this->company->id,
        'submitted_by_id' => $this->storeManager->id,
        'submitted_by_type' => ModelMapping::STORE_MANAGER->name,
        'submitted_at' => Carbon::now(),
    ]);

    $response = $this->stockTakeQueries->getStoreAndWarehouseMangerStockTakesExport([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 15,
    ], $this->store->id, $this->company->id);

    expect($response->first()->toArray())
        ->toHaveKeys(['id', 'requested_by_id', 'location_id', 'submitted_at']);
});

test('setUpdatedAt method returns stock take as expected', function (): void {
    $date = now()->subMinute()->format('Y-m-d H:i:s');
    $stockTake = StockTake::factory()->create([
        'updated_at' => $date,
    ]);

    $this->stockTakeQueries->setUpdatedAt($stockTake->id);

    $this->assertDatabaseMissing('stock_takes', [
        'id' => $stockTake->id,
        'updated_at' => $date,
    ]);
});

test('getLocationColumnsByIdAndCompanyId method returns stock take as expected', function (): void {
    $stockTake = StockTake::factory()->create([
        'requested_by_id' => $this->storeManager->id,
        'location_id' => $this->store->id,
        'company_id' => $this->company->id,
        'submitted_by_id' => $this->storeManager->id,
        'submitted_by_type' => ModelMapping::STORE_MANAGER->name,
        'submitted_at' => Carbon::now(),
    ]);

    $response = $this->stockTakeQueries->getLocationColumnsByIdAndCompanyId($stockTake->id, $this->company->id);

    expect($response->toArray())
        ->toHaveKeys(['id', 'location_id']);
});
