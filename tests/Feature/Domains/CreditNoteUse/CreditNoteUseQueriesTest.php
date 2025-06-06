<?php

declare(strict_types=1);

use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\CreditNoteUse\CreditNoteUseQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Models\CreditNote;
use App\Models\CreditNoteUse;
use App\Models\PaymentType;
use App\Models\Sale;
use App\Models\SalePayment;

test('the addNew method adds the credit note use data', function (): void {
    $creditNote = CreditNote::factory()->create([
        'available_amount' => 10.00,
        'status' => CreditNoteStatuses::ACTIVE->value,
    ]);

    $staticCreditNoteType = PaymentType::find(StaticPaymentTypes::CREDIT_NOTE->value);

    if ($staticCreditNoteType) {
        $creditNoteId = $staticCreditNoteType->id;
    } else {
        $paymentType = PaymentType::factory()->create([
            'id' => StaticPaymentTypes::CREDIT_NOTE->value,
        ]);

        $creditNoteId = $paymentType->id;
    }

    $sale = Sale::factory()->create();

    $salePayment = SalePayment::factory()->create([
        'sale_id' => $sale->id,
        'payment_type_id' => $creditNoteId,
        'amount' => 10,
    ]);

    $creditNoteUseQueries = resolve(CreditNoteUseQueries::class);

    $creditNoteUseQueries->addNew($creditNote, $salePayment->id, $sale->counter_update_id, 10.00);

    $this->assertDatabaseHas('credit_note_uses', [
        'credit_note_id' => $creditNote->id,
        'counter_update_id' => $sale->counter_update_id,
        'sale_payment_id' => $salePayment->id,
        'amount' => '10.00',
    ]);
});

test(
    'the getBySalePaymentId method returns the credit note use by sale payment id',
    function (): void {
        $salePayment = SalePayment::factory()->create();

        $creditNoteUse = CreditNoteUse::factory()->create([
            'sale_payment_id' => $salePayment->id,
        ]);

        $creditNoteUseQueries = resolve(CreditNoteUseQueries::class);
        $response = $creditNoteUseQueries->getBySalePaymentId($salePayment->id);

        expect($response->toArray())
            ->toHaveKey('id', $creditNoteUse->id)
            ->toHaveKey('credit_note_id', $creditNoteUse->credit_note_id);
    }
);

test('the getByCounterUpdateId method return the credit note use data by counter update id', function (): void {
    $creditNoteUse = CreditNoteUse::factory()->create([
        'amount' => 10.00,
    ]);

    $creditNoteUseQueries = resolve(CreditNoteUseQueries::class);
    $response = $creditNoteUseQueries->getByCounterUpdateId($creditNoteUse->counter_update_id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $creditNoteUse->id)
        ->toHaveKey('amount', $creditNoteUse->amount);
});
