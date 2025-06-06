<?php

declare(strict_types=1);

namespace App\Domains\BookingPayment\DataPreparer;

use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Domains\Sale\DataPreparer\UserDataPreparer;
use App\Models\BookingPaymentPayment;
use App\Models\CreditNote;
use App\Models\CreditNoteUse;

class DataPreparer
{
    public function getCreditNote(BookingPaymentPayment $bookingPaymentPayment): array
    {
        /** @var ?CreditNoteUse $creditNoteUse */
        $creditNoteUse = $bookingPaymentPayment->creditNoteUse;
        if (! $creditNoteUse) {
            return [];
        }

        /** @var CreditNote $creditNote */
        $creditNote = $creditNoteUse->creditNote;

        $userDataPreparer = resolve(UserDataPreparer::class);

        return [
            'id' => $creditNote->id,
            'counter_update_id' => $creditNote->counter_update_id,
            'sale_return_id' => $creditNote->sale_return_id,
            'cancel_layaway_sale_id' => $creditNote->cancel_layaway_sale_id,
            'user_type' => $userDataPreparer->getUserType($creditNote),
            'user_id' => $creditNote->member_id,
            'member_id' => $creditNote->member_id,
            'expiry_date' => $creditNote->expiry_date,
            'total_amount' => $creditNote->total_amount,
            'available_amount' => $creditNote->available_amount,
            'status' => CreditNoteStatuses::getCaseNameByValue($creditNote->status),
        ];
    }
}
