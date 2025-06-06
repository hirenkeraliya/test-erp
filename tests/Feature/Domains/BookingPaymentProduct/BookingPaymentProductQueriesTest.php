<?php

declare(strict_types=1);

use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\BookingPaymentProduct\BookingPaymentProductQueries;
use App\Domains\BookingPaymentProduct\DataObjects\BookingPaymentProductData;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\BookingPayment;
use App\Models\BookingPaymentProduct;
use App\Models\Cashier;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\Promoter;
use Illuminate\Http\Request;

beforeEach(function (): void {
    $this->location = Location::factory()->create([
        'type_id' => LocationTypes::STORE->value,
    ]);
    $this->cashier = Cashier::factory()->create();
    $this->counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $this->counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $this->counter->id,
        'cashier_id' => $this->cashier->id,
    ]);
    $this->cashier->counter_update_id = $this->counterUpdate->id;
    $this->cashier->save();

    $this->productId = Product::factory()->create([
        'company_id' => $this->location->company_id,
    ])->id;

    $this->bookingPayment = BookingPayment::factory()->create([
        'counter_update_id' => $this->counterUpdate->id,
        'status' => BookingPaymentStatuses::ACTIVE,
    ]);

    $this->bookingPaymentProduct = BookingPaymentProduct::factory()->create([
        'booking_payment_id' => $this->bookingPayment->id,
        'product_id' => $this->productId,
    ]);

    $this->bookingPaymentProductQueries = new BookingPaymentProductQueries();
});

test('the createMany method updates the booking payment products details', function (): void {
    $request = new Request();

    $request->setUserResolver(fn (): Cashier => $this->cashier);

    $employee = Employee::factory()->create([
        'company_id' => $this->location->company_id,
    ]);

    $promoter = Promoter::factory()->create([
        'employee_id' => $employee->id,
    ]);

    $products = [
        [
            'product_id' => $this->productId,
            'quantity' => 5,
            'promoter_ids' => [$promoter->id],
            'price' => 1,
        ],
    ];

    $bookingPaymentProductData = new BookingPaymentProductData($products, []);

    $this->bookingPaymentProductQueries->createMany(
        $bookingPaymentProductData->products,
        $this->bookingPayment->id,
    );

    $this->assertDatabaseHas('booking_payment_products', [
        'booking_payment_id' => $this->bookingPayment->id,
        'product_id' => $products[0]['product_id'],
    ]);

    $this->assertDatabaseHas('booking_payment_product_promoter', [
        'promoter_id' => $promoter->id,
    ]);
});

test('the deleteBookingPaymentProducts method deletes the given booking payment id', function (): void {
    $this->bookingPaymentProductQueries->deleteBookingPaymentProducts($this->bookingPayment->id);

    $this->assertSoftDeleted($this->bookingPaymentProduct);
});
