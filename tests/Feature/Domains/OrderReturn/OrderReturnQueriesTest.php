<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\OrderReturn\OrderReturnQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\MasterProduct;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\OrderReturnItem;
use App\Models\Product;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->company = Company::factory()->create([
        'name' => 'Test Company',
        'email' => 'abc@company.test',
        'code' => 'ABC',
    ]);

    $this->date = Carbon::now();

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
        'created_at' => $this->date,
    ]);

    $this->orderReturn = OrderReturn::factory()->create([
        'store_manager_id' => $this->storeManager->getKey(),
        'location_id' => $this->location->getKey(),
        'member_id' => $this->member->getKey(),
        'original_order_id' => $this->order->getKey(),
        'created_at' => $this->date,
    ]);

    $this->orderItems = OrderItem::factory()->create([
        'order_id' => $this->order->getKey(),
        'product_id' => $this->product->getKey(),
        'exchange_item_id' => null,
    ]);

    $this->orderReturnQueries = new OrderReturnQueries();
});

test('getPaginatedCompleteOrderWithRelations method call return proper response', function (): void {
    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
        'date_range' => null,
        'member_id' => $this->member->getKey(),
    ];
    $response = $this->orderReturnQueries->getPaginatedCompleteOrderWithRelations(
        $filterData,
        $this->storeManager->getKey(),
        $this->location->getKey()
    );

    expect($response)->toBeInstanceOf(LengthAwarePaginator::class);
});

test('getFilteredTotalsForReport method call return proper response', function (): void {
    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
        'date_range' => null,
        'member_id' => $this->member->getKey(),
    ];
    $response = $this->orderReturnQueries->getFilteredTotalsForReport(
        $filterData,
        $this->storeManager->getKey(),
        $this->location->getKey()
    );
    expect($response)->toBeInstanceOf(Collection::class);
});

test(
    'getOrderReturnItemsForStoreManager method call return proper response when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $response = $this->orderReturnQueries->getOrderReturnItemsForStoreManager(
            $this->orderReturn->getKey(),
            $this->location->getKey()
        );

        expect($response->toArray())->toHaveKeys([
            'id',
            'receipt_number',
            'store_manager_id',
            'location_id',
            'member_id',
            'original_order_id',
            'total_tax_amount',
            'cart_discount_amount',
            'item_discount_amount',
            'total_discount_amount',
            'total_amount_before_round_off',
            'round_off_amount',
            'total_price_paid',
        ]);
    }
);

test(
    'getOrderReturnItemsForStoreManager method call return proper response when product variant is true',
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

        $orderReturn = OrderReturn::factory()->create([
            'store_manager_id' => $this->storeManager->getKey(),
            'location_id' => $this->location->getKey(),
            'member_id' => $this->member->getKey(),
            'original_order_id' => $order->getKey(),
            'created_at' => $this->date,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
            'exchange_item_id' => null,
        ]);

        $response = $this->orderReturnQueries->getOrderReturnItemsForStoreManager(
            $orderReturn->getKey(),
            $this->location->getKey()
        );

        expect($response->toArray())->toHaveKeys([
            'id',
            'receipt_number',
            'store_manager_id',
            'location_id',
            'member_id',
            'original_order_id',
            'total_tax_amount',
            'cart_discount_amount',
            'item_discount_amount',
            'total_discount_amount',
            'total_amount_before_round_off',
            'round_off_amount',
            'total_price_paid',
        ]);
    }
);

test(
    'getOrderReturnReceiptForStoreManager method call return proper response when product variant is false',
    function (): void {
        Config::set('app.product_variant', false);

        $response = $this->orderReturnQueries->getOrderReturnReceiptForStoreManager(
            $this->orderReturn->getKey(),
            $this->location->getKey()
        );

        expect($response->toArray())->toHaveKeys([
            'id',
            'store_manager_id',
            'location_id',
            'member_id',
            'receipt_number',
            'original_order_id',
            'total_tax_amount',
            'cart_discount_amount',
            'item_discount_amount',
            'total_discount_amount',
            'total_amount_before_round_off',
            'round_off_amount',
            'total_price_paid',
            'created_at',
        ]);
    }
);

test(
    'getOrderReturnReceiptForStoreManager method call return proper response when product variant is true',
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

        $orderReturn = OrderReturn::factory()->create([
            'store_manager_id' => $this->storeManager->getKey(),
            'location_id' => $this->location->getKey(),
            'member_id' => $this->member->getKey(),
            'original_order_id' => $order->getKey(),
            'created_at' => $this->date,
        ]);

        $response = $this->orderReturnQueries->getOrderReturnReceiptForStoreManager(
            $orderReturn->getKey(),
            $this->location->getKey()
        );

        expect($response->toArray())->toHaveKeys([
            'id',
            'store_manager_id',
            'location_id',
            'member_id',
            'receipt_number',
            'original_order_id',
            'total_tax_amount',
            'cart_discount_amount',
            'item_discount_amount',
            'total_discount_amount',
            'total_amount_before_round_off',
            'round_off_amount',
            'total_price_paid',
            'created_at',
        ]);
    }
);

test('new order return can be added', function (): void {
    $this->orderReturnQueries->addNew(
        $this->storeManager,
        $this->order->getKey(),
        $this->location->getKey(),
        '0001',
        'test',
        null
    );

    $this->assertDatabaseHas(OrderReturn::class, [
        'receipt_number' => 'test',
        'original_order_id' => $this->order->id,
        'store_manager_id' => $this->storeManager->getKey(),
    ]);
});

test('updateTotals method updates the order return details as expected', function (): void {
    $orderReturn = OrderReturn::factory()->create([
        'original_order_id' => $this->order->getKey(),
        'store_manager_id' => $this->storeManager->getKey(),
        'location_id' => $this->location->getKey(),
        'member_id' => $this->member->getKey(),
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $this->order->id,
        'item_tax_amount' => 10.00,
        'exchange_item_id' => null,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    OrderReturnItem::factory()->create([
        'order_return_id' => $orderReturn->id,
        'original_order_item_id' => $orderItem->id,
        'total_tax_amount' => 10.00,
        'total_price_paid' => 20.00,
        'cart_discount_amount' => 30.00,
    ]);

    $this->orderReturnQueries->updateTotals($orderReturn);

    $this->assertDatabaseHas(OrderReturn::class, [
        'id' => $orderReturn->getKey(),
        'round_off_amount' => 0.00,
    ]);
});

test(
    'updateTotals method updates the sale return details as expected when sale has round off amount',
    function (): void {
        $orderReturn = OrderReturn::factory()->create([
            'original_order_id' => $this->order->getKey(),
            'store_manager_id' => $this->storeManager->getKey(),
            'location_id' => $this->location->getKey(),
            'member_id' => $this->member->getKey(),
        ]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'item_tax_amount' => 10.00,
            'exchange_item_id' => null,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        OrderReturnItem::factory()->create([
            'order_return_id' => $orderReturn->id,
            'original_order_item_id' => $orderItem->id,
            'total_tax_amount' => 10.00,
            'total_price_paid' => 20.00,
            'cart_discount_amount' => 30.00,
        ]);

        $this->orderReturnQueries->updateTotals($orderReturn, 0.1);

        $this->assertDatabaseHas(OrderReturn::class, [
            'id' => $orderReturn->getKey(),
            'round_off_amount' => 0.10,
        ]);
    }
);

test('getOrderReturnReceipt method call return proper response when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $response = $this->orderReturnQueries->getOrderReturnReceipt(
        $this->orderReturn->getKey(),
        $this->location->getCompanyId()
    );

    expect($response->toArray())->toHaveKeys([
        'id',
        'store_manager_id',
        'location_id',
        'member_id',
        'receipt_number',
        'original_order_id',
        'total_tax_amount',
        'cart_discount_amount',
        'item_discount_amount',
        'total_discount_amount',
        'total_amount_before_round_off',
        'round_off_amount',
        'total_price_paid',
        'created_at',
    ]);
});

test('getOrderReturnReceipt method call return proper response when product variant is true', function (): void {
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

    $orderReturn = OrderReturn::factory()->create([
        'store_manager_id' => $this->storeManager->getKey(),
        'location_id' => $this->location->getKey(),
        'member_id' => $this->member->getKey(),
        'original_order_id' => $order->getKey(),
        'created_at' => $this->date,
    ]);

    $response = $this->orderReturnQueries->getOrderReturnReceipt(
        $orderReturn->getKey(),
        $this->location->getCompanyId()
    );

    expect($response->toArray())->toHaveKeys([
        'id',
        'store_manager_id',
        'location_id',
        'member_id',
        'receipt_number',
        'original_order_id',
        'total_tax_amount',
        'cart_discount_amount',
        'item_discount_amount',
        'total_discount_amount',
        'total_amount_before_round_off',
        'round_off_amount',
        'total_price_paid',
        'created_at',
    ]);
});

test('getOrderReturnItems method call return proper response when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $response = $this->orderReturnQueries->getOrderReturnItems(
        $this->orderReturn->getKey(),
        $this->location->getCompanyId()
    );

    expect($response->toArray())->toHaveKeys([
        'id',
        'receipt_number',
        'store_manager_id',
        'location_id',
        'member_id',
        'original_order_id',
        'total_tax_amount',
        'cart_discount_amount',
        'item_discount_amount',
        'total_discount_amount',
        'total_amount_before_round_off',
        'round_off_amount',
        'total_price_paid',
    ]);
});

test('getOrderReturnItems method call return proper response when product variant is true', function (): void {
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

    $orderReturn = OrderReturn::factory()->create([
        'store_manager_id' => $this->storeManager->getKey(),
        'location_id' => $this->location->getKey(),
        'member_id' => $this->member->getKey(),
        'original_order_id' => $order->getKey(),
        'created_at' => $this->date,
    ]);

    $response = $this->orderReturnQueries->getOrderReturnItems(
        $orderReturn->getKey(),
        $this->location->getCompanyId()
    );

    expect($response->toArray())->toHaveKeys([
        'id',
        'receipt_number',
        'store_manager_id',
        'location_id',
        'member_id',
        'original_order_id',
        'total_tax_amount',
        'cart_discount_amount',
        'item_discount_amount',
        'total_discount_amount',
        'total_amount_before_round_off',
        'round_off_amount',
        'total_price_paid',
    ]);
});

test('digitalInvoiceUpdate method call update order return', function (): void {
    $this->orderReturnQueries->digitalInvoiceUpdate($this->orderReturn->getKey());
    $this->assertDatabaseHas('order_returns', [
        'id' => $this->orderReturn->getKey(),
        'digital_invoice_submitted' => true,
    ]);
});
