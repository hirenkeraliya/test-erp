<?php

declare(strict_types=1);

namespace App\Domains\Voucher\Resources;

use App\Domains\Common\Enums\DiscountTypes;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosBirthdayVoucherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Voucher $voucher */
        $voucher = $this;

        /** @var Collection $voucherMismatches */
        $voucherMismatches = $voucher->mismatches;
        $messages = $voucherMismatches->pluck('message')->toArray();

        return [
            'id' => $voucher->id,
            'member_id' => $voucher->member_id,
            'created_by_store_id' => $voucher->created_by_location_id,
            'created_by_location_id' => $voucher->created_by_location_id,
            'discount_type' => DiscountTypes::getCaseNameByValue($voucher->discount_type),
            'number' => $voucher->number,
            'minimum_spend_amount' => $voucher->minimum_spend_amount,
            'percentage' => $voucher->percentage,
            'flat_amount' => $voucher->flat_amount,
            'expiry_date' => $voucher->expiry_date,
            'dream_price_applicable' => $voucher->dream_price_applicable,
            'item_wise_promotion_applicable' => $voucher->item_wise_promotion_applicable,
            'cart_wide_promotion_applicable' => $voucher->cart_wide_promotion_applicable,
            'mismatches' => $messages,
            'transactions' => $voucher->getVoucherTransactions(),
        ];
    }
}
