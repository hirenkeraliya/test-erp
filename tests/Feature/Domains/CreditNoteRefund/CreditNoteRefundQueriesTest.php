<?php

declare(strict_types=1);

use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\CreditNoteRefund\CreditNoteRefundQueries;
use App\Domains\CreditNoteRefund\DataObjects\CreditNoteRefundData;
use App\Models\CounterUpdate;
use App\Models\CreditNote;
use App\Models\CreditNoteRefund;
use App\Models\Currency;
use App\Models\PaymentType;
use App\Models\StoreManager;
use Carbon\Carbon;

test(
    'the getByCounterUpdateIdWithPaymentType method returns the credit note refunds by counter update id',
    function (): void {
        $counterUpdate = CounterUpdate::factory()->create();

        $creditNoteRefund = CreditNoteRefund::factory()->create([
            'counter_update_id' => $counterUpdate->id,
        ]);

        $creditNoteRefundQueries = resolve(CreditNoteRefundQueries::class);
        $response = $creditNoteRefundQueries->getByCounterUpdateIdWithPaymentType($counterUpdate->id);

        expect($response->first()->toArray())
        ->toHaveKey('id', $creditNoteRefund->id)
        ->toHaveKey('amount', $creditNoteRefund->amount)
        ->toHaveKey('payment_type_id', $creditNoteRefund->payment_type_id)
        ->toHaveKey('payment_type');
    }
);

test('A credit note refund can be added', function (): void {
    $creditNote = CreditNote::factory()->create([
        'total_amount' => 100,
        'available_amount' => 100,
        'expiry_date' => Carbon::now()->addDays(2),
        'status' => CreditNoteStatuses::ACTIVE->value,
    ]);

    $paymentType = PaymentType::factory()->create();
    $storeManager = StoreManager::factory()->create();
    $currency = Currency::factory()->create();

    $prepareArray = [
        'payment_type_id' => $paymentType->id,
        'store_manager_id' => $storeManager->id,
        'amount' => 100,
        'passcode' => '123456',
        'currency_id' => $currency->id,
        'current_currency_rate' => 1,
        'currency_amount' => 100,
    ];

    $creditNoteRefundData = new CreditNoteRefundData(...$prepareArray);

    $counterUpdate = CounterUpdate::factory()->create();

    $creditNoteRefundQueries = resolve(CreditNoteRefundQueries::class);
    $creditNoteRefundQueries->addNew($creditNote->id, $counterUpdate->id, $creditNoteRefundData);

    $this->assertDatabaseHas('credit_note_refunds', [
        'credit_note_id' => $creditNote->id,
        'counter_update_id' => $counterUpdate->id,
        'payment_type_id' => $creditNoteRefundData->payment_type_id,
        'amount' => $creditNoteRefundData->amount,
        'store_manager_id' => $creditNoteRefundData->store_manager_id,
    ]);
});

test('A credit note refund can be added for cancel layaway sale', function (): void {
    $creditNote = CreditNote::factory()->create([
        'total_amount' => 100,
        'available_amount' => 100,
        'expiry_date' => Carbon::now()->addDays(2),
        'status' => CreditNoteStatuses::ACTIVE->value,
    ]);

    $paymentType = PaymentType::factory()->create();
    $storeManager = StoreManager::factory()->create();

    $counterUpdate = CounterUpdate::factory()->create();

    $creditNoteRefundQueries = resolve(CreditNoteRefundQueries::class);
    $creditNoteRefundQueries->addNewForCancelLayawaySale(
        $creditNote->id,
        $counterUpdate->id,
        $paymentType->id,
        100,
        $storeManager->id
    );

    $this->assertDatabaseHas('credit_note_refunds', [
        'credit_note_id' => $creditNote->id,
        'counter_update_id' => $counterUpdate->id,
        'payment_type_id' => $paymentType->id,
        'amount' => 100.00,
        'store_manager_id' => $storeManager->id,
    ]);
});
