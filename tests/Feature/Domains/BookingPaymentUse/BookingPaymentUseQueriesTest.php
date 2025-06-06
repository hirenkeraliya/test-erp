<?php

declare(strict_types=1);

use App\Domains\BookingPayment\Enums\BookingPaymentStatuses;
use App\Domains\BookingPaymentUse\BookingPaymentUseQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Models\BookingPayment;
use App\Models\BookingPaymentUse;
use App\Models\PaymentType;
use App\Models\Sale;
use App\Models\SalePayment;

test('the addNew method adds the booking payment use data', function (): void {
    $bookingPayment = BookingPayment::factory()->create([
        'available_amount' => 10.00,
        'status' => BookingPaymentStatuses::ACTIVE->value,
    ]);

    $staticBookingPaymentType = PaymentType::find(StaticPaymentTypes::BOOKING_PAYMENT->value);

    if ($staticBookingPaymentType) {
        $bookingPaymentId = $staticBookingPaymentType->id;
    } else {
        $paymentType = PaymentType::factory()->create([
            'id' => StaticPaymentTypes::BOOKING_PAYMENT->value,
        ]);

        $bookingPaymentId = $paymentType->id;
    }

    $sale = Sale::factory()->create();

    $salePayment = SalePayment::factory()->create([
        'sale_id' => $sale->id,
        'payment_type_id' => $bookingPaymentId,
        'amount' => 10,
    ]);

    $bookingPaymentUseQueries = resolve(BookingPaymentUseQueries::class);

    $bookingPaymentUseQueries->addNew($bookingPayment, $salePayment->id, $sale->counter_update_id, 10.00);

    $this->assertDatabaseHas('booking_payment_uses', [
        'booking_payment_id' => $bookingPayment->id,
        'counter_update_id' => $sale->counter_update_id,
        'sale_payment_id' => $salePayment->id,
        'amount' => '10.00',
    ]);
});

test('the getByCounterUpdateId method return the booking payment use data by counter update id', function (): void {
    $bookingPaymentUse = BookingPaymentUse::factory()->create([
        'amount' => 10.00,
    ]);

    $bookingPaymentUseQueries = resolve(BookingPaymentUseQueries::class);
    $response = $bookingPaymentUseQueries->getByCounterUpdateId($bookingPaymentUse->counter_update_id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $bookingPaymentUse->id)
        ->toHaveKey('amount', $bookingPaymentUse->amount);
});
