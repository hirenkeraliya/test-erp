<?php

declare(strict_types=1);

namespace App\Domains\LoyaltyPointUpdate\Resources;

use App\Domains\LoyaltyPointUpdate\Enums\LoyaltyPointUpdateTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\LoyaltyPointUpdate;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberAppLoyaltyPointUsedListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var LoyaltyPointUpdate $loyaltyPointUpdate */
        $loyaltyPointUpdate = $this;

        /** @var Sale $affectedBy */
        $affectedBy = $loyaltyPointUpdate->affectedBy;

        $locationName = null;

        if ($affectedBy instanceof Sale) {
            /** @var CounterUpdate $counterUpdate */
            $counterUpdate = $affectedBy->counterUpdate;

            /** @var Counter $counter */
            $counter = $counterUpdate->counter;

            $location = $counter->location;

            $locationName = $location?->name;
        }

        return [
            'id' => $loyaltyPointUpdate->id,
            'before_points' => $loyaltyPointUpdate->closing_loyalty_points_balance - $loyaltyPointUpdate->points,
            'used_points' => $loyaltyPointUpdate->points,
            'after_points' => $loyaltyPointUpdate->closing_loyalty_points_balance,
            'type_id' => LoyaltyPointUpdateTypes::getCaseNameByValue($loyaltyPointUpdate->type_id),
            'affected_by_type' => $loyaltyPointUpdate->affected_by_type,
            'sale_id' => $loyaltyPointUpdate->affected_by_id,
            'happened_at' => $loyaltyPointUpdate->happened_at,
            'sale' => [
                'id' => $affectedBy->id,
                'offline_sale_id' => $affectedBy->offline_sale_id,
                'member_id' => $affectedBy->member_id,
                'total_tax_amount' => (float) $affectedBy->total_tax_amount,
                'cart_discount_amount' => (float) $affectedBy->cart_discount_amount,
                'items_discount_amount' => (float) $affectedBy->items_discount_amount,
                'total_discount_amount' => (float) $affectedBy->total_discount_amount,
                'total_amount_before_round_off' => (float) $affectedBy->total_amount_before_round_off,
                'round_off_amount' => (float) $affectedBy->round_off,
                'change_due' => (float) $affectedBy->change_due,
                'total_amount_paid' => (float) $affectedBy->total_amount_paid,
                'layaway_pending_amount' => (float) $affectedBy->layaway_pending_amount,
                'layaway_completed_at' => (float) $affectedBy->layaway_completed_at,
                'credit_pending_amount' => (float) $affectedBy->credit_pending_amount,
                'credit_completed_at' => (float) $affectedBy->credit_completed_at,
                'sale_notes' => $affectedBy->notes,
                'bill_reference_number' => $affectedBy->bill_reference_number,
                'extra_details' => $affectedBy->extra_details ?? null,
                'has_mismatch' => $affectedBy->has_mismatch,
                'status' => SaleStatus::getCaseNameByValue($affectedBy->getStatus()),
                'happened_at' => $affectedBy->happened_at,
                'location_name' => $locationName,
            ],
        ];
    }
}
