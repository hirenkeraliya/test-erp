<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Inventory\DataObjects\WarehouseManagerApiWarehouseStockData;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Api\WarehouseManager\WarehouseController;
use App\Models\Employee;
use App\Models\Location;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('calls the getWarehouses method and returns stores stock record', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $warehouseManager = WarehouseManager::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::WAREHOUSE->value,
    ]);

    $warehouseManager->locations = collect([$location]);

    $request = new Request();
    $request->setUserResolver(fn (): WarehouseManager => $warehouseManager);

    $this->mock(WarehouseManagerQueries::class, function ($mock) use ($warehouseManager): void {
        $mock->shouldReceive('loadWarehouses')
            ->once()
            ->andReturn($warehouseManager);
    });

    $warehouseController = new WarehouseController();
    $response = $warehouseController->getWarehouses($request);

    expect($response['warehouses']->resource)->toBeCollection();
    expect($response['locations']->resource)->toBeCollection();
});

test('calls the getWarehouseStock method and returns stores stock record', function (): void {
    $employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $warehouseManager = WarehouseManager::factory()->make([
        'id' => 1,
        'employee_id' => $employee->id,
    ]);

    $filterData = [
        'page' => 1,
        'per_page' => 10,
    ];

    $request = new Request();
    $request->setUserResolver(fn (): WarehouseManager => $warehouseManager);

    $warehouseManagerApiWarehouseStockData = new WarehouseManagerApiWarehouseStockData(...$filterData);

    $this->mock(EmployeeQueries::class, function ($mock) use ($employee): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn($employee->id);
    });

    $this->mock(InventoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getInventoryStocksForApplication')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 10, 15));
    });

    $warehouseController = new WarehouseController();
    $response = $warehouseController->getWarehouseStock($request, $warehouseManagerApiWarehouseStockData, 1);

    $this->assertEquals(10, $response['total_records']);
    $this->assertEquals(collect([]), collect($response['warehouse_stock']));
});
