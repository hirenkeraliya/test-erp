<?php

declare(strict_types=1);

namespace App\Domains\Voucher\Resources;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Voucher\Enums\VoucherStatusTypes;
use App\Domains\VoucherConfiguration\Services\VoucherConfigurationService;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppVoucherListResource extends JsonResource
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

        /** @var VoucherConfiguration $voucherConfiguration */
        $voucherConfiguration = $voucher->voucherConfiguration;

        return [
            'id' => $voucher->id,
            'created_by_store_id' => $voucher->created_by_location_id,
            'created_by_location_id' => $voucher->created_by_location_id,
            'discount_type' => DiscountTypes::getCaseNameByValue($voucher->discount_type),
            'voucher_type' => VoucherConfigurationService::getVoucherType(
                $voucherConfiguration->restricted_by_type,
                $voucherConfiguration->voucher_type,
                $voucherConfiguration->discount_type
            ),
            'number' => $voucher->number,
            'name' => $voucherConfiguration->title,
            'minimum_spend_amount' => $voucher->minimum_spend_amount,
            'percentage' => $voucher->percentage,
            'flat_amount' => $voucher->flat_amount,
            'expiry_date' => $voucher->expiry_date,
            'dream_price_applicable' => $voucher->dream_price_applicable,
            'item_wise_promotion_applicable' => $voucher->item_wise_promotion_applicable,
            'cart_wide_promotion_applicable' => $voucher->cart_wide_promotion_applicable,
            'transactions' => $voucher->getVoucherTransactions(),
            'voucher_apply_footer_notes' => $voucherConfiguration->redemption_foot_note,
            'handover_footer_notes' => $voucherConfiguration->handover_foot_note,
            'redemption_foot_note' => $voucherConfiguration->redemption_foot_note,
            'handover_foot_note' => $voucherConfiguration->handover_foot_note,
            'status' => VoucherStatusTypes::getFormattedArrayForPos($voucher->status),
        ];
    }
}
