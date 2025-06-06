<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfiguration\Resources;

use App\Domains\Common\Enums\DiscountTypes;
use App\Models\VoucherConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosBirthdayVoucherConfigurationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var VoucherConfiguration $voucherConfiguration */
        $voucherConfiguration = $this;

        $getValue = static::getKeyNameAsPerSelectedVoucher($voucherConfiguration->discount_type);

        return [
            'id' => $voucherConfiguration->id,
            'use_minimum_spend_amount' => (float) $voucherConfiguration->use_minimum_spend_amount,
            'validity_days' => $voucherConfiguration->validity_days,
            $getValue => (float) $voucherConfiguration->get_value,
            'start_date' => $voucherConfiguration->start_date,
            'end_date' => $voucherConfiguration->end_date,
            'dream_price_applicable' => $voucherConfiguration->dream_price_applicable,
            'item_wise_promotion_applicable' => $voucherConfiguration->item_wise_promotion_applicable,
            'cart_wide_promotion_applicable' => $voucherConfiguration->cart_wide_promotion_applicable,
            'voucher_apply_footer_notes' => $voucherConfiguration->redemption_foot_note,
            'handover_footer_notes' => $voucherConfiguration->handover_foot_note,
            'redemption_foot_note' => $voucherConfiguration->redemption_foot_note,
            'handover_foot_note' => $voucherConfiguration->handover_foot_note,
        ];
    }

    public static function getKeyNameAsPerSelectedVoucher(?int $discountType): string
    {
        if ($discountType === DiscountTypes::PERCENTAGE->value) {
            return 'percentage';
        }

        return 'flat_amount';
    }
}
