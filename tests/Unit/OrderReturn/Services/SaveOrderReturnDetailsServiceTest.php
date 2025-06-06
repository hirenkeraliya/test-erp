<?php

declare(strict_types=1);

use App\Domains\Inventory\Services\OrderReturnInventoryService;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\OrderCreditNote\OrderCreditNoteQueries;
use App\Domains\OrderItemUnit\OrderItemUnitQueries;
use App\Domains\OrderReturn\DataObjects\OrderReturnData;
use App\Domains\OrderReturn\OrderReturnQueries;
use App\Domains\OrderReturn\Services\CheckOrderReturnDetailsService;
use App\Domains\OrderReturn\Services\OrderReturnService;
use App\Domains\OrderReturn\Services\SaveOrderReturnDetailsService;
use App\Domains\OrderReturnItem\OrderReturnItemQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Sequence\SequenceQueries;
use App\Models\Location;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemUnit;
use App\Models\OrderReturn;
use App\Models\OrderReturnItem;
use App\Models\Product;
use App\Models\SaleReturnReason;
use App\Models\Sequence;
use App\Models\StoreManager;

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

    $this->storeManager = StoreManager::factory()->make([
        'employee_id' => 1,
        'id' => 1,
    ]);

    $this->checkOrderReturnDetailsService = new CheckOrderReturnDetailsService();
    $this->checkOrderReturnDetailsService->orderReturnData = $this->orderReturnData;
    $this->checkOrderReturnDetailsService->locationId = 1;
    $this->saveOrderReturnDetailsService = new SaveOrderReturnDetailsService();
    $this->orderReturnService = new OrderReturnService();
    $this->orderReturnService->orderReturnItems = collect($this->orderReturnData->order_return_items);
    $this->orderReturnService->checkOrderReturnDetailsService = $this->checkOrderReturnDetailsService;
});

test(
    'saveOrderReturnDetails method calls the same class methods as expected',
    function (): void {
        $orderReturnReason = [];
        $this->checkOrderReturnDetailsService->orderReturnData = $this->orderReturnData;

        $this->checkOrderReturnDetailsService->location = Location::factory()->make([
            'id' => 1,
            'company_id' => $this->companyId,
            'credit_note_expiration_days,' => 10,
        ]);

        $this->checkOrderReturnDetailsService->companyId = 1;
        $this->checkOrderReturnDetailsService->member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $product = Product::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'unit_of_measure_id' => 1,
            'season_id' => 1,
            'department_id' => 1,
            'color_id' => 1,
            'size_id' => 1,
            'brand_id' => 1,
            'style_id' => 1,
            'is_non_inventory' => true,
        ]);

        $orderReturn = OrderReturn::factory()->make([
            'id' => 1,
            'original_order_id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
        ]);

        $orderReturnItem = OrderReturnItem::factory()->make([
            'id' => 1,
            'order_return_id' => 1,
            'original_order_item_id' => 1,
            'product_id' => $product->id,
            'order_return_reason_id' => 1,
        ]);

        $orderItem = OrderItem::factory()->make([
            'id' => 1,
            'order_id' => 1,
            'product_id' => $product->id,
            'complimentary_item_reason_id' => 1,
            'exchange_item_id' => 1,
        ]);

        $orderItem->product = $product;

        $order = Order::factory()->make([
            'member_id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'order_return_id' => null,
            'cancel_order_reason_id' => null,
        ]);

        $order->member = Member::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'created_location_id' => 1,
        ]);

        $orderItem->order = $order;

        $orderItemUnit = OrderItemUnit::factory()->make([
            'order_item_id' => 1,
            'inventory_id' => 1,
            'purchase_amount_id' => 1,
            'batch_id' => 1,
            'quantity' => 100,
        ]);

        $orderReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 1,
            'company_id' => 1,
        ]);

        $orderReturnReason[] = SaleReturnReason::factory()->make([
            'id' => 2,
            'company_id' => 1,
        ]);

        $orderReturn->OrderItems = collect([$orderReturnItem]);

        $this->checkOrderReturnDetailsService->orderReturnService = $this->mock(
            OrderReturnService::class,
            function ($mock) use ($orderItem, $orderReturnReason): void {
                $mock->shouldReceive('checkRoundOffValue')
                    ->once();

                $mock->returnedOrderItems = collect([$orderItem]);
                $mock->orderReturnItems = collect($this->orderReturnData->order_return_items);
                $mock->orderReturnReasons = collect($orderReturnReason);
            }
        );

        $mock = $this->createPartialMock(SaveOrderReturnDetailsService::class, []);

        $sequence = Sequence::factory()->make([
            'location_id' => 1,
            'type_id' => 1,
        ]);

        $this->mock(OrderReturnQueries::class, function ($mock) use ($orderReturn): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($orderReturn);
            $mock->shouldReceive('updateTotals')
                ->once();
            $mock->shouldReceive('loadRelations')
                ->once();
        });

        $this->mock(OrderReturnItemQueries::class, function ($mock) use ($orderReturnItem): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($orderReturnItem);
        });

        $this->mock(OrderCreditNoteQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
            $mock->shouldReceive('addNew')
                ->times(2)
                ->andReturn($sequence);
        });

        $mock->saveOrderReturnDetails($this->storeManager, $this->checkOrderReturnDetailsService, 1);
    }
);

test('updateInventory method calls addInventoryAsPerOrderReturn method of InventoryService class', function (): void {
    $this->checkOrderReturnDetailsService->location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->checkOrderReturnDetailsService->companyId = 1;
    $this->checkOrderReturnDetailsService->orderReturnData = $this->orderReturnData;

    $product = Product::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'unit_of_measure_id' => 1,
        'season_id' => 1,
        'department_id' => 1,
        'color_id' => 1,
        'size_id' => 1,
        'brand_id' => 1,
        'style_id' => 1,
        'is_non_inventory' => true,
        'type_id' => ProductTypes::REGULAR_PRODUCT->value,
    ]);

    $orderReturn = OrderReturn::factory()->make([
        'id' => 1,
        'original_order_id' => 1,
        'store_manager_id' => 1,
        'location_id' => 1,
        'member_id' => 1,
    ]);

    $orderReturnItem = OrderReturnItem::factory()->make([
        'id' => 1,
        'order_return_id' => 1,
        'original_order_item_id' => 1,
        'product_id' => $product->id,
        'order_return_reason_id' => 1,
    ]);

    $orderItem = OrderItem::factory()->make([
        'id' => 1,
        'order_id' => 1,
        'product_id' => $product->id,
        'complimentary_item_reason_id' => 1,
        'exchange_item_id' => 1,
    ]);

    $orderItem->product = $product;

    $order = Order::factory()->make([
        'member_id' => 1,
        'store_manager_id' => 1,
        'location_id' => 1,
        'order_return_id' => null,
        'cancel_order_reason_id' => null,
    ]);

    $order->member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $orderItem->order = $order;

    $orderItemUnit = OrderItemUnit::factory()->make([
        'order_item_id' => 1,
        'inventory_id' => 1,
        'purchase_amount_id' => 1,
        'batch_id' => 1,
        'quantity' => 100,
    ]);

    $orderItem->orderItemUnits = collect([$orderItemUnit]);

    $orderReturnReason = SaleReturnReason::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $this->checkOrderReturnDetailsService->orderReturnService = new OrderReturnService();

    $mock = $this->createPartialMock(SaveOrderReturnDetailsService::class, []);

    $this->mock(OrderItemUnitQueries::class, function ($mock): void {
        $mock->shouldReceive('incrementReturnedQuantity')
            ->once();
    });

    $this->mock(OrderReturnInventoryService::class, function ($mock): void {
        $mock->shouldReceive('addInventory')
            ->once();
    });

    $returnItemDetails['return_quantity'] = 10;

    $mock->updateInventory(
        $this->checkOrderReturnDetailsService,
        $orderItem,
        $orderReturnItem,
        $this->storeManager,
        $orderReturnReason,
        $returnItemDetails
    );
});

test(
    'getSequenceNumber method call and return the sequence number',
    function (): void {
        $location = Location::factory()->make([
            'id' => 1,
            'company_id' => 1,
            'type_id' => LocationTypes::STORE->value,
            'country_id' => 1,
        ]);
        $sequence = Sequence::factory()->make([
            'number' => '000001',
            'location_id' => 1,
        ]);

        $this->mock(SequenceQueries::class, function ($mock) use ($sequence): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->andReturn($sequence);
        });

        $response = $this->saveOrderReturnDetailsService->getSequenceNumber($location);
        expect($response)->toBeString();
    }
);
