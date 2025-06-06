<?php

declare(strict_types=1);

use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Http\Controllers\Admin\PurchaseOrderFulfillmentController;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test('It calls the fetchPurchaseDeliveryOrders method and returns a proper response', function (): void {
    setCompanyIdInSession();

    $filterData = [
        'search_text' => '',
        'sort_by' => '',
        'sort_direction' => '',
        'per_page' => '',
        'select_status' => '',
        'select_order_type' => '',
        'date_range' => '',
        'location_id' => null,
    ];

    $request = new Request($filterData);

    $request->setUserResolver(fn (): Admin => new Admin([
        'employee_id' => 1,
    ]));

    $purchaseOrderFulfillmentQueries = $this->mock(PurchaseOrderFulfillmentQueries::class, function ($mock) use (
        $filterData
    ): void {
        $mock->shouldReceive('deliveryOrderListQuery')
            ->once()
            ->with($filterData, 1)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        $mock->shouldReceive('allDeliveryOrdersStatusCount')
            ->once()
            ->with($filterData, 1)
            ->andReturn(new Collection([]));
    });

    $purchaseOrderFulfillmentController = new PurchaseOrderFulfillmentController($purchaseOrderFulfillmentQueries);
    $response = $purchaseOrderFulfillmentController->fetchPurchaseDeliveryOrders($request);

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']->resource);
});
