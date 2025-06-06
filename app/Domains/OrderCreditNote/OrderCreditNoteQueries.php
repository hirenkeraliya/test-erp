<?php

declare(strict_types=1);

namespace App\Domains\OrderCreditNote;

use App\Domains\CreditNote\Enums\CreditNoteStatuses;
use App\Models\OrderCreditNote;
use Carbon\Carbon;

class OrderCreditNoteQueries
{
    public function addNew(
        int $storeManagerId,
        int $locationId,
        int $orderReturnId,
        float $amount,
        int $memberId,
        ?int $creditNoteExpirationDays,
    ): OrderCreditNote {
        $carbonDate = Carbon::now();

        $expiryDate = $creditNoteExpirationDays > 0
            ? $carbonDate->addDays($creditNoteExpirationDays)->format('Y-m-d')
            : null;

        return OrderCreditNote::create([
            'store_manager_id' => $storeManagerId,
            'location_id' => $locationId,
            'order_return_id' => $orderReturnId,
            'member_id' => $memberId,
            'expiry_date' => $expiryDate,
            'total_amount' => $amount,
            'available_amount' => $amount,
            'status' => CreditNoteStatuses::ACTIVE->value,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,store_manager_id,location_id,order_return_id,member_id,expiry_date,total_amount,available_amount,status';
    }

    public function updateMember(int $oldMemberId, int $newMemberId): void
    {
        $orderCreditNotes = OrderCreditNote::query()
            ->select('id', 'member_id')
            ->where('member_id', $oldMemberId)
            ->get();

        foreach ($orderCreditNotes as $orderCreditNote) {
            $orderCreditNote->member_id = $newMemberId;
            $orderCreditNote->save();
        }
    }
}
