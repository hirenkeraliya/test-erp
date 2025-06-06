<?php

declare(strict_types=1);

use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\BookingPaymentPayments\BookingPaymentPaymentQueries;
use App\Domains\BookingPaymentPayments\DataObjects\BookingPaymentTopUpData;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\BookingPayment;
use App\Models\BookingPaymentPayment;
use App\Models\Cashier;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Location;
use App\Models\PaymentType;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;
    $this->cashier = Cashier::factory()->create();
    $this->location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);
    $this->counter = Counter::factory()->create([
        'location_id' => $this->location->id,
    ]);

    $this->counterUpdate = CounterUpdate::factory()->create([
        'counter_id' => $this->counter->id,
        'cashier_id' => $this->cashier->id,
    ]);

    $this->bookingPayment = BookingPayment::factory()->create([
        'counter_update_id' => $this->counterUpdate->id,
        'status' => BookingPaymentStatuses::ACTIVE,
    ]);

    $this->cashier->counter_update_id = $this->counterUpdate->id;
    $this->cashier->save();

    $this->bookingPaymentPaymentQueries = new BookingPaymentPaymentQueries();
});

test('the addNew method add the booking payment payment data', function (): void {
    $paymentType = PaymentType::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $validateData = [
        'payment_type_id' => $paymentType->id,
        'amount' => 100,
        'remarks' => null,
    ];

    $bookingPaymentTopUpData = new BookingPaymentTopUpData(...$validateData);

    $this->bookingPaymentPaymentQueries->addNew(
        $bookingPaymentTopUpData,
        $this->bookingPayment->id,
        $this->cashier->counter_update_id,
    );

    $this->assertDatabaseHas('booking_payment_payments', [
        'booking_payment_id' => $this->bookingPayment->id,
        'counter_update_id' => $this->cashier->counter_update_id,
        'payment_type_id' => $validateData['payment_type_id'],
        'amount' => $validateData['amount'],
        'remarks' => $validateData['remarks'],
    ]);
});

test(
    'the getByCounterUpdateIdWithPaymentType method returns the booking payment payments with payment type by counter update id',
    function (): void {
        $payment = PaymentType::factory()->create([
            'company_id' => $this->companyId,
        ]);

        $bookingPayment = BookingPaymentPayment::factory()->create([
            'booking_payment_id' => $this->bookingPayment->id,
            'counter_update_id' => $this->cashier->counter_update_id,
            'payment_type_id' => $payment->id,
        ]);

        $response = $this->bookingPaymentPaymentQueries->getByCounterUpdateIdWithPaymentType(
            $this->cashier->counter_update_id,
        );

        expect($response->first()->toArray())
            ->toHaveKey('payment_type_id', $bookingPayment->payment_type_id)
            ->toHaveKey('amount', $bookingPayment->amount)
            ->toHaveKey('payment_type');
    }
);

test('the addNewForMultiple method add the booking payment payment data', function (): void {
    $paymentType = PaymentType::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $data = [
        'payment_type_id' => $paymentType->id,
        'amount' => 10,
        'extra_details' => [],
    ];

    $this->bookingPaymentPaymentQueries->addNewForMultiple(
        $this->bookingPayment->id,
        $this->cashier->counter_update_id,
        $data,
    );

    $this->assertDatabaseHas('booking_payment_payments', [
        'booking_payment_id' => $this->bookingPayment->id,
        'counter_update_id' => $this->cashier->counter_update_id,
        'payment_type_id' => $data['payment_type_id'],
        'amount' => $data['amount'],
    ]);
});
