<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\PurchaseOrder\DataObjects\WarehouseManagerApiPurchaseOrderData;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrderItem\DataObjects\WarehouseManagerApiPurchaseOrderItemData;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Domains\WarehouseManager\WarehouseManagerQueries;
use App\Http\Controllers\Api\WarehouseManager\PurchaseOrderController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\WarehouseManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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

test(
    'calls the getPaginatedPurchaseOrders and returns purchase order records',
    function (): void {
        $filterData = [
            'per_page' => 10,
            'page' => 1,
            'id' => $this->location->id,
            'start_date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->subDay()->format('Y-m-d'),
        ];

        $request = new Request();
        $request->setUserResolver(fn (): WarehouseManager => $this->warehouseManager);

        $warehouseManagerApiPurchaseOrderData = new WarehouseManagerApiPurchaseOrderData(...$filterData);

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

        $this->mock(PurchaseOrderQueries::class, function ($mock): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $purchaseOrderController = new PurchaseOrderController();

        $response = $purchaseOrderController->getPaginatedPurchaseOrders(
            $request,
            $warehouseManagerApiPurchaseOrderData
        );

        expect($response['data']->collection)->toBeInstanceOf(Collection::class);
    }
);

test(
    'getPaginatedPurchaseOrders throws an Exception when the warehouse manager specify a different warehouse',
    function (): void {
        $filterData = [
            'per_page' => 10,
            'page' => 1,
            'id' => $this->location->id,
            'start_date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->subDay()->format('Y-m-d'),
        ];

        $request = new Request();
        $request->setUserResolver(fn (): WarehouseManager => $this->warehouseManager);

        $warehouseManagerApiPurchaseOrderData = new WarehouseManagerApiPurchaseOrderData(...$filterData);

        $this->mock(WarehouseManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndWarehouseId')
                ->once()
                ->with((int) $this->warehouseManager->id, (int) $this->location->id)
                ->andReturn(false);
        });

        $purchaseOrderController = new PurchaseOrderController();

        $purchaseOrderController->getPaginatedPurchaseOrders($request, $warehouseManagerApiPurchaseOrderData);
    }
)->throws(HttpException::class);

test(
    'calls the getItemsByPurchaseOrderId and returns purchase order item records',
    function (): void {
        $filterData = [
            'id' => 1,
            'warehouse_id' => $this->location->id,
            'location_id' => $this->location->id,
            'per_page' => 10,
            'page' => 1,
        ];

        $request = new Request($filterData);
        $request->setUserResolver(fn (): WarehouseManager => $this->warehouseManager);

        $warehouseManagerApiPurchaseOrderItemData = new WarehouseManagerApiPurchaseOrderItemData(...$filterData);

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

        $this->mock(PurchaseOrderItemQueries::class, function ($mock): void {
            $mock->shouldReceive('getPaginatedByPurchaseOrderId')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $purchaseOrderController = new PurchaseOrderController();

        $response = $purchaseOrderController->getItemsByPurchaseOrderId(
            $request,
            $warehouseManagerApiPurchaseOrderItemData
        );

        expect($response['purchase_order_items']->collection)->toBeInstanceOf(Collection::class);
    }
);

test(
    'getItemsByPurchaseOrderId throws an Exception when the warehouse manager specify a different warehouse',
    function (): void {
        $filterData = [
            'id' => 1,
            'warehouse_id' => $this->location->id,
            'location_id' => $this->location->id,
            'per_page' => 10,
            'page' => 1,
        ];

        $request = new Request();
        $request->setUserResolver(fn (): WarehouseManager => $this->warehouseManager);

        $warehouseManagerApiPurchaseOrderItemData = new WarehouseManagerApiPurchaseOrderItemData(...$filterData);

        $this->mock(WarehouseManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndWarehouseId')
                ->once()
                ->with((int) $this->warehouseManager->id, (int) $this->location->id)
                ->andReturn(false);
        });

        $purchaseOrderController = new PurchaseOrderController();

        $purchaseOrderController->getItemsByPurchaseOrderId($request, $warehouseManagerApiPurchaseOrderItemData);
    }
)->throws(HttpException::class);

test('calls the getStatuses and returns statuses ', function (): void {
    $purchaseOrderController = new PurchaseOrderController();
    $response = $purchaseOrderController->getStatuses();
    expect($response['statuses'][0])
        ->toHaveKeys(['id', 'name', 'key']);
});

test('calls the getOrderTypes and returns order types ', function (): void {
    $purchaseOrderController = new PurchaseOrderController();
    $response = $purchaseOrderController->getStatuses();
    expect($response['statuses'][0])
        ->toHaveKeys(['id', 'name', 'key']);
});
