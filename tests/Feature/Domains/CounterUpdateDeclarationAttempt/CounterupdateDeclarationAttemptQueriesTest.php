<?php

declare(strict_types=1);

use App\Domains\CounterUpdateDeclarationAttempt\CounterUpdateDeclarationAttemptQueries;
use App\Models\CounterUpdate;
use App\Models\CounterUpdateDeclarationAttempt;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->counterUpdate = CounterUpdate::factory()->create([
        'opening_balance' => '100',
        'closed_at' => null,
        'mismatch_amount' => null,
        'amount_mismatch_reason' => null,
    ]);

    $this->counterUpdateDeclarationAttemptA = CounterUpdateDeclarationAttempt::factory()->create([
        'counter_update_id' => $this->counterUpdate->id,
    ]);
    $this->counterUpdateDeclarationAttemptB = CounterUpdateDeclarationAttempt::factory()->create([
        'counter_update_id' => $this->counterUpdate->id,
    ]);

    $this->counterUpdateDeclarationAttemptQueries = new CounterUpdateDeclarationAttemptQueries();
});

test(
    'the getList method returns the counter update declaration attempt list',
    function (): void {
        $response = $this->counterUpdateDeclarationAttemptQueries->getList($this->counterUpdate->id);

        expect($response->first()->toArray())
            ->toHaveKey('id', $this->counterUpdateDeclarationAttemptA->id)
            ->toHaveKey('offline_id', $this->counterUpdateDeclarationAttemptA->offline_id)
            ->toHaveKey('counter_update_declaration_attempt_payments')
            ->toHaveKey('counter_update')
            ->toHaveKey('counter_update.cashier')
            ->toHaveKey('counter_update.cashier.employee');
    }
);

test('Counter update declaration attempt can be added', function (): void {
    $counterUpdateDeclarationAttempt = CounterUpdateDeclarationAttempt::factory()->make([
        'counter_update_id' => $this->counterUpdate->id,
        'offline_id' => 'A12345122',
        'happened_at' => Carbon::now()->format('Y-m-d H:i:s'),
    ])->toArray();

    $this->counterUpdateDeclarationAttemptQueries->addNew(
        $counterUpdateDeclarationAttempt['offline_id'],
        $counterUpdateDeclarationAttempt['happened_at'],
        $this->counterUpdate->id
    );

    $this->assertDatabaseHas('counter_update_declaration_attempts', [
        'counter_update_id' => $this->counterUpdate->id,
        'offline_id' => $counterUpdateDeclarationAttempt['offline_id'],
    ]);
});
