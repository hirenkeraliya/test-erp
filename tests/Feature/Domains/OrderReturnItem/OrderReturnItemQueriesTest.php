<?php

declare(strict_types=1);

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\OrderReturnItem\OrderReturnItemQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\OrderReturnItem;
use App\Models\Product;
use App\Models\SaleReturnReason;
use App\Models\SaleReturnReasonType;
use App\Models\StoreManager;

beforeEach(function (): void {
    $this->company = Company::factory()->create([
        'name' => 'Test Company',
        'email' => 'abc@company.test',
        'code' => 'ABC',
    ]);

    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->getKey(),
    ]);

    $this->storeManager = StoreManager::factory()->create([
        'employee_id' => $this->employee->getKey(),
    ]);

    $this->location = Location::factory()->create([
        'company_id' => $this->company->getKey(),
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->order = Order::factory()->create([
        'store_manager_id' => $this->storeManager->getKey(),
        'location_id' => $this->location->getKey(),
        'order_return_id' => null,
        'cancel_order_reason_id' => null,
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
    ]);

    $this->orderReturn = OrderReturn::factory()->create([
        'store_manager_id' => $this->storeManager->getKey(),
        'location_id' => $this->location->getKey(),
        'member_id' => $this->member->getKey(),
        'original_order_id' => $this->order->getKey(),
    ]);

    $this->orderItems = OrderItem::factory()->create([
        'order_id' => $this->order->getKey(),
        'product_id' => $this->product->getKey(),
        'exchange_item_id' => null,
    ]);

    $this->orderReturnReason = SaleReturnReason::factory()->create();

    $this->orderReturnReason->saleReturnReasonTypes = collect([
        SaleReturnReasonType::factory()->create([
            'type_id' => SaleReturnOrVoidSaleReasonTypes::ORDERS->value,
        ]),
    ]);

    $this->orderReturnItemQueries = new OrderReturnItemQueries();
});

test('new Order return item can be added', function (): void {
    $orderReturnItem = OrderReturnItem::factory()->make([
        'order_return_id' => $this->orderReturn->getKey(),
        'original_order_item_id' => $this->orderItems->getKey(),
        'product_id' => $this->product->getKey(),
        'order_return_reason_id' => $this->orderReturnReason->getKey(),
    ]);

    $this->orderReturnItemQueries->addNew(
        $orderReturnItem->order_return_reason_id,
        $orderReturnItem->order_return_id,
        $orderReturnItem->original_order_item_id,
        $orderReturnItem->product_id,
        20.20,
        30.20,
        10.10,
        10.10,
        10.10,
        10.10
    );

    $this->assertDatabaseHas(OrderReturnItem::class, [
        'order_return_id' => $orderReturnItem->order_return_id,
        'original_order_item_id' => $orderReturnItem->original_order_item_id,
        'product_id' => $orderReturnItem->product_id,
        'quantity' => 20.20,
        'total_price_paid' => 30.20,
        'order_return_reason_id' => $orderReturnItem->order_return_reason_id,
    ]);
});

test('if product is merged then the product id is updated', function (): void {
    $productAId = Product::factory()->create()->id;
    $productBId = Product::factory()->create()->id;

    OrderReturnItem::factory()->create([
        'order_return_id' => $this->orderReturn->id,
        'product_id' => $productBId,
        'original_order_item_id' => $this->orderItems->getKey(),
        'order_return_reason_id' => $this->orderReturnReason->getKey(),
    ]);

    $this->orderReturnItemQueries->updateProductId($this->company->getKey(), $productBId, $productAId);

    $this->assertDatabaseHas(OrderReturnItem::class, [
        'order_return_id' => $this->orderReturn->id,
        'product_id' => $productAId,
    ]);
});
