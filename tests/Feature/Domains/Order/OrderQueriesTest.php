<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\DataObjects\OrderECommerceData;
use App\Domains\Order\DataObjects\OrderTrackingDetailsData;
use App\Domains\Order\Enums\OrderChannels;
use App\Domains\Order\Enums\OrderPickingStatus;
use App\Domains\Order\Enums\OrderReportTypes;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\Order\OrderQueries;
use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPickingList;
use App\Models\OrderPickingListItem;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\SaleChannel;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    $this->company = Company::factory()->create([
        'name' => 'Order Test',
    ]);

    $this->date = Carbon::now();

    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->getKey(),
    ]);

    $this->storeManager = StoreManager::factory()->create([
        'employee_id' => $this->employee->getKey(),
    ]);

    $this->location = Location::factory()->create([
        'id' => 1,
        'company_id' => $this->company->getKey(),
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->member = Member::factory()->create([
        'company_id' => $this->company->getKey(),
        'created_location_id' => $this->location->getKey(),
    ]);

    $this->product = Product::factory()->create([
        'company_id' => $this->company->getKey(),
    ]);

    $this->order = Order::factory()->create([
        'store_manager_id' => $this->storeManager->getKey(),
        'location_id' => $this->location->getKey(),
        'member_id' => $this->member->getKey(),
        'order_return_id' => null,
        'cancel_order_reason_id' => null,
        'created_at' => $this->date,
    ]);

    $this->orderItems = OrderItem::factory()->create([
        'order_id' => $this->order->getKey(),
        'product_id' => $this->product->getKey(),
        'exchange_item_id' => null,
    ]);

    $this->orderQueries = new OrderQueries();
});

test('getOrderWithStoreAndItemsForCompleteLayaway method returns the order', function (): void {
    $this->order->type_id = OrderTypes::PENDING_LAYAWAY_ORDER;
    $response = $this->orderQueries->getOrderWithStoreAndItemsForCompleteLayaway(
        $this->order->getKey(),
        $this->storeManager->getKey(),
        $this->location->getKey(),
        $this->company->getKey(),
    );

    expect($response)->toBeInstanceOf(Order::class);
    expect($response)->toHaveKeys([...$this->orderQueries->getBasicColumns(), 'order_items']);
});

test(
    'updateLayawayPendingAmountAndStatus method updates the order layaway pending amount and type id',
    function (): void {
        $this->orderQueries->updateLayawayPendingAmountAndStatus($this->order, 100);

        $this->assertDatabaseHas(Order::class, [
            'id' => $this->order->getKey(),
            'layaway_pending_amount' => 100,
            'type_id' => OrderTypes::PENDING_LAYAWAY_ORDER->value,
        ]);
    }
);

test(
    'updateLayawayAmountOf method updates the order layaway pending amount, Amount Paid, total amount before round off and type id',
    function (): void {
        $this->order->layaway_pending_amount = 100;
        $this->order->save();

        $payments = collect([
            [
                'type_id' => PaymentType::factory()->create([
                    'company_id' => $this->company->getKey(),
                ])->getKey(),
                'amount' => 100,
            ],
        ]);
        $this->orderQueries->updateLayawayAmountOf($this->order, $payments);

        $this->assertDatabaseHas(Order::class, [
            'id' => $this->order->getKey(),
            'layaway_pending_amount' => null,
            'type_id' => OrderTypes::COMPLETE_LAYAWAY_ORDER->value,
        ]);
    }
);

test('getOrderWithStoreAndItemsForCompleteCredit method returns the order', function (): void {
    $this->order->type_id = OrderTypes::PENDING_CREDIT_ORDER;
    $response = $this->orderQueries->getOrderWithStoreAndItemsForCompleteCredit(
        $this->order->getKey(),
        $this->storeManager->getKey(),
        $this->location->getKey(),
        $this->company->getKey(),
    );

    expect($response)->toBeInstanceOf(Order::class);
    expect($response)->toHaveKeys([...$this->orderQueries->getBasicColumns(), 'order_items']);
});

test(
    'updateCreditPendingAmountAndTypeId method updates the order credit pending amount and type id',
    function (): void {
        $this->orderQueries->updateCreditPendingAmountAndTypeId($this->order, 100);

        $this->assertDatabaseHas(Order::class, [
            'id' => $this->order->getKey(),
            'credit_pending_amount' => 100,
            'type_id' => OrderTypes::PENDING_CREDIT_ORDER->value,
        ]);
    }
);

test(
    'updateCreditAmountOf method updates the order credit pending amount, Amount Paid, total amount before round off and type id',
    function (): void {
        $this->order->credit_pending_amount = 100;
        $this->order->save();

        $payments = collect([
            [
                'type_id' => PaymentType::factory()->create([
                    'company_id' => $this->company->getKey(),
                ])->getKey(),
                'amount' => 100,
            ],
        ]);
        $this->orderQueries->updateCreditAmountOf($this->order, $payments);

        $this->assertDatabaseHas(Order::class, [
            'id' => $this->order->getKey(),
            'credit_pending_amount' => null,
            'type_id' => OrderTypes::COMPLETE_CREDIT_ORDER->value,
        ]);
    }
);

test(
    'getOrderDetailsForReceipt method returns the order details for the receipt when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);
        $response = $this->orderQueries->getOrderDetailsForReceipt($this->order->id);

        expect($response->toArray())->toHaveKeys($this->orderQueries->getBasicColumns());
    }
);

test(
    'getOrderDetailsForReceipt method returns the order details for the receipt when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->company->getKey(),
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        Product::factory()->create([
            'company_id' => $this->company->getKey(),
            'master_product_id' => $masterProduct->id,
        ]);

        $order = Order::factory()->create([
            'store_manager_id' => $this->storeManager->getKey(),
            'location_id' => $this->location->getKey(),
            'member_id' => $this->member->getKey(),
            'order_return_id' => null,
            'cancel_order_reason_id' => null,
            'created_at' => $this->date,
        ]);

        $response = $this->orderQueries->getOrderDetailsForReceipt($order->id);

        expect($response->toArray())->toHaveKeys($this->orderQueries->getBasicColumns());
    }
);

test(
    'getLayawayOrderItemsByForPrint method returns the order details for the receipt when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $response = $this->orderQueries->getLayawayOrderItemsByForPrint($this->order->id);

        expect($response->toArray())->toHaveKeys($this->orderQueries->getBasicColumns());
    }
);

test(
    'getLayawayOrderItemsByForPrint method returns the order details for the receipt when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->company->getKey(),
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        Product::factory()->create([
            'company_id' => $this->company->getKey(),
            'master_product_id' => $masterProduct->id,
        ]);

        $order = Order::factory()->create([
            'store_manager_id' => $this->storeManager->getKey(),
            'location_id' => $this->location->getKey(),
            'member_id' => $this->member->getKey(),
            'order_return_id' => null,
            'cancel_order_reason_id' => null,
            'created_at' => $this->date,
        ]);

        $response = $this->orderQueries->getLayawayOrderItemsByForPrint($order->id);

        expect($response->toArray())->toHaveKeys($this->orderQueries->getBasicColumns());
    }
);

test(
    'getOrderDetailsForReport method returns the order details with items for the report when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $filterData = [
            'report_type' => OrderReportTypes::BY_SUMMARY->value,
            'location_id' => $this->location->getKey(),
            'store_manager_id' => null,
            'date_range' => [$this->date->format('Y-m-d'), $this->date->format('Y-m-d')],
            'product_id' => null,
            'article_number' => null,
        ];

        $response = $this->orderQueries->getOrderDetailsForReport($filterData, $this->company->getKey());

        expect($response)->toBeInstanceOf(Collection::class);
        expect($response->first()->toArray())->toHaveKeys([...$this->orderQueries->getBasicColumns(), 'order_items']);
    }
);

test(
    'getOrderDetailsForReport method returns the order details with items for the report when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->company->getKey(),
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->company->getKey(),
            'master_product_id' => $masterProduct->id,
        ]);

        $order = Order::factory()->create([
            'store_manager_id' => $this->storeManager->getKey(),
            'location_id' => $this->location->getKey(),
            'member_id' => $this->member->getKey(),
            'order_return_id' => null,
            'cancel_order_reason_id' => null,
            'created_at' => $this->date,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
            'exchange_item_id' => null,
        ]);

        $filterData = [
            'report_type' => OrderReportTypes::BY_SUMMARY->value,
            'location_id' => $this->location->getKey(),
            'store_manager_id' => null,
            'date_range' => [$this->date->format('Y-m-d'), $this->date->format('Y-m-d')],
            'product_id' => null,
            'article_number' => null,
        ];

        $response = $this->orderQueries->getOrderDetailsForReport($filterData, $this->company->getKey());

        expect($response)->toBeInstanceOf(Collection::class);
        expect($response->first()->toArray())->toHaveKeys([...$this->orderQueries->getBasicColumns(), 'order_items']);
    }
);

test('loadOrderItems methods loads the order items in response', function (): void {
    expect($this->order)->not->toHaveKeys(['order_items']);
    $response = $this->orderQueries->loadOrderItems($this->order);
    expect($response)->toHaveKeys(['order_items']);
});

test('addNewForEcommerce method creates the order', function (): void {
    $orderECommerceData = new OrderECommerceData(
        member_id: $this->member->getKey(),
        order_items: [],
        payment_amount: 0,
        payment_type_id: 1,
        notes: 'test',
        order_round_off_amount: 0,
        total_tax_amount: 0,
        delivery_charges: 1,
        member_details: [],
    );

    $response = $this->orderQueries->addNewForEcommerce(
        $orderECommerceData,
        $this->location->getKey(),
        '00001',
        '007',
        now()->format('Y-m-d H:i:s'),
        OrderChannels::E_COMMERCE->value
    );

    expect($response)->toBeInstanceOf(Order::class);

    assertDatabaseHas(Order::class, [
        'type_id' => OrderTypes::REGULAR_ORDER,
        'status' => OrderStatus::PLACED,
        'channel_id' => OrderChannels::E_COMMERCE,
    ]);
});

test('A order can be fetched', function (): void {
    $response = $this->orderQueries->getById($this->order->getKey());
    expect($response->toArray())
        ->toHaveKey('id', $this->order->getKey())
        ->toHaveKey('pickup_location_id', $this->order->pickup_location_id);
});

test('getByIdWith method call and return proper response', function (): void {
    $response = $this->orderQueries->getByIdWithItemsAndStore($this->order->shipment_order_number);
    expect($response->toArray())
        ->toHaveKey('id', $this->order->getKey())
        ->toHaveKey('location_id', $this->order->location_id)
        ->toHaveKey('status', $this->order->status)
        ->toHaveKeys(['order_items']);
});

test('updateOrderTrackingDetails method call and updates the tracking details', function (): void {
    $this->orderQueries->updateTrackingDetails(
        new OrderTrackingDetailsData('123456789', 'leo logistics', 'http://test.com/', '1234ASDD'),
        $this->order->getKey()
    );

    assertDatabaseHas(Order::class, [
        'id' => $this->order->getKey(),
        'tracking_number' => '123456789',
        'courier_name' => 'leo logistics',
    ]);
});

test('digitalInvoiceUpdate method call and updates sale digital invoice submitted', function (): void {
    $this->order->digital_invoice_submitted = false;
    $this->order->save();
    $this->orderQueries->digitalInvoiceUpdate($this->order->id);

    assertDatabaseHas(Order::class, [
        'id' => $this->order->id,
        'digital_invoice_submitted' => true,
    ]);
});

test(
    'getOrderItemsForEcommerce method call and return proper response when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $response = $this->orderQueries->getOrderItemsForEcommerce(
            (string) $this->order->getKey(),
            $this->order->location_id,
            $this->company->getKey()
        );
        expect($response->toArray())
            ->toHaveKey('id', $this->order->getKey())
            ->toHaveKey('location_id', $this->order->location_id)
            ->toHaveKey('status', $this->order->status)
            ->toHaveKeys(['order_items']);
    }
);

test(
    'getOrderItemsForEcommerce method call and return proper response when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->company->getKey(),
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->company->getKey(),
            'master_product_id' => $masterProduct->id,
        ]);

        $order = Order::factory()->create([
            'store_manager_id' => $this->storeManager->getKey(),
            'location_id' => $this->location->getKey(),
            'member_id' => $this->member->getKey(),
            'order_return_id' => null,
            'cancel_order_reason_id' => null,
            'created_at' => $this->date,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
            'exchange_item_id' => null,
        ]);

        $response = $this->orderQueries->getOrderItemsForEcommerce(
            (string) $order->getKey(),
            $order->location_id,
            $this->company->getKey()
        );

        expect($response->toArray())
            ->toHaveKey('id', $order->getKey())
            ->toHaveKey('location_id', $order->location_id)
            ->toHaveKey('status', $order->status)
            ->toHaveKey('order_items.0.product.master_product_id', $masterProduct->id)
            ->toHaveKeys(['order_items']);
    }
);

test(
    'getByIdsWithLoadRelationsForShipment method call and return relation with response when product variant is false',
    function (): void {
        $response = $this->orderQueries->getByIdsWithLoadRelationsForShipment([(string) $this->order->getKey()]);

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->order->getKey())
            ->toHaveKey('location_id', $this->order->location_id)
            ->toHaveKey('status', $this->order->status)
            ->toHaveKeys(['billing_address', 'shipping_address', 'member', 'location']);
    }
);

test(
    'getByIdsWithLoadRelationsForShipment method call and return relation with response when product variant is true',
    function (): void {
        Config::set('app.product_variant', true);

        $masterProduct = MasterProduct::factory()->create([
            'company_id' => $this->company->getKey(),
            'has_batch' => false,
            'is_non_inventory' => false,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->company->getKey(),
            'master_product_id' => $masterProduct->id,
        ]);

        $order = Order::factory()->create([
            'store_manager_id' => $this->storeManager->getKey(),
            'location_id' => $this->location->getKey(),
            'member_id' => $this->member->getKey(),
            'order_return_id' => null,
            'cancel_order_reason_id' => null,
            'created_at' => $this->date,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
            'exchange_item_id' => null,
        ]);

        $response = $this->orderQueries->getByIdsWithLoadRelationsForShipment([(string) $order->getKey()]);

        expect($response->first()->toArray())
            ->toHaveKey('id', $order->getKey())
            ->toHaveKey('location_id', $order->location_id)
            ->toHaveKey('status', $order->status)
            ->toHaveKeys(['billing_address', 'shipping_address', 'member', 'location']);
    }
);

test(
    'the updateMember method update the order queries member id to new member id',
    function (): void {
        $member = Member::factory()->create();

        $this->assertDatabaseHas(Order::class, [
            'id' => $this->order->getKey(),
            'member_id' => $this->order->member_id,
        ]);

        $this->orderQueries->updateMember($this->order->member_id, $member->getKey());

        $this->assertDatabaseHas(Order::class, [
            'id' => $this->order->getKey(),
            'member_id' => $member->getKey(),
        ]);
    }
);

test(
    'the markAsAccepted method update the status of packing orders.',
    function (): void {
        Queue::fake();
        $this->order->status = OrderStatus::PACKING->value;
        $this->order->save();

        $orderPickingList = OrderPickingList::factory()->create([
            'company_id' => $this->company->getKey(),
            'status' => OrderPickingStatus::DRAFT->value,
        ]);

        $orderPickingListItem = OrderPickingListItem::factory()->create([
            'order_id' => $this->order->getKey(),
            'order_picking_list_id' => $orderPickingList->id,
        ]);

        $this->orderQueries->markAsAccepted($orderPickingListItem->order_picking_list_id);

        $this->assertDatabaseHas(Order::class, [
            'id' => $this->order->getKey(),
            'status' => OrderStatus::ACCEPTED->value,
        ]);
    }
);

test(
    'the markAsReadyForPickup method update the status of packing orders.',
    function (): void {
        Queue::fake();
        $this->order->status = OrderStatus::PACKING->value;
        $this->order->save();

        $orderPickingList = OrderPickingList::factory()->create([
            'company_id' => $this->company->getKey(),
            'status' => OrderPickingStatus::IN_PROGRESS->value,
        ]);

        $orderPickingListItem = OrderPickingListItem::factory()->create([
            'order_id' => $this->order->getKey(),
            'order_picking_list_id' => $orderPickingList->id,
        ]);

        $this->orderQueries->markAsReadyForPickup($orderPickingListItem->order_picking_list_id);

        $this->assertDatabaseHas(Order::class, [
            'id' => $this->order->getKey(),
            'status' => OrderStatus::READY_FOR_PICKUP->value,
        ]);
    }
);

test('A paginated order fetched', function (): void {
    $saleChannel = SaleChannel::factory()->create([
        'type_id' => SaleChannelTypes::ECOMMERCE->value,
    ]);

    $order = Order::factory()->create([
        'sale_channel_id' => $saleChannel->getKey(),
        'store_manager_id' => $this->storeManager->getKey(),
        'channel_id' => OrderChannels::E_COMMERCE->value,
        'location_id' => $this->location->getKey(),
        'member_id' => $this->member->getKey(),
        'order_return_id' => null,
        'cancel_order_reason_id' => null,
        'created_at' => $this->date,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->getKey(),
        'product_id' => $this->product->getKey(),
        'exchange_item_id' => null,
    ]);

    $filterData = [
        'page' => 1,
        'per_page' => 10,
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'sale_channel_id' => $saleChannel->getKey(),
    ];

    $response = $this->orderQueries->getPaginatedOrders($filterData, true);

    expect($response->first()->toArray())
        ->toHaveKey('id', $order->getKey())
        ->toHaveKeys(['order_items', 'payments']);
});

test('getPaginatedOrderListForMemberApi order fetched', function (): void {
    $saleChannel = SaleChannel::factory()->create([
        'type_id' => SaleChannelTypes::ECOMMERCE->value,
    ]);

    $order = Order::factory()->create([
        'sale_channel_id' => $saleChannel->getKey(),
        'store_manager_id' => $this->storeManager->getKey(),
        'channel_id' => OrderChannels::E_COMMERCE->value,
        'location_id' => $this->location->getKey(),
        'member_id' => $this->member->getKey(),
        'order_return_id' => null,
        'cancel_order_reason_id' => null,
        'created_at' => $this->date,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->getKey(),
        'product_id' => $this->product->getKey(),
        'exchange_item_id' => null,
    ]);

    $filterData = [
        'page' => 1,
        'per_page' => 10,
        'sort_by' => 'id',
        'sort_direction' => 'desc',
        'sale_channel_id' => $saleChannel->getKey(),
    ];

    $response = $this->orderQueries->getPaginatedOrderListForMemberApi($filterData, $this->member->getKey());

    expect($response->getCollection()->first()->toArray())
        ->toHaveKey('id', $order->getKey());
});

test('getOrderDetailsById returns the order details by order id and member id', function (): void {
    $saleChannel = SaleChannel::factory()->create([
        'type_id' => SaleChannelTypes::ECOMMERCE->value,
    ]);

    $order = Order::factory()->create([
        'sale_channel_id' => $saleChannel->getKey(),
        'store_manager_id' => $this->storeManager->getKey(),
        'channel_id' => OrderChannels::E_COMMERCE->value,
        'location_id' => $this->location->getKey(),
        'member_id' => $this->member->getKey(),
        'order_return_id' => null,
        'cancel_order_reason_id' => null,
        'created_at' => $this->date,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->getKey(),
        'product_id' => $this->product->getKey(),
        'exchange_item_id' => null,
    ]);

    $response = $this->orderQueries->getOrderDetailsById($order->getKey(), $this->member->getKey());

    expect($response->toArray())
        ->toHaveKey('id', $order->getKey())
        ->toHaveKeys(['payments']);
});

test('A order details by order id and sales channel id', function (): void {
    $saleChannel = SaleChannel::factory()->create([
        'type_id' => SaleChannelTypes::ECOMMERCE->value,
    ]);

    $order = Order::factory()->create([
        'sale_channel_id' => $saleChannel->getKey(),
        'store_manager_id' => $this->storeManager->getKey(),
        'channel_id' => OrderChannels::E_COMMERCE->value,
        'location_id' => $this->location->getKey(),
        'member_id' => $this->member->getKey(),
        'order_return_id' => null,
        'cancel_order_reason_id' => null,
        'created_at' => $this->date,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->getKey(),
        'product_id' => $this->product->getKey(),
        'exchange_item_id' => null,
    ]);

    $response = $this->orderQueries->getOrderByIdAndSaleChannelIdWithRelation($order->getKey(), $saleChannel->getKey());

    expect($response)->not->toBeNull();
    expect($response->toArray())
    ->toHaveKey('id', $order->getKey())
    ->toHaveKeys(['order_items', 'payments']);
});
