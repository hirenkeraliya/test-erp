<?php

declare(strict_types=1);

namespace App\Domains\HoldSaleDetail;

use App\CommonFunctions;
use App\Domains\HoldSale\DataObjects\HoldSaleData;
use App\Models\HoldSaleDetail;
use Closure;

class HoldSaleDetailQueries
{
    public function addNew(
        int $holdSaleId,
        HoldSaleData $holdSaleData,
        array $extraDetails,
        ?int $memberId,
    ): HoldSaleDetail {
        return HoldSaleDetail::create([
            'hold_sale_id' => $holdSaleId,
            'member_id' => $memberId,
            'happened_at' => $holdSaleData->happened_at,
            'released_at' => $holdSaleData->released_at ?? null,
            'total_amount_paid' => $holdSaleData->total_amount_paid ?? 0.00,
            'total_tax_amount' => $holdSaleData->total_tax_amount ?? 0.00,
            'cart_discount_amount' => $holdSaleData->cart_discount_amount ?? 0.00,
            'items_discount_amount' => $holdSaleData->items_discount_amount ?? 0.00,
            'total_discount_amount' => $holdSaleData->total_discount_amount ?? 0.00,
            'round_off' => $holdSaleData->round_off ?? 0.00,
            'change_due' => $holdSaleData->change_due ?? 0.00,
            'bill_reference_number' => $holdSaleData->bill_reference_number,
            'notes' => $holdSaleData->notes,
            'extra_details' => $extraDetails,
            'is_layaway' => $holdSaleData->is_layaway,
            'layaway_pending_amount' => $holdSaleData->layaway_pending_amount,
            'is_credit_sale' => $holdSaleData->is_credit_sale,
            'credit_pending_amount' => $holdSaleData->credit_pending_amount,
            'store_manager_id' => $holdSaleData->store_manager_id,
            'reason' => $holdSaleData->reason,
        ]);
    }

    public static function getColumnNamesForPos(): string
    {
        return 'id,hold_sale_id,member_id,happened_at,released_at,total_amount_paid,total_tax_amount,cart_discount_amount,items_discount_amount,total_discount_amount,round_off,change_due,bill_reference_number,notes,extra_details,is_layaway,layaway_pending_amount,is_credit_sale,credit_pending_amount,reason';
    }

    public function filterByHappenedAtWithinDateRange(array $date): Closure
    {
        return fn ($query) => $query->where('happened_at', '>=', CommonFunctions::addStartTime($date[0]))
            ->where('happened_at', '<=', CommonFunctions::addEndTime($date[1]));
    }

    public function updateMember(int $oldMemberId, int $newMemberId): void
    {
        $holdSaleDetails = HoldSaleDetail::query()
            ->select('id', 'member_id')
            ->where('member_id', $oldMemberId)
            ->get();

        foreach ($holdSaleDetails as $holdSaleDetail) {
            $holdSaleDetail->member_id = $newMemberId;
            $holdSaleDetail->save();
        }
    }
}
