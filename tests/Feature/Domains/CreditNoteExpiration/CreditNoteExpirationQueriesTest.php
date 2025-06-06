<?php

declare(strict_types=1);

use App\Domains\CreditNoteExpiration\CreditNoteExpirationQueries;
use App\Models\CreditNote;

test('Credit Note Expiration can be added', function (): void {
    $creditNote = CreditNote::factory()->create();

    $creditNoteExpirationQueries = new CreditNoteExpirationQueries();
    $creditNoteExpirationQueries->addNew($creditNote->id, 100.10);

    $this->assertDatabaseHas('credit_note_expirations', [
        'credit_note_id' => $creditNote->id,
        'amount' => 100.10,
    ]);
});
