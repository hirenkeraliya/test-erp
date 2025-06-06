<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Inventory\Services\EcommerceOrderInventoryService;
use App\Domains\InventoryUnit\InventoryUnitQueries;
use App\Domains\InventoryUpdate\InventoryUpdateQueries;
use App\Domains\LoyaltyPoint\LoyaltyPointQueries;
use App\Domains\LoyaltyPoint\Services\LoyaltyPointService;
use App\Domains\LoyaltyPoint\Services\RevertLoyaltyPointService;
use App\Domains\LoyaltyPointUpdate\LoyaltyPointUpdateQueries;
use App\Domains\Order\Enums\OrderStatus;
use App\Domains\OrderDiscount\OrderDiscountQueries;
use App\Domains\Voucher\VoucherQueries;
use App\Domains\VoucherTransaction\VoucherTransactionQueries;
use App\Models\Inventory;
use App\Models\InventoryUnit;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyPointUpdate;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\OrderItem;
use App\Models\OrderItemUnit;
use App\Models\Product;
use App\Models\SaleChannel;
use App\Models\Voucher;

test(
    'rollBackInventory method calls the rollback inventory as expected',
    function (): void {
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

        $order = Order::factory()->make([
            'id' => 1,
            'store_manager_id' => 1,
            'location_id' => 1,
            'member_id' => 1,
            'order_return_id' => 1,
            'cancel_order_reason_id' => 1,
            'receipt_number' => '123456',
            'status' => OrderStatus::CANCELLED,
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

        $saleChannel = SaleChannel::factory()->make([
            'id' => 1,
            'name' => 'sale_channel_1',
            'company_id' => 1,
            'default_location_id' => 1,
        ]);

        $this->mock(InventoryQueries::class, function ($mock) use ($inventory): void {
            $mock->shouldReceive('fetchOrCreate')
                ->once()
                ->andReturn($inventory);
            $mock->shouldReceive('increaseStock')
                ->once();
        });

        $this->mock(InventoryUnitQueries::class, function ($mock) use ($inventoryUnit): void {
            $mock->shouldReceive('getByInventoryIdBatchIdAndPurchaseAmountId')
                ->once()
                ->andReturn($inventoryUnit);
            $mock->shouldReceive('increaseStock')
                ->once();
        });

        $this->mock(InventoryUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('addNew')
                ->once();
        });

        $ecommerceOrderInventoryService = new EcommerceOrderInventoryService();
        $ecommerceOrderInventoryService->rollBackInventory($order, $saleChannel);
    }
);

it('checkAndRevertLoyaltyPoints method calls respective queries class methods as expected', function (): void {
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
        'status' => OrderStatus::CANCELLED,
    ]);

    $order->member = $member;

    $loyaltyPoint = LoyaltyPoint::factory()->make([
        'id' => 1,
        'member_id' => $member->id,
        'sale_id' => null,
        'order_id' => $order->id,
        'loyalty_campaign_id' => 0,
        'points' => 100,
    ]);

    $loyaltyPoint->member = $member;

    $this->mock(LoyaltyPointQueries::class, function ($mock) use ($order, $loyaltyPoint): void {
        $mock->shouldReceive('getLoyaltyPointForGivenOrder')
            ->once()
            ->with($order->id)
            ->andReturn(collect([$loyaltyPoint]));
    });

    $this->mock(LoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('decreaseLoyaltyPoints')
            ->once();
    });

    $ecommerceOrderInventoryService = new EcommerceOrderInventoryService();
    $ecommerceOrderInventoryService->checkAndRevertLoyaltyPoints($order);
});

test('revertUsedLoyaltyPoints method calls respective queries class methods as expected', function (): void {
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
        'status' => OrderStatus::CANCELLED,
    ]);

    $order->member = $member;

    $loyaltyPointUpdates = LoyaltyPointUpdate::factory()->make([
        'id' => 1,
        'member_id' => $member->id,
        'affected_by_id' => 1,
    ]);

    $this->mock(RevertLoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('increaseLoyaltyPoints')
            ->once();
    });

    $this->mock(LoyaltyPointUpdateQueries::class, function ($mock) use ($loyaltyPointUpdates): void {
        $mock->shouldReceive('getUsedLoyaltyPoint')
            ->once()
            ->andReturn(collect([$loyaltyPointUpdates]));
    });

    $ecommerceOrderInventoryService = new EcommerceOrderInventoryService();
    $ecommerceOrderInventoryService->revertUsedLoyaltyPoints($order);
});

test('revertUsedLoyaltyPoints method return null when no loyalty point used', function (): void {
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
        'status' => OrderStatus::CANCELLED,
    ]);

    $order->member = $member;

    $this->mock(RevertLoyaltyPointService::class, function ($mock): void {
        $mock->shouldReceive('increaseLoyaltyPoints')
            ->never();
    });

    $this->mock(LoyaltyPointUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getUsedLoyaltyPoint')
            ->once()
            ->andReturn(collect([]));
    });

    $ecommerceOrderInventoryService = new EcommerceOrderInventoryService();
    $response = $ecommerceOrderInventoryService->revertUsedLoyaltyPoints($order);
    $this->assertNull($response);
});

test('checkAndRevertVouchersGenerated method calls respective queries class methods as expected', function (): void {
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
        'status' => OrderStatus::CANCELLED,
    ]);

    $order->member = $member;

    $voucher = Voucher::factory()->make([
        'id' => 1,
        'voucher_configuration_id' => 1,
        'member_id' => $member->id,
        'generated_by_sale_id' => 1,
    ]);

    $this->mock(VoucherQueries::class, function ($mock) use ($voucher): void {
        $mock->shouldReceive('getVouchersByOrderId')
            ->once()
            ->andReturn(collect([$voucher]));

        $mock->shouldReceive('updateCancelledAt')
        ->once();
    });

    $this->mock(VoucherTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $ecommerceOrderInventoryService = new EcommerceOrderInventoryService();
    $ecommerceOrderInventoryService->checkAndRevertVouchersGenerated($order->id, 1);
});

it('checkAndRevertUsedVoucher method calls respective queries class methods as expected', function (): void {
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
        'status' => OrderStatus::CANCELLED,
    ]);

    $order->member = $member;

    $voucher = Voucher::factory()->make([
        'id' => 1,
        'voucher_configuration_id' => 1,
        'member_id' => $member->id,
        'generated_by_sale_id' => 1,
    ]);

    OrderDiscount::factory()->make([
        'id' => 1,
        'order_id' => $order->id,
        'discountable_type' => ModelMapping::VOUCHER->name,
        'discountable_id' => $voucher->id,
    ]);

    $this->mock(OrderDiscountQueries::class, function ($mock): void {
        $mock->shouldReceive('getVoucherIdByOrder')
            ->once()
            ->andReturn(1);
    });

    $this->mock(VoucherQueries::class, function ($mock) use ($voucher): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($voucher);
        $mock->shouldReceive('resetUsedAt')
            ->once();
    });

    $this->mock(VoucherTransactionQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->once();
    });

    $ecommerceOrderInventoryService = new EcommerceOrderInventoryService();
    $ecommerceOrderInventoryService->checkAndRevertUsedVoucher($order->id, 1);
});
