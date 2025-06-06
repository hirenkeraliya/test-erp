<?php

declare(strict_types=1);

namespace App\Domains\CreditNoteVoidUse;

use App\Models\CreditNoteVoidUse;

class CreditNoteVoidUseQueries
{
    public function addNew(int $creditNoteId, int $creditNoteUseId, int $voidSaleId, float $amount): void
    {
        CreditNoteVoidUse::create([
            'credit_note_id' => $creditNoteId,
            'credit_note_uses_id' => $creditNoteUseId,
            'void_sale_id' => $voidSaleId,
            'amount' => $amount,
        ]);
    }
}
