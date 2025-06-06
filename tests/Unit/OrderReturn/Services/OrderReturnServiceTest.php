<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\OrderItem\OrderItemQueries;
use App\Domains\OrderReturn\DataObjects\OrderReturnData;
use App\Domains\OrderReturn\Services\CheckOrderReturnDetailsService;
use App\Domains\OrderReturn\Services\OrderReturnService;
use App\Domains\OrderReturn\Services\SaveOrderReturnDetailsService;
use App\Domains\SaleReturnReason\SaleReturnReasonQueries;
use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SaleReturnReason;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->orderDetails = [
        'order_return_items' => [
            [
                'order_item_id' => 1,
                'price_paid_per_unit' => '11.00',
                'return_quantity' => '1',
                'order_return_reason_id' => 1,
            ],
        ],
        'member_id' => 1,
    ];

    $this->orderReturnData = new OrderReturnData(...$this->orderDetails);

    $this->companyId = 1;

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->checkOrderReturnDetailsService = new CheckOrderReturnDetailsService();
    $this->saveOrderReturnDetailsService = new SaveOrderReturnDetailsService();
    $this->orderReturnService = new OrderReturnService();
    $this->orderReturnService->returnedOrderItems = collect($this->orderReturnData->order_return_items);
    $this->orderReturnService->orderReturnItems = collect($this->orderReturnData->order_return_items);
});

test(
    'it calls the getByIdsWithRelations method of OrderItemQueries class',
    function (): void {
        $this->mock(OrderItemQueries::class, function ($mock): void {
            $mock->shouldReceive('getByIdsWithRelations')
                ->once()
                ->andReturn(collect([]));
        });

        $mock = $this->createPartialMock(OrderReturnService::class, ['hasReturnItems']);

        $mock->expects($this->once())
            ->method('hasReturnItems')
            ->will($this->returnValue(true));

        $response = $mock->getReturnedOrderItems([1]);
        $this->assertTrue($response->toArray() === []);
    }
);

test('getReturnedOrderItems method returns null when cart return items not set', function (): void {
    $mock = $this->createPartialMock(OrderReturnService::class, ['hasReturnItems']);

    $mock->expects($this->once())
        ->method('hasReturnItems')
        ->will($this->returnValue(false));

    $response = $mock->getReturnedOrderItems([1]);
    $this->assertTrue($response->toArray() === []);
});

test('getReturnReasonIds method returns the sale return reason ids', function (): void {
    $response = $this->orderReturnService->getReturnReasonIds([1]);

    expect($response)
        ->toHaveKey(0, 1);
});

test('it calls the getByIdsAndCompanyId method of SaleReturnReasonQueries class', function (): void {
    $this->mock(SaleReturnReasonQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIdsAndCompanyIdForOrderReturn')
            ->once()
            ->andReturn(collect([]));
    });

    $mock = $this->createPartialMock(OrderReturnService::class, ['hasReturnItems']);

    $this->checkOrderReturnDetailsService->companyId = 1;
    $mock->checkOrderReturnDetailsService = $this->checkOrderReturnDetailsService;

    $mock->expects($this->once())
        ->method('hasReturnItems')
        ->will($this->returnValue(true));

    $response = $mock->getOrderReturnReasons([1]);
    $this->assertTrue($response->toArray() === []);
});

test('getOrderReturnReasons method returns null when cart return items not set', function (): void {
    $mock = $this->createPartialMock(OrderReturnService::class, ['hasReturnItems']);

    $mock->expects($this->once())
        ->method('hasReturnItems')
        ->will($this->returnValue(false));

    $response = $mock->getOrderReturnReasons([1]);
    $this->assertTrue($response->toArray() === []);
});

test('checkReturnItems method throws an exception when return pending layaway sale', function (): void {
    $orderItem = OrderItem::factory()->make([
        'id' => 1,
        'order_id' => 1,
        'product_id' => 1,
        'total_price_paid' => 100,
        'quantity' => 10,
        'exchange_item_id' => null,
        'complimentary_item_reason_id' => 1,
    ]);

    $orderItem->order = Order::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'store_manager_id' => 1,
        'location_id' => 1,
        'layaway_pending_amount' => 100,
        'type_id' => OrderTypes::PENDING_LAYAWAY_ORDER,
        'order_return_id' => 1,
        'cancel_order_reason_id' => 1,
    ]);

    $this->orderReturnService->returnedOrderItems = collect([$orderItem]);
    $this->orderReturnService->checkReturnItems();
})->throws(HttpException::class, 'Pending Layaway order cannot be returned.');

test(
    'checkReturnItems method throws an exception when sale return reasons does not available in our records',
    function (): void {
        $orderItem = OrderItem::factory()->make([
            'id' => 1,
            'order_id' => 1,
            'product_id' => 1,
            'total_price_paid' => 100,
            'quantity' => 10,
            'exchange_item_id' => null,
            'complimentary_item_reason_id' => 1,
        ]);

        $orderItem->order = Order::factory()->make([
            'id' => 1,
            'member_id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'layaway_pending_amount' => 100,
            'type_id' => OrderTypes::REGULAR_ORDER,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
        ]);

        $this->location->orders_return_days_limit = 10;
        $this->checkOrderReturnDetailsService->location = $this->location;

        $orderItem->product = commonGetProductDetails(false);

        $this->orderReturnService->returnedOrderItems = collect([$orderItem]);
        $this->orderReturnService->orderReturnReasons = collect([]);
        $this->orderReturnService->checkOrderReturnDetailsService = $this->checkOrderReturnDetailsService;
        $this->orderReturnService->checkReturnItems(1, true);
    }
)->throws(HttpException::class, 'Some of the order return reasons are not available in our records.');

test('checkReturnItems method returns the response as expected', function (): void {
    $this->location->orders_return_days_limit = 10;
    $this->checkOrderReturnDetailsService->location = $this->location;

    $orderItem = OrderItem::factory()->make([
        'id' => 1,
        'order_id' => 1,
        'product_id' => 1,
        'total_price_paid' => 100,
        'quantity' => 10,
        'exchange_item_id' => null,
        'complimentary_item_reason_id' => 1,
    ]);

    $orderItem->order = Order::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'store_manager_id' => 1,
        'location_id' => 1,
        'layaway_pending_amount' => 100,
        'type_id' => OrderTypes::REGULAR_ORDER,
        'order_return_id' => 1,
        'cancel_order_reason_id' => 1,
    ]);

    $this->orderReturnData = new OrderReturnData(...$this->orderDetails);

    $orderItem->order->payments = collect([]);

    $orderItem->product = commonGetProductDetails(false);

    $saleReturnReason[] = SaleReturnReason::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $saleReturnReason[] = SaleReturnReason::factory()->make([
        'id' => 2,
        'company_id' => 1,
    ]);

    $mock = $this->createPartialMock(OrderReturnService::class, ['getReturnReasonIds']);

    $mock->expects($this->once())
        ->method('getReturnReasonIds')
        ->will($this->returnValue([1, 2]));

    $mock->returnedOrderItems = collect([$orderItem]);
    $mock->orderReturnReasons = collect($saleReturnReason);
    $mock->orderReturnItems = collect($this->orderReturnData->order_return_items);
    $this->checkOrderReturnDetailsService->orderReturnData = $this->orderReturnData;
    $mock->checkOrderReturnDetailsService = $this->checkOrderReturnDetailsService;

    $response = $mock->checkReturnItems(1, true);

    $this->assertNull($response);
});
