<?php

declare(strict_types=1);

namespace App\Domains\CreditNote\Services;

use App\Domains\CreditNote\CreditNoteQueries;
use App\Domains\Sale\DataObjects\SaleData;
use App\Models\SaleReturn;
use Illuminate\Support\Collection;

class CreditNoteService
{
    public function getCreditNotes(?SaleReturn $saleReturn, SaleData $saleData, int $locationId): Collection
    {
        $creditNotes = collect([]);

        if ($saleReturn instanceof SaleReturn && $saleReturn->creditNote) {
            $creditNotes->push($saleReturn->creditNote);
        }

        $creditNoteIds = collect($saleData->payments)->pluck('credit_note_id')->toArray();
        $creditNoteQueries = resolve(CreditNoteQueries::class);
        $paymentCreditNotes = $creditNoteQueries->getByIds($creditNoteIds, $locationId);

        foreach ($paymentCreditNotes as $paymentCreditNote) {
            $creditNotes->push($paymentCreditNote);
        }

        return $creditNotes;
    }
}
