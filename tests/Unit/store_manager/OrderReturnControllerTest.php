<?php

declare(strict_types=1);

use App\Domains\OrderReturn\OrderReturnQueries;
use App\Domains\OrderReturn\Resources\OrderReturnListResource;
use App\Http\Controllers\StoreManager\OrderReturnController;
use App\Models\OrderReturn;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('fetchOrderReturns method call and returns proper response', function (): void {
    $requestParameter = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
        'date_range' => null,
        'member_id' => null,
    ];

    $orderReturnQueries = $this->mock(OrderReturnQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getPaginatedCompleteOrderWithRelations')
            ->once()
            ->with($requestParameter, 1, 1)
            ->andReturn(new LengthAwarePaginator([], 20, 15));
        $mock->shouldReceive('getFilteredTotalsForReport')
            ->once()
            ->with($requestParameter, 1, 1)
            ->andReturn(collect());
    });

    $requestParameter['location_id'] = 1;
    $requestParameter['store_manager_id'] = 1;

    $storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => 1,
    ]);

    $orderReturnController = new OrderReturnController($orderReturnQueries);

    $request = new Request($requestParameter);
    $request->setUserResolver(fn (): StoreManager => $storeManager);

    $response = $orderReturnController->fetchOrderReturns($request);

    $this->assertEquals(20, $response['total_records']);
    $this->assertEquals(OrderReturnListResource::collection(collect([])), $response['data']);
});

test('fetchOrderReturnItems method call and returns proper response', function (): void {
    setStoreIdInSession();
    $orderReturnQueries = $this->mock(OrderReturnQueries::class, function ($mock): void {
        $mock->shouldReceive('getOrderReturnItemsForStoreManager')
            ->once()
            ->with(1, 1)
            ->andReturn(new OrderReturn());
    });

    $orderReturnController = new OrderReturnController($orderReturnQueries);
    $response = $orderReturnController->fetchOrderReturnItems(1);
    $this->assertEquals(new OrderReturn(), $response['order_return_details']->resource);
});

test('fetchOrderReturnsForReceipt method call and returns proper response', function (): void {
    setStoreIdInSession();
    $orderReturnQueries = $this->mock(OrderReturnQueries::class, function ($mock): void {
        $mock->shouldReceive('getOrderReturnReceiptForStoreManager')
            ->once()
            ->with(1, 1)
            ->andReturn(new OrderReturn());
    });

    $orderReturnController = new OrderReturnController($orderReturnQueries);
    $response = $orderReturnController->fetchOrderReturnsForReceipt(1);
    $this->assertEquals(new OrderReturn(), $response['order_return_details']->resource);
});
