<?php

declare(strict_types=1);

namespace App\Domains\CreditNoteUse;

use App\Models\CreditNote;
use App\Models\CreditNoteUse;
use Illuminate\Support\Collection;

class CreditNoteUseQueries
{
    public function addNew(
        CreditNote $creditNote,
        int $salePaymentId,
        int $counterUpdateId,
        float $paymentAmount
    ): void {
        CreditNoteUse::create([
            'credit_note_id' => $creditNote->id,
            'counter_update_id' => $counterUpdateId,
            'sale_payment_id' => $salePaymentId,
            'amount' => $paymentAmount,
        ]);
    }

    public function recordBookingPaymentUse(
        CreditNote $creditNote,
        int $bookingPaymentId,
        int $counterUpdateId,
        float $paymentAmount
    ): void {
        CreditNoteUse::create([
            'credit_note_id' => $creditNote->id,
            'counter_update_id' => $counterUpdateId,
            'booking_payment_payment_id' => $bookingPaymentId,
            'amount' => $paymentAmount,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,counter_update_id,sale_payment_id,amount,credit_note_id,booking_payment_payment_id';
    }

    public function getColumnNamesForReports(): string
    {
        return 'id,credit_note_id,sale_payment_id,amount,created_at,booking_payment_payment_id';
    }

    public function getBySalePaymentId(int $salePaymentId): ?CreditNoteUse
    {
        return CreditNoteUse::select('id', 'credit_note_id')
            ->where('sale_payment_id', $salePaymentId)
            ->first();
    }

    public function getByCounterUpdateId(int $counterUpdateId): Collection
    {
        return CreditNoteUse::select('id', 'amount')
            ->where('counter_update_id', $counterUpdateId)
            ->get();
    }
}
