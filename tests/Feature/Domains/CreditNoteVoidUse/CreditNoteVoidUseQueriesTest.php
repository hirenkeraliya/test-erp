<?php

declare(strict_types=1);

use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\CreditNoteVoidUse\CreditNoteVoidUseQueries;
use App\Models\CreditNote;
use App\Models\CreditNoteUse;
use App\Models\VoidSale;

test('the addNew method adds the credit note void use data', function (): void {
    $creditNote = CreditNote::factory()->create([
        'available_amount' => 10.00,
        'status' => CreditNoteStatuses::ACTIVE->value,
    ]);

    $creditNoteUse = CreditNoteUse::factory()->create([
        'credit_note_id' => $creditNote->id,
    ]);

    $voidSale = VoidSale::factory()->create();

    $creditNoteVoidUseQueries = resolve(CreditNoteVoidUseQueries::class);

    $creditNoteVoidUseQueries->addNew($creditNote->id, $creditNoteUse->id, $voidSale->id, 10.00);

    $this->assertDatabaseHas('credit_note_void_uses', [
        'credit_note_id' => $creditNote->id,
        'credit_note_uses_id' => $creditNoteUse->id,
        'void_sale_id' => $voidSale->id,
        'amount' => '10.00',
    ]);
});
