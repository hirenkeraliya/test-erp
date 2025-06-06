<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\OrderPayment\OrderPaymentQueries;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\StoreManager;
use Carbon\Carbon;

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

    $this->orderPayment = OrderPayment::factory()->create([
        'order_id' => $this->order->getKey(),
        'store_manager_id' => $this->storeManager->getKey(),
        'location_id' => $this->location->getKey(),
    ]);

    $this->orderPaymentQueries = new OrderPaymentQueries();
});

test('addNewForEcommerce method creates the order payments', function (): void {
    $paymentType = PaymentType::factory()->create();

    $paymentDetails = [
        'type_id' => $paymentType->getKey(),
        'amount' => $this->order->getTotalAmountPaid(),
        'notes' => $paymentType->name,
    ];

    $this->orderPaymentQueries->addNewForEcommerce($this->order, $paymentDetails, $this->location->getKey());

    assertDatabaseHas(OrderPayment::class, [
        'order_id' => $this->order->getKey(),
        'store_manager_id' => null,
        'location_id' => $this->location->getKey(),
        'payment_type_id' => $paymentType->getKey(),
        'amount' => $this->order->getTotalAmountPaid(),
        'notes' => $paymentType->name,
    ]);
});
