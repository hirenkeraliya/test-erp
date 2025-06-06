<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Order\Enums\OrderChannels;
use App\Domains\Order\Enums\OrderReportTypes;
use App\Domains\Order\Enums\OrderTypes;
use App\Domains\OrderItem\OrderItemQueries;
use App\Models\BoxProduct;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->company = Company::factory()->create([
        'name' => 'Order Test',
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

    $this->member = Member::factory()->create([
        'company_id' => $this->company->getKey(),
        'created_location_id' => $this->location->getKey(),
    ]);

    $this->product = Product::factory()->create([
        'company_id' => $this->company->getKey(),
    ]);

    $this->date = Carbon::now();

    $this->order = Order::factory()->create([
        'store_manager_id' => $this->storeManager->getKey(),
        'location_id' => $this->location->getKey(),
        'member_id' => $this->member->getKey(),
        'order_return_id' => null,
        'cancel_order_reason_id' => null,
        'total_tax_amount' => 0.0,
        'cart_discount_amount' => 0.0,
        'item_discount_amount' => 0.0,
        'total_discount_amount' => 0.0,
        'layaway_pending_amount' => 2,
        'total_amount_before_round_off' => 10,
        'round_off' => 0.0,
        'total_amount_paid' => 8,
        'type_id' => OrderTypes::PENDING_LAYAWAY_ORDER->value,
        'channel_id' => OrderChannels::B2B_ORDERS->value,
        'created_at' => $this->date,
    ]);

    $this->orderItem = OrderItem::factory()->create([
        'order_id' => $this->order->getKey(),
        'product_id' => $this->product->getKey(),
        'exchange_item_id' => null,
        'quantity' => 1,
        'complimentary_item_reason_id' => null,
        'original_product_price_per_unit' => 10,
        'cart_discount_amount' => 0.0,
        'item_discount_amount' => 0.0,
        'total_discount_amount' => 0.0,
        'item_tax_amount' => 0.0,
        'price_paid_per_unit' => 10,
        'total_price_paid' => 8,
    ]);

    $this->orderItemQueries = new OrderItemQueries();
});

test('updateTotalPricePaid method updates the total price paid', function (): void {
    $this->orderItem->total_price_paid = 0;
    $this->orderItem->save();

    $this->orderItemQueries->updateTotalPricePaid($this->orderItem, 100);

    $this->orderItem->refresh();

    $this->assertDatabaseHas(OrderItem::class, [
        'id' => $this->orderItem->getKey(),
        'total_price_paid' => $this->orderItem->getTotalPricePaid(),
    ]);
});

test('updateLayawayAmountOf methods updates the prices in the order items', function (): void {
    $this->orderItemQueries->updateLayawayAmountOf($this->order, 2, false);

    $this->orderItem->refresh();

    $this->assertDatabaseHas(OrderItem::class, [
        'id' => $this->orderItem->getKey(),
        'total_price_paid' => $this->orderItem->getTotalPricePaid(),
    ]);
});

test('updateCreditAmountOf methods updates the prices in the order items', function (): void {
    $this->orderItemQueries->updateCreditAmountOf($this->order, 2, false);

    $this->orderItem->refresh();

    $this->assertDatabaseHas(OrderItem::class, [
        'id' => $this->orderItem->getKey(),
        'total_price_paid' => $this->orderItem->getTotalPricePaid(),
    ]);
});

test('getOrderItemsForTheReport methods returns the order items for report', function (): void {
    $filterData = [
        'report_type' => OrderReportTypes::BY_SUMMARY->value,
        'location_id' => $this->location->getKey(),
        'store_manager_id' => null,
        'date_range' => [$this->date->format('Y-m-d'), $this->date->format('Y-m-d')],
        'product_id' => '',
        'article_number' => '',
    ];

    $response = $this->orderItemQueries->getOrderItemsForTheReport($filterData, $this->company->getKey());

    expect($response->first()->toArray())->toHaveKeys(explode(',', $this->orderItemQueries->getBasicColumnNames()));
});

test('getByIdsWithRelations methods returns the order items', function (): void {
    $response = $this->orderItemQueries->getByIdsWithRelations([$this->orderItem->getKey()]);

    expect($response)->toBeInstanceOf(Collection::class);
    expect($response->first()->toArray())->toHaveKeys(explode(',', $this->orderItemQueries->getBasicColumnNames()));
});

test('addNew method creates new in the order items', function (): void {
    $item = [
        'price' => 10,
        'total_price_paid' => 10,
        'id' => $this->product->getKey(),
        'quantity' => 1,
        'promotion_id' => null,
        'vendor_commission_percentage' => null,
    ];

    $orderItem = $this->orderItemQueries->addNew($this->order, $item, 10, 0, 0, 0, null);

    $this->assertDatabaseHas(OrderItem::class, [
        'id' => $orderItem->getKey(),
        'total_price_paid' => 10.000000,
        'original_product_price_per_unit' => 10.000000,
        'promotion_id' => null,
        'item_discount_amount' => 0.000000,
    ]);
});

test('addNew method creates new in the order items with discount', function (): void {
    $promotion = Promotion::factory()->create([
        'company_id' => $this->company->getKey(),
    ]);

    $item = [
        'price' => 10,
        'total_price_paid' => 5,
        'id' => $this->product->getKey(),
        'quantity' => 1,
        'promotion_id' => $promotion->getKey(),
        'item_discount_amount' => 5,
        'vendor_commission_percentage' => null,
    ];

    $orderItem = $this->orderItemQueries->addNew($this->order, $item, 10, 0, 0, 5, null);

    $this->assertDatabaseHas(OrderItem::class, [
        'id' => $orderItem->getKey(),
        'total_price_paid' => 5.000000,
        'original_product_price_per_unit' => 10.000000,
        'promotion_id' => $promotion->getKey(),
        'item_discount_amount' => 5.000000,
    ]);
});

test('addNew method creates new in the order items with bundle', function (): void {
    $item = [
        'price' => 10,
        'total_price_paid' => 10,
        'id' => $this->product->getKey(),
        'quantity' => 1,
        'promotion_id' => null,
        'vendor_commission_percentage' => null,
    ];

    $boxProduct = BoxProduct::factory()->create([
        'product_id' => $this->product->getKey(),
        'units' => 5,
        'retail_price' => 10,
    ]);

    $orderItem = $this->orderItemQueries->addNew($this->order, $item, 10, 0, 0, 0, null, $boxProduct);

    $this->assertDatabaseHas(OrderItem::class, [
        'id' => $orderItem->getKey(),
        'total_price_paid' => 10.000000,
        'original_product_price_per_unit' => 10.000000,
        'promotion_id' => null,
        'item_discount_amount' => 0.000000,
        'box_product_id' => $boxProduct->getKey(),
        'product_box_package_type_id' => $boxProduct->package_type_id,
        'product_box_units' => $boxProduct->units,
    ]);
});
