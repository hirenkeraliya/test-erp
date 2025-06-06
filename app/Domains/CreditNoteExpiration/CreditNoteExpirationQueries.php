<?php

declare(strict_types=1);

namespace App\Domains\CreditNoteExpiration;

use App\Models\CreditNoteExpiration;

class CreditNoteExpirationQueries
{
    public function addNew(int $creditNoteId, float $amount): void
    {
        CreditNoteExpiration::create([
            'credit_note_id' => $creditNoteId,
            'amount' => $amount,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'credit_note_id,amount';
    }
}
