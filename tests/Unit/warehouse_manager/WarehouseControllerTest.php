<?php

declare(strict_types=1);

use App\Domains\Location\LocationQueries;
use App\Domains\Warehouse\DataObjects\WarehouseSelectionData;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Exceptions\RedirectBackWithErrorException;
use App\Http\Controllers\WarehouseManager\WarehouseController;
use App\Models\Employee;
use App\Models\Location;
use App\Models\WarehouseManager;
use Illuminate\Http\Request;

test('getAuthorizedWarehouses method returns proper response', function (): void {
    $warehouseController = new WarehouseController();

    $request = new Request();

    $warehouseManager = WarehouseManager::factory()->make([
        'employee_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $warehouseManager->warehouses = collect([$location]);

    $request->setUserResolver(fn (): WarehouseManager => $warehouseManager);

    $this->mock(WarehouseManagerQueries::class, function ($mock) use ($warehouseManager): void {
        $mock->shouldReceive('getWarehouseManagerWarehouses')
            ->once()
            ->with($warehouseManager)
            ->andReturn($warehouseManager->warehouses);
    });

    $response = $warehouseController->getAuthorizedWarehouses($request);

    expect($response['locations'][0])
        ->toHaveKey('name', $location->name);
});

test('setSelectedWarehouse method works if warehouse is valid', function (): void {
    Employee::factory()->make([
        'company_id' => 1,
        'designation_id' => 1,
    ]);

    $warehouseManager = WarehouseManager::factory()->make([
        'employee_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $warehouseManager->warehouses = collect([$location]);

    $request = new Request();

    $request->setUserResolver(fn (): WarehouseManager => $warehouseManager);

    $warehouseSelectionData = new WarehouseSelectionData($location->id);

    $this->mock(WarehouseManagerQueries::class, function ($mock) use ($warehouseManager): void {
        $mock->shouldReceive('getWarehouseManagerWarehouseIds')
            ->once()
            ->with($warehouseManager)
            ->andReturn([1]);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdOfWarehouse')
            ->once()
            ->andReturn(1);
    });

    $warehouseController = new WarehouseController();
    $redirectResponse = $warehouseController->setSelectedWarehouse($warehouseSelectionData, $request);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertStringContainsString('warehouse-manager/dashboard', $redirectResponse->getTargetUrl());
});

test('setSelectedWarehouse method throw exception if warehouse is not valid', function (): void {
    $warehouseManager = WarehouseManager::factory()->make([
        'employee_id' => 1,
    ]);

    $warehouseManager->warehouses = collect([]);

    $request = new Request();

    $request->setUserResolver(fn (): WarehouseManager => $warehouseManager);

    $warehouseSelectionData = new WarehouseSelectionData(5);
    $this->mock(WarehouseManagerQueries::class, function ($mock) use ($warehouseManager): void {
        $mock->shouldReceive('getWarehouseManagerWarehouseIds')
            ->once()
            ->with($warehouseManager);
    });

    $warehouseController = new WarehouseController();
    $warehouseController->setSelectedWarehouse($warehouseSelectionData, $request);
})->throws(RedirectBackWithErrorException::class);
