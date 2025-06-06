<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfiguration\Resources;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\VoucherConfiguration\Enums\ExcludeByTypes;
use App\Domains\VoucherConfiguration\Services\VoucherConfigurationService;
use App\Models\VoucherConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosLoyaltyPointVoucherConfigurationListResource extends JsonResource
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

        $voucherType = VoucherConfigurationService::getVoucherType(
            $voucherConfiguration->restricted_by_type,
            $voucherConfiguration->voucher_type,
            $voucherConfiguration->discount_type
        );

        return [
            'id' => $voucherConfiguration->id,
            'voucher_type' => $voucherType,
            'exclude_by_type' => ExcludeByTypes::getCaseNameByValue($voucherConfiguration->exclude_by_type),
            'products' => $voucherConfiguration->products->pluck('id')->toArray(),
            'categories' => $voucherConfiguration->categories->pluck('id')->toArray(),
            'issue_minimum_spend_amount' => (float) $voucherConfiguration->issue_minimum_spend_amount,
            'use_minimum_spend_amount' => (float) $voucherConfiguration->use_minimum_spend_amount,
            'validity_days' => $voucherConfiguration->validity_days,
            $getValue => (float) $voucherConfiguration->get_value,
            'promotion_tiers' => $voucherConfiguration->voucherConfigurationTiers->map(
                fn ($voucherConfigurationTier): array => [
                    'loyalty_point' => (float) $voucherConfigurationTier->minimum_spend_amount,
                    $getValue => (float) $voucherConfigurationTier->get_value,
                ]
            ),
            'memberships' => $voucherConfiguration->memberships->map(
                fn ($membership): array => [
                    'id' => $membership->id,
                    'name' => $membership->name,
                ]
            ),
            'start_date' => $voucherConfiguration->start_date,
            'end_date' => $voucherConfiguration->end_date,
            'dream_price_applicable' => $voucherConfiguration->dream_price_applicable,
            'item_wise_promotion_applicable' => $voucherConfiguration->item_wise_promotion_applicable,
            'cart_wide_promotion_applicable' => $voucherConfiguration->cart_wide_promotion_applicable,
            'voucher_apply_footer_notes' => $voucherConfiguration->redemption_foot_note,
            'handover_footer_notes' => $voucherConfiguration->handover_foot_note,
            'redemption_foot_note' => $voucherConfiguration->redemption_foot_note,
            'handover_foot_note' => $voucherConfiguration->handover_foot_note,
            'title' => $voucherConfiguration->title,
            'description' => $voucherConfiguration->description,
            'terms_and_conditions' => $voucherConfiguration->terms_and_conditions,
            'image_url' => $voucherConfiguration->getDiskBasedFirstMediaUrl('image'),
            'thumbnail_url' => $voucherConfiguration->getDiskBasedFirstMediaUrl('thumbnail'),
            'status' => (int) $voucherConfiguration->status,
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
