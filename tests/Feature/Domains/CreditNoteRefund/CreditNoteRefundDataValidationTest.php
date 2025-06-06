<?php

declare(strict_types=1);

use App\Domains\CreditNoteRefund\DataObjects\CreditNoteRefundData;
use App\Models\PaymentType;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

test('validation pass when all credit note refund details are provided', function (): void {
    $paymentType = PaymentType::factory()->create([
        'name' => 'Cash',
    ]);

    $storeManager = StoreManager::factory()->create();

    $request = new Request([
        'payment_type_id' => $paymentType->id,
        'amount' => 100,
        'store_manager_id' => $storeManager->id,
        'passcode' => '123456',
    ]);

    $request->validate(CreditNoteRefundData::rules());

    $this->assertTrue(true);
});

test('validation throws an exception when records do not exist', function (): void {
    PaymentType::factory()->create([
        'name' => 'Cash',
    ]);

    StoreManager::factory()->create();

    $request = new Request([
        'payment_type_id' => 200,
        'amount' => 100,
        'currency_id' => 1,
        'current_currency_rate' => 1,
        'currency_amount' => 100,
        'store_manager_id' => 200,
        'passcode' => '.',
    ]);

    $request->validate(CreditNoteRefundData::rules());
})->throws(ValidationException::class);
