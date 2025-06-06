<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\PurchaseOrder\DataObjects\StoreManagerApiPurchaseOrderData;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrderItem\DataObjects\StoreManagerApiPurchaseOrderItemData;
use App\Domains\PurchaseOrderItem\PurchaseOrderItemQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Api\StoreManager\PurchaseOrderController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StoreManager;
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

    $this->storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
    ]);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
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
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $storeManagerApiPurchaseOrderData = new StoreManagerApiPurchaseOrderData(...$filterData);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->with((int) $this->storeManager->id, (int) $this->location->id)
                ->andReturn(true);
        });

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdOfStore')
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

        $response = $purchaseOrderController->getPaginatedPurchaseOrders($request, $storeManagerApiPurchaseOrderData);

        expect($response['data']->collection)->toBeInstanceOf(Collection::class);
    }
);

test(
    'getPaginatedPurchaseOrders throws an Exception when the store manager specify a different location',
    function (): void {
        $filterData = [
            'per_page' => 10,
            'page' => 1,
            'id' => $this->location->id,
            'start_date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->subDay()->format('Y-m-d'),
        ];

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $storeManagerApiPurchaseOrderData = new StoreManagerApiPurchaseOrderData(...$filterData);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->with((int) $this->storeManager->id, (int) $this->location->id)
                ->andReturn(false);
        });

        $purchaseOrderController = new PurchaseOrderController();

        $purchaseOrderController->getPaginatedPurchaseOrders($request, $storeManagerApiPurchaseOrderData);
    }
)->throws(HttpException::class);

test(
    'calls the getItemsByPurchaseOrderId and returns purchase order item records',
    function (): void {
        $filterData = [
            'id' => 1,
            'store_id' => $this->location->id,
            'location_id' => $this->location->id,
            'per_page' => 10,
            'page' => 1,
        ];

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $storeManagerApiPurchaseOrderItemData = new StoreManagerApiPurchaseOrderItemData(...$filterData);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->with((int) $this->storeManager->id, (int) $this->location->id)
                ->andReturn(true);
        });

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getCompanyIdOfStore')
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
            $storeManagerApiPurchaseOrderItemData
        );

        expect($response['purchase_order_items']->collection)->toBeInstanceOf(Collection::class);
    }
);

test(
    'getItemsByPurchaseOrderId throws an Exception when the store manager specify a different location',
    function (): void {
        $filterData = [
            'id' => 1,
            'store_id' => $this->location->id,
            'location_id' => $this->location->id,
            'per_page' => 10,
            'page' => 1,
        ];

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $storeManagerApiPurchaseOrderItemData = new StoreManagerApiPurchaseOrderItemData(...$filterData);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->with((int) $this->storeManager->id, (int) $this->location->id)
                ->andReturn(false);
        });

        $purchaseOrderController = new PurchaseOrderController();

        $purchaseOrderController->getItemsByPurchaseOrderId($request, $storeManagerApiPurchaseOrderItemData);
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
    $response = $purchaseOrderController->getOrderTypes();
    expect($response['order_types'][0])
        ->toHaveKeys(['id', 'name', 'key']);
});
