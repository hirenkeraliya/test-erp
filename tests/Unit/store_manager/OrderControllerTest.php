<?php

declare(strict_types=1);

use App\Domains\Order\OrderQueries;
use App\Domains\OrderAddress\Enums\OrderAddressesType;
use App\Domains\OrderAddress\OrderAddressQueries;
use App\Domains\OrderItem\Resources\OrderItemsReportResource;
use App\Http\Controllers\StoreManager\OrderController;
use App\Models\Order;
use App\Models\OrderAddress;
use Illuminate\Http\Request;

test('fetchOrderItemsEcommerceByOrderId method call and returns proper response', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $locationId = 1;
    setStoreIdInSession($locationId);

    $requestParameter = [
        'order_id' => '1',
    ];

    $orderQueries = $this->mock(OrderQueries::class, function ($mock) use (
        $requestParameter,
        $locationId,
        $companyId
    ): void {
        $mock->shouldReceive('getOrderItemsForEcommerce')
            ->once()
            ->with($requestParameter['order_id'], $locationId, $companyId)
            ->andReturn(new Order());
    });
    $orderController = new OrderController($orderQueries);

    $response = $orderController->fetchOrderItemsEcommerceByOrderId(new Request($requestParameter));
    expect($response)->toBeArray();
    $this->assertArrayHasKey('order_details', $response);
    $this->assertInstanceOf(OrderItemsReportResource::class, $response['order_details']);
});

test('fetchOrderAddress can return shipping address', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $request = new Request([
        'order_id' => '1',
        'type' => OrderAddressesType::SHIPPING_ADDRESS->value,
    ]);

    $orders = Order::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'location_id' => 1,
        'member_id' => 1,
        'order_return_id' => 1,
        'cancel_order_reason_id' => 1,
        'receipt_number' => '123456',
    ]);

    $orderAddress = OrderAddress::factory()->make([
        'order_id' => $orders->id,
        'type_id' => OrderAddressesType::SHIPPING_ADDRESS->value,
        'address_line_1' => '123 Main St',
        'address_line_2' => 'Apt 4B',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '1234567890',
        'area_code' => '12345',
    ]);

    $this->mock(OrderAddressQueries::class, function ($mock) use ($orderAddress): void {
        $mock->shouldReceive('getOrderAddress')
            ->once()
            ->andReturn($orderAddress);
    });

    $orderController = resolve(OrderController::class);

    $response = $orderController->fetchOrderAddress($request);
    expect($response)->toBeArray();
});
