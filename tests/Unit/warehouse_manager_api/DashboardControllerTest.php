<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Api\WarehouseManager\DashboardController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StockTransfer;
use App\Models\WarehouseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $this->employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'designation_id' => 1,
    ]);

    $this->warehouseManager = WarehouseManager::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
    ]);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::WAREHOUSE->value,
    ]);
});

test('calls the getTransferStatusesData method and returns record', function (): void {
    $stockTransfer = StockTransfer::factory()->make([
        'company_id' => 1,
        'transfer_type' => 2,
        'status' => 6,
        'stock_transfer_reason_id' => 2,
        'source_location_type' => 'WAREHOUSE',
        'source_location_id' => 1,
        'destination_location_type' => 'WAREHOUSE',
        'destination_location_id' => 1,
        'requested_by_type' => 'name',
        'requested_by_id' => 1,
    ]);

    $request = new Request([
        'warehouse_id' => $this->location->id,
        'location_id' => $this->location->id,
    ]);
    $request->setUserResolver(fn (): WarehouseManager => $this->warehouseManager);

    $this->warehouseManager->warehouses = $this->location->id;

    $this->mock(WarehouseManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndWarehouseId')
        ->once()
            ->with((int) $this->warehouseManager->id, (int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdOfWarehouse')
        ->once()
            ->with((int) $this->location->id)
            ->andReturn(true);
    });

    $filterData = [
        'location_id' => $this->location->id,
        'transfer_type' => null,
        'search_text' => null,
        'stock_transfer_date' => null,
        'select_status' => null,
    ];

    $this->mock(StockTransferQueries::class, function ($mock) use ($filterData, $stockTransfer): void {
        $mock->shouldReceive('warehouseManagerTransferOrRequestOrderStatusCount')
        ->once()
            ->with([StockTransferTypes::TRANSFER_ORDER->value], $filterData, $this->company->id, $this->location->id)
            ->andReturn(new Collection([$stockTransfer]));

        $mock->shouldReceive('warehouseManagerTransferOrRequestOrderStatusCount')
            ->once()
            ->with([StockTransferTypes::REQUEST_ORDER->value], $filterData, $this->company->id, $this->location->id)
            ->andReturn(new Collection([$stockTransfer]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getTransferStatusesData($request);

    expect($response['transfer_orders'][0])
        ->toHaveKeys(['id', 'name', 'count']);

    expect($response['request_orders'][0])
        ->toHaveKeys(['id', 'name', 'count']);
});

test(
    'getTransferStatusesData method throws an Exception when the store manager specify a different location',
    function (): void {
        $request = new Request([
            'warehouse_id' => $this->location->id,
            'location_id' => $this->location->id,
        ]);
        $request->setUserResolver(fn (): WarehouseManager => $this->warehouseManager);

        $this->warehouseManager->warehouses = $this->location->id;

        $this->mock(WarehouseManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndWarehouseId')
            ->once()
                ->with((int) $this->warehouseManager->id, (int) $this->location->id)
                ->andReturn(false);
        });

        $dashboardController = new DashboardController();
        $dashboardController->getTransferStatusesData($request);
    }
)->throws(HttpException::class);
