<?php

declare(strict_types=1);

use App\Domains\Inventory\Services\EcommerceOrderInventoryService;
use App\Domains\Order\DataObjects\OrderECommerceStatusData;
use App\Domains\Order\DataObjects\OrdersDataForApi;
use App\Domains\Order\DataObjects\OrderTrackingDetailsData;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\OrderQueries;
use App\Domains\OrderChannelReference\OrderChannelReferenceQueries;
use App\Http\Controllers\Api\SaleChannel\Order\OrderController;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\LoyaltyPointUpdate;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemUnit;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('updateStatus method call and return proper response', function (): void {
    $data = [
        'shipment_order_number' => 'test',
        'status' => OrderStatus::ACCEPTED->name,
        'order_id' => null,
        'tracking_number' => null,
        'external_order_id' => null,
    ];

    $orderData = new OrderECommerceStatusData(...$data);

    $order = Order::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'location_id' => 1,
        'member_id' => 1,
        'order_return_id' => 1,
        'cancel_order_reason_id' => 1,
        'receipt_number' => '123456',
        'status' => OrderStatus::PLACED,
    ]);

    $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
        $mock->shouldReceive('loadRelations')
            ->once()
            ->andReturn($order);
        $mock->shouldReceive('getByIdWithItemsAndStore')
            ->once()
            ->andReturn($order);
        $mock->shouldReceive('updateStatus')
            ->once();
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel();

    $orderController = new OrderController($orderQueries);
    $response = $orderController->updateStatus($orderData, $request);

    $this->assertEquals('order status update successfully.', $response['message']);
});

test('updateOrderTrackingDetails method call and return proper response', function (): void {
    $data = [
        'tracking_number' => '123456789',
        'courier_name' => 'leo logistics',
        'tracking_url' => 'http://test.com',
        'shipment_order_number' => 'ABCD',
    ];

    $orderData = new OrderTrackingDetailsData(...$data);

    $order = Order::factory()->make([
        'id' => 1,
        'store_manager_id' => 1,
        'location_id' => 1,
        'member_id' => 1,
        'order_return_id' => 1,
        'cancel_order_reason_id' => 1,
        'receipt_number' => '123456',
        'status' => OrderStatus::PLACED,
    ]);

    $orderQueries = $this->mock(OrderQueries::class, function ($mock): void {
        $mock->shouldReceive('updateTrackingDetails')
            ->once()
            ->andReturn(true);
    });

    [$saleChannel, $request] = setRequestUserForSaleChannel();

    $orderController = new OrderController($orderQueries);
    $response = $orderController->updateOrderTrackingDetails($orderData, $request, $order->getKey());

    $this->assertEquals('Order tracking details update successful.', $response['message']);
});

test(
    'updateStatus Method Returns Success for Status Transition from Placed to Accepted or Packing',
    function (string $orderStatusValue): void {
        $data = [
            'shipment_order_number' => 'AXS',
            'status' => $orderStatusValue,
            'order_id' => null,
            'tracking_number' => null,
            'external_order_id' => null,
        ];

        $orderData = new OrderECommerceStatusData(...$data);

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'receipt_number' => '123456',
            'status' => OrderStatus::PLACED,
        ]);

        $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($order);
            $mock->shouldReceive('getByIdWithItemsAndStore')
                ->once()
                ->andReturn($order);
            $mock->shouldReceive('updateStatus')
                ->once();
        });

        [$saleChannel,
            $request] = setRequestUserForSaleChannel();

        $orderController = new OrderController($orderQueries);
        $response = $orderController->updateStatus($orderData, $request);

        $this->assertEquals('order status update successfully.', $response['message']);
    }
)->with([OrderStatus::ACCEPTED->name, OrderStatus::PACKING->name]);

test(
    'updateStatus Method Returns Error for Invalid Status Transition from Placed to Anything Other Than Accepted or Packing',
    function (string $orderStatusValue): void {
        $data = [
            'shipment_order_number' => 'ABC',
            'status' => $orderStatusValue,
            'order_id' => null,
            'tracking_number' => null,
            'external_order_id' => null,
        ];

        $orderData = new OrderECommerceStatusData(...$data);

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'receipt_number' => '123456',
            'status' => OrderStatus::PLACED,
        ]);

        $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('getByIdWithItemsAndStore')
                ->once()
                ->andReturn($order);
            $mock->shouldNotReceive('updateStatus');
        });

        [$saleChannel,
            $request] = setRequestUserForSaleChannel();

        $orderController = new OrderController($orderQueries);
        $response = $orderController->updateStatus($orderData, $request);

        $this->assertEquals('order status update successfully.', $response['message']);
    }
)->with([
    OrderStatus::READY_FOR_PICKUP->name,
    OrderStatus::OUT_FOR_DELIVERY->name,
    OrderStatus::DELIVERED->name,
    OrderStatus::DECLINED->name,
    OrderStatus::RETURNED->name,
    OrderStatus::REFUNDED->name,
])->throws(HttpException::class, 'Invalid status transition.');

test(
    'updateStatus Method Returns Success for Valid Status Transition from Accepted to Packing, Cancelled, or Declined',
    function (string $orderStatusValue): void {
        $data = [
            'shipment_order_number' => 'ASDD',
            'status' => $orderStatusValue,
            'order_id' => null,
            'tracking_number' => null,
            'external_order_id' => null,
        ];

        $orderData = new OrderECommerceStatusData(...$data);

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'receipt_number' => '123456',
            'status' => OrderStatus::ACCEPTED,
        ]);

        $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($order);
            $mock->shouldReceive('getByIdWithItemsAndStore')
                ->once()
                ->andReturn($order);
            $mock->shouldReceive('updateStatus')
                ->once();
        });

        [$saleChannel,
            $request] = setRequestUserForSaleChannel();

        $orderController = new OrderController($orderQueries);
        $response = $orderController->updateStatus($orderData, $request);

        $this->assertEquals('order status update successfully.', $response['message']);
    }
)->with([OrderStatus::PACKING->name, OrderStatus::CANCELLED->name, OrderStatus::DECLINED->name]);

test(
    "updateStatus method should return an error if status transitions from 'accepted' to anything other than 'packing', 'cancelled', or 'declined'",
    function (string $orderStatusValue): void {
        $data = [
            'shipment_order_number' => 'XYZ',
            'status' => $orderStatusValue,
            'order_id' => null,
            'tracking_number' => null,
            'external_order_id' => null,
        ];

        $orderData = new OrderECommerceStatusData(...$data);

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'receipt_number' => '123456',
            'status' => OrderStatus::ACCEPTED,
        ]);

        $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('getByIdWithItemsAndStore')
                ->once()
                ->andReturn($order);
            $mock->shouldNotReceive('updateStatus');
        });

        [$saleChannel,
            $request] = setRequestUserForSaleChannel();

        $orderController = new OrderController($orderQueries);
        $response = $orderController->updateStatus($orderData, $request);

        $this->assertEquals('order status update successfully.', $response['message']);
    }
)->with([
    OrderStatus::PLACED->name,
    OrderStatus::READY_FOR_PICKUP->name,
    OrderStatus::OUT_FOR_DELIVERY->name,
    OrderStatus::DELIVERED->name,
    OrderStatus::RETURNED->name,
    OrderStatus::REFUNDED->name,
])->throws(HttpException::class, 'Invalid status transition.');

test(
    'updateStatus Method Returns Success for Valid Status Transition from Packing to ready_for_pickup',
    function (string $orderStatusValue): void {
        $data = [
            'shipment_order_number' => 'SADDS',
            'status' => $orderStatusValue,
            'order_id' => null,
            'tracking_number' => null,
            'external_order_id' => null,
        ];

        $orderData = new OrderECommerceStatusData(...$data);

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'receipt_number' => '123456',
            'status' => OrderStatus::PACKING,
        ]);

        $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($order);
            $mock->shouldReceive('getByIdWithItemsAndStore')
                ->once()
                ->andReturn($order);
            $mock->shouldReceive('updateStatus')
                ->once();
        });

        [$saleChannel,
            $request] = setRequestUserForSaleChannel();

        $orderController = new OrderController($orderQueries);
        $response = $orderController->updateStatus($orderData, $request);

        $this->assertEquals('order status update successfully.', $response['message']);
    }
)->with([OrderStatus::READY_FOR_PICKUP->name]);

test(
    "updateStatus method should return an error if status transitions from 'packing' to anything other than 'ready_for_pickup'",
    function (string $orderStatusValue): void {
        $data = [
            'shipment_order_number' => '2323',
            'status' => $orderStatusValue,
            'order_id' => null,
            'tracking_number' => null,
            'external_order_id' => null,
        ];

        $orderData = new OrderECommerceStatusData(...$data);

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'receipt_number' => '123456',
            'status' => OrderStatus::PACKING,
        ]);

        $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('getByIdWithItemsAndStore')
                ->once()
                ->andReturn($order);
            $mock->shouldNotReceive('updateStatus');
        });

        [$saleChannel,
            $request] = setRequestUserForSaleChannel();

        $orderController = new OrderController($orderQueries);
        $response = $orderController->updateStatus($orderData, $request);

        $this->assertEquals('order status update successfully.', $response['message']);
    }
)->with([
    OrderStatus::PLACED->name,
    OrderStatus::CANCELLED->name,
    OrderStatus::DECLINED->name,
    OrderStatus::ACCEPTED->name,
    OrderStatus::OUT_FOR_DELIVERY->name,
    OrderStatus::DELIVERED->name,
    OrderStatus::RETURNED->name,
    OrderStatus::REFUNDED->name,
])->throws(HttpException::class, 'Invalid status transition.');

test(
    'updateStatus Method Returns Success for Valid Status Transition from ready_for_pickup to out_for_delivery',
    function (string $orderStatusValue): void {
        $data = [
            'shipment_order_number' => 'test',
            'status' => $orderStatusValue,
            'order_id' => null,
            'tracking_number' => null,
            'external_order_id' => null,
        ];

        $orderData = new OrderECommerceStatusData(...$data);

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'receipt_number' => '123456',
            'status' => OrderStatus::READY_FOR_PICKUP,
        ]);

        $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($order);
            $mock->shouldReceive('getByIdWithItemsAndStore')
                ->once()
                ->andReturn($order);
            $mock->shouldReceive('updateStatus')
                ->once();
        });

        [$saleChannel,
            $request] = setRequestUserForSaleChannel();

        $orderController = new OrderController($orderQueries);
        $response = $orderController->updateStatus($orderData, $request);

        $this->assertEquals('order status update successfully.', $response['message']);
    }
)->with([OrderStatus::OUT_FOR_DELIVERY->name]);

test(
    "updateStatus method should return an error if status transitions from 'ready_for_pickup' to anything other than 'out_for_delivery'",
    function (string $orderStatusValue): void {
        $data = [
            'shipment_order_number' => 'test',
            'status' => $orderStatusValue,
            'order_id' => null,
            'tracking_number' => null,
            'external_order_id' => null,
        ];

        $orderData = new OrderECommerceStatusData(...$data);

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'receipt_number' => '123456',
            'status' => OrderStatus::READY_FOR_PICKUP,
        ]);

        $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('getByIdWithItemsAndStore')
                ->once()
                ->andReturn($order);
            $mock->shouldNotReceive('updateStatus');
        });

        [$saleChannel,
            $request] = setRequestUserForSaleChannel();

        $orderController = new OrderController($orderQueries);
        $response = $orderController->updateStatus($orderData, $request);

        $this->assertEquals('order status update successfully.', $response['message']);
    }
)->with([
    OrderStatus::PLACED->name,
    OrderStatus::CANCELLED->name,
    OrderStatus::DECLINED->name,
    OrderStatus::ACCEPTED->name,
    OrderStatus::PACKING->name,
    OrderStatus::DELIVERED->name,
    OrderStatus::RETURNED->name,
    OrderStatus::REFUNDED->name,
])->throws(HttpException::class, 'Invalid status transition.');

test(
    'updateStatus Method Returns Success for Valid Status Transition from out_for_delivery to delivered',
    function (string $orderStatusValue): void {
        $data = [
            'shipment_order_number' => 'test',
            'status' => $orderStatusValue,
            'order_id' => null,
            'tracking_number' => null,
            'external_order_id' => null,
        ];

        $orderData = new OrderECommerceStatusData(...$data);

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'receipt_number' => '123456',
            'status' => OrderStatus::OUT_FOR_DELIVERY,
        ]);

        $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($order);
            $mock->shouldReceive('getByIdWithItemsAndStore')
                ->once()
                ->andReturn($order);
            $mock->shouldReceive('updateStatus')
                ->once();
        });

        [$saleChannel,
            $request] = setRequestUserForSaleChannel();

        $orderController = new OrderController($orderQueries);
        $response = $orderController->updateStatus($orderData, $request);

        $this->assertEquals('order status update successfully.', $response['message']);
    }
)->with([OrderStatus::DELIVERED->name]);

test(
    "updateStatus method should return an error if status transitions from 'out_for_delivery' to anything other than 'delivered'",
    function (string $orderStatusValue): void {
        $data = [
            'shipment_order_number' => 'test',
            'status' => $orderStatusValue,
            'order_id' => null,
            'tracking_number' => null,
            'external_order_id' => null,
        ];

        $orderData = new OrderECommerceStatusData(...$data);

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'receipt_number' => '123456',
            'status' => OrderStatus::OUT_FOR_DELIVERY,
        ]);

        $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('getByIdWithItemsAndStore')
                ->once()
                ->andReturn($order);
            $mock->shouldNotReceive('updateStatus');
        });

        [$saleChannel,
            $request] = setRequestUserForSaleChannel();

        $orderController = new OrderController($orderQueries);
        $response = $orderController->updateStatus($orderData, $request);

        $this->assertEquals('order status update successfully.', $response['message']);
    }
)->with([
    OrderStatus::PLACED->name,
    OrderStatus::CANCELLED->name,
    OrderStatus::DECLINED->name,
    OrderStatus::ACCEPTED->name,
    OrderStatus::PACKING->name,
    OrderStatus::READY_FOR_PICKUP->name,
    OrderStatus::RETURNED->name,
    OrderStatus::REFUNDED->name,
    OrderStatus::REFUNDED->name,
])->throws(HttpException::class, 'Invalid status transition.');

test(
    'updateStatus Method Returns Success for Valid Status Transition from delivered to returned',
    function (string $orderStatusValue): void {
        $data = [
            'shipment_order_number' => 'test',
            'status' => $orderStatusValue,
            'order_id' => null,
            'tracking_number' => null,
            'external_order_id' => null,
        ];

        $orderData = new OrderECommerceStatusData(...$data);

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'receipt_number' => '123456',
            'status' => OrderStatus::DELIVERED,
        ]);

        $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($order);
            $mock->shouldReceive('getByIdWithItemsAndStore')
                ->once()
                ->andReturn($order);
            $mock->shouldReceive('updateStatus')
                ->once();
        });

        [$saleChannel,
            $request] = setRequestUserForSaleChannel();

        $orderController = new OrderController($orderQueries);
        $response = $orderController->updateStatus($orderData, $request);

        $this->assertEquals('order status update successfully.', $response['message']);
    }
)->with([OrderStatus::RETURNED->name]);

test(
    "updateStatus method should return an error if status transitions from 'delivered' to anything other than 'returned'",
    function (string $orderStatusValue): void {
        $data = [
            'shipment_order_number' => 'test',
            'status' => $orderStatusValue,
            'order_id' => null,
            'tracking_number' => null,
            'external_order_id' => null,
        ];

        $orderData = new OrderECommerceStatusData(...$data);

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'receipt_number' => '123456',
            'status' => OrderStatus::DELIVERED,
        ]);

        $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('getByIdWithItemsAndStore')
                ->once()
                ->andReturn($order);
            $mock->shouldNotReceive('updateStatus');
        });

        [$saleChannel,
            $request] = setRequestUserForSaleChannel();

        $orderController = new OrderController($orderQueries);
        $response = $orderController->updateStatus($orderData, $request);

        $this->assertEquals('order status update successfully.', $response['message']);
    }
)->with([
    OrderStatus::PLACED->name,
    OrderStatus::CANCELLED->name,
    OrderStatus::DECLINED->name,
    OrderStatus::ACCEPTED->name,
    OrderStatus::PACKING->name,
    OrderStatus::READY_FOR_PICKUP->name,
    OrderStatus::OUT_FOR_DELIVERY->name,
    OrderStatus::REFUNDED->name,
])->throws(HttpException::class, 'Invalid status transition.');

test(
    'updateStatus Method Returns Success for Valid Status Transition',
    function (OrderStatus $orderStatus, string $orderStatusValue): void {
        $data = [
            'shipment_order_number' => 'test',
            'status' => $orderStatusValue,
            'order_id' => null,
            'tracking_number' => null,
            'external_order_id' => null,
        ];

        $orderData = new OrderECommerceStatusData(...$data);

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'receipt_number' => '123456',
            'status' => $orderStatus,
        ]);

        $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($order);
            $mock->shouldReceive('getByIdWithItemsAndStore')
                ->once()
                ->andReturn($order);
            $mock->shouldReceive('updateStatus')
                ->once();
        });

        [$saleChannel,
            $request] = setRequestUserForSaleChannel();

        $orderController = new OrderController($orderQueries);
        $response = $orderController->updateStatus($orderData, $request);

        $this->assertEquals('order status update successfully.', $response['message']);
    }
)->with([
    [OrderStatus::CANCELLED, OrderStatus::REFUNDED->name],
    [OrderStatus::RETURNED, OrderStatus::REFUNDED->name],
    [OrderStatus::DECLINED, OrderStatus::REFUNDED->name],
]);

test(
    'updateStatus method should return an error if status transitions',
    function (OrderStatus $orderStatus, string $orderStatusValue): void {
        $data = [
            'shipment_order_number' => 'test',
            'status' => $orderStatusValue,
            'order_id' => null,
            'tracking_number' => null,
            'external_order_id' => null,
        ];

        $orderData = new OrderECommerceStatusData(...$data);

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'receipt_number' => '123456',
            'status' => $orderStatus,
        ]);

        $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('getByIdWithItemsAndStore')
                ->once()
                ->andReturn($order);
            $mock->shouldNotReceive('updateStatus');
        });

        [$saleChannel,
            $request] = setRequestUserForSaleChannel();

        $orderController = new OrderController($orderQueries);
        $response = $orderController->updateStatus($orderData, $request);

        $this->assertEquals('order status update successfully.', $response['message']);
    }
)->with([
    [OrderStatus::CANCELLED, OrderStatus::PLACED->name],
    [OrderStatus::CANCELLED, OrderStatus::ACCEPTED->name],
    [OrderStatus::CANCELLED, OrderStatus::PACKING->name],
    [OrderStatus::CANCELLED, OrderStatus::READY_FOR_PICKUP->name],
    [OrderStatus::CANCELLED, OrderStatus::OUT_FOR_DELIVERY->name],
    [OrderStatus::CANCELLED, OrderStatus::DELIVERED->name],
    [OrderStatus::CANCELLED, OrderStatus::DECLINED->name],
    [OrderStatus::CANCELLED, OrderStatus::RETURNED->name],
    [OrderStatus::CANCELLED, OrderStatus::PLACED->name],

    [OrderStatus::DECLINED, OrderStatus::ACCEPTED->name],
    [OrderStatus::DECLINED, OrderStatus::PACKING->name],
    [OrderStatus::DECLINED, OrderStatus::READY_FOR_PICKUP->name],
    [OrderStatus::DECLINED, OrderStatus::OUT_FOR_DELIVERY->name],
    [OrderStatus::DECLINED, OrderStatus::DELIVERED->name],
    [OrderStatus::DECLINED, OrderStatus::CANCELLED->name],
    [OrderStatus::DECLINED, OrderStatus::RETURNED->name],

    [OrderStatus::RETURNED, OrderStatus::ACCEPTED->name],
    [OrderStatus::RETURNED, OrderStatus::PACKING->name],
    [OrderStatus::RETURNED, OrderStatus::READY_FOR_PICKUP->name],
    [OrderStatus::RETURNED, OrderStatus::OUT_FOR_DELIVERY->name],
    [OrderStatus::RETURNED, OrderStatus::DELIVERED->name],
    [OrderStatus::RETURNED, OrderStatus::CANCELLED->name],
    [OrderStatus::RETURNED, OrderStatus::DECLINED->name],
])->throws(HttpException::class, 'Invalid status transition.');

test(
    'updateStatus Method call and rollback inventory when order status is',
    function (OrderStatus $orderStatus, int $orderStatusValue): void {
        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'name' => 'ABC',
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'upc' => 'abd123',
        ]);

        $inventory = Inventory::factory()->make([
            'id' => 1,
            'product_id' => $product->id,
            'location_id' => 1,
        ]);

        $inventoryUnit = InventoryUnit::factory()->make([
            'id' => 1,
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 1,
        ]);

        $inventory->inventoryUnits = collect([$inventoryUnit]);
        $product->inventory = $inventory;

        $member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => $member->id,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'receipt_number' => '123456',
            'status' => $orderStatusValue,
        ]);

        $order->member = $member;

        $loyaltyPointUpdates = LoyaltyPointUpdate::factory()->make([
            'id' => 1,
            'member_id' => $member->id,
            'affected_by_id' => 1,
        ]);

        $orderItem = OrderItem::factory()->make([
            'id' => 1,
            'order_id' => $order->id,
            'product_id' => $product->id,
            'exchange_item_id' => 1,
            'complimentary_item_reason_id' => 1,
        ]);

        $orderItemUnit = OrderItemUnit::factory()->make([
            'id' => 1,
            'order_item_id' => $orderItem->id,
            'inventory_id' => $inventory->id,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
        ]);

        $orderItem->orderItemUnits = collect([$orderItemUnit]);
        $order->orderItems = collect([$orderItem]);

        [
            $saleChannel,
            $request
        ] = setRequestUserForSaleChannel();

        $saleChannelInventoryRollbackOrderStatus1 = [
            'sale_channel_id' => $saleChannel->id,
            'order_status' => OrderStatus::CANCELLED->value,
        ];

        $saleChannelInventoryRollbackOrderStatus2 = [
            'sale_channel_id' => $saleChannel->id,
            'order_status' => OrderStatus::DECLINED->value,
        ];

        $saleChannel->saleChannelInventoryRollbackOrderStatus = collect(
            [$saleChannelInventoryRollbackOrderStatus1, $saleChannelInventoryRollbackOrderStatus2]
        );

        $data = [
            'shipment_order_number' => 'test',
            'status' => $orderStatus->name,
            'order_id' => null,
            'tracking_number' => null,
            'external_order_id' => null,
        ];

        $orderData = new OrderECommerceStatusData(...$data);

        $orderQueries = $this->mock(OrderQueries::class, function ($mock) use ($order): void {
            $mock->shouldReceive('getByIdWithItemsAndStore')
                ->once()
                ->andReturn($order);

            $mock->shouldReceive('updateStatus')
                ->once();

            $mock->shouldReceive('loadRelations')
                ->once()
                ->andReturn($order);
        });

        $this->mock(EcommerceOrderInventoryService::class, function ($mock) use ($order, $saleChannel): void {
            $mock->shouldReceive('rollBackInventory')
                ->with($order, $saleChannel)
                ->once();

            $mock->shouldReceive('checkAndRevertLoyaltyPoints')
                ->with($order)
                ->once();

            $mock->shouldReceive('revertUsedLoyaltyPoints')
                ->with($order)
                ->once();

            $mock->shouldReceive('checkAndRevertVouchersGenerated')
                ->with($order->id, $order->location_id)
                ->once();

            $mock->shouldReceive('checkAndRevertUsedVoucher')
                ->with($order->id, $order->location_id)
                ->once();
        });

        $orderController = new OrderController($orderQueries);
        $response = $orderController->updateStatus($orderData, $request);

        $this->assertEquals('order status update successfully.', $response['message']);
    }
)->with([
    [OrderStatus::CANCELLED, OrderStatus::ACCEPTED->value],
    [OrderStatus::DECLINED, OrderStatus::ACCEPTED->value],
]);

test('getOrderIds method call and return proper response', function (): void {
    $externalOrderId = '12345';

    $orderIds = collect([1, 2, 3]);

    $this->mock(OrderChannelReferenceQueries::class, function ($mock) use ($orderIds): void {
        $mock->shouldReceive('getOrderIdsByExternalOrderId')
            ->once()
            ->andReturn($orderIds);
    });

    $orderController = new OrderController($this->mock(OrderQueries::class));
    $response = $orderController->getOrderIds($externalOrderId);

    $this->assertEquals([
        'order_ids' => $orderIds,
    ], $response);
});

test('It calls the getPaginatedOrders method and returns order records', function (): void {
    [$saleChannel, $request] = setRequestUserForSaleChannel();

    $paginatedOrders = [
        'page' => 1,
        'per_page' => 10,
        'sort_by' => '',
        'sort_direction' => '',
    ];

    $ordersDataForApi = new OrdersDataForApi(...$paginatedOrders);

    $orderQueries = $this->mock(OrderQueries::class, function ($mock): void {
        $mock->shouldReceive('getPaginatedOrders')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 1, 10));
    });

    $orderController = new OrderController($orderQueries);
    $response = $orderController->getPaginatedOrders($request, $ordersDataForApi);

    expect($response)->toHaveKey('orders');
});

test('getOrderDetailsById returns order details for valid order and sale channel', function (): void {
    [$saleChannel, $request] = setRequestUserForSaleChannel();

    $orderId = 1;
    $orderQueries = $this->mock(OrderQueries::class, function ($mock): void {
        $mock->shouldReceive('getOrderByIdAndSaleChannelIdWithRelation')
            ->once()
            ->andReturn(new Order());
    });

    $orderController = new OrderController($orderQueries);
    $response = $orderController->getOrderDetailsById($request, $orderId);

    expect($response)->toHaveKey('order');
});
