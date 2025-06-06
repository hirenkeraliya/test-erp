<?php

declare(strict_types=1);

use App\Domains\CounterUpdateDeclarationAttemptPayment\CounterUpdateDeclarationAttemptPaymentQueries;
use App\Models\CounterUpdateDeclarationAttempt;
use App\Models\CounterUpdateDeclarationAttemptPayment;

beforeEach(function (): void {
    $this->counterUpdateDeclarationAttemptA = CounterUpdateDeclarationAttempt::factory()->create();

    $this->counterUpdateDeclarationAttemptPaymentQueries = new CounterUpdateDeclarationAttemptPaymentQueries();
});

test('Counter update declaration attempt payments can be added', function (): void {
    $counterUpdateDeclarationAttemptPayment = CounterUpdateDeclarationAttemptPayment::factory()->make([
        'counter_update_declaration_attempt_id' => $this->counterUpdateDeclarationAttemptA->id,
    ]);

    $payments = [
        [
            'counter_update_declaration_attempt_id' => $this->counterUpdateDeclarationAttemptA->id,
            'payment_type_id' => $counterUpdateDeclarationAttemptPayment->payment_type_id,
            'declared_amount' => $counterUpdateDeclarationAttemptPayment->declared_amount,
            'calculated_amount' => $counterUpdateDeclarationAttemptPayment->calculated_amount,
            'denominations' => null,
        ],
    ];

    $this->counterUpdateDeclarationAttemptPaymentQueries->createMany($payments);

    $this->assertDatabaseHas('counter_update_declaration_attempt_payments', [
        'counter_update_declaration_attempt_id' => $this->counterUpdateDeclarationAttemptA->id,
        'payment_type_id' => $counterUpdateDeclarationAttemptPayment->payment_type_id,
        'declared_amount' => $counterUpdateDeclarationAttemptPayment->declared_amount,
        'calculated_amount' => $counterUpdateDeclarationAttemptPayment->calculated_amount,
    ]);
});
