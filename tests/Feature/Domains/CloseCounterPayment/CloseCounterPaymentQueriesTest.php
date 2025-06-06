<?php

declare(strict_types=1);

use App\Domains\CloseCounterPayment\CloseCounterPaymentQueries;
use App\Models\CounterUpdate;
use App\Models\PaymentType;

test('New close counter payment can be added', function (): void {
    $counterUpdate = CounterUpdate::factory()->create();
    $paymentType = PaymentType::factory()->create();

    $salePayment = [
        'payment_type_id' => $paymentType->id,
        'total_transactions' => 1,
        'total' => 1,
    ];

    $closeCounterPaymentQueries = resolve(CloseCounterPaymentQueries::class);
    $closeCounterPaymentQueries->addNew($counterUpdate->id, $salePayment);

    $this->assertDatabaseHas('close_counter_payments', [
        'counter_update_id' => $counterUpdate->id,
        'payment_type_id' => $salePayment['payment_type_id'],
        'total_transactions' => $salePayment['total_transactions'],
        'total_amount' => $salePayment['total'],
    ]);
});
