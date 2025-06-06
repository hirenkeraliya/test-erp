<?php

declare(strict_types=1);

use App\Domains\StoreDayClosePayment\StoreDayClosePaymentQueries;
use App\Models\PaymentType;
use App\Models\StoreDayClose;

test('addNew method add the store day close payment data', function (): void {
    $storeDayClose = StoreDayClose::factory()->create();
    $paymentType = PaymentType::factory()->create();
    $payments = collect([
        [
            'total_transactions' => 1,
            'total_amount' => 1,
        ],
    ]);

    $storeDayClosePaymentQueries = resolve(StoreDayClosePaymentQueries::class);
    $storeDayClosePaymentQueries->addNew($storeDayClose->id, $paymentType->id, $payments);

    $this->assertDatabaseHas('store_day_close_payments', [
        'store_day_close_id' => $storeDayClose->id,
        'payment_type_id' => $paymentType->id,
        'total_transactions' => '1.00',
        'total_amount' => '1.00',
    ]);
});
