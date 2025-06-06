<?php

declare(strict_types=1);

namespace App\Domains\Member\Resources;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Voucher\Enums\VoucherStatusTypes;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaginatedVouchersListResource extends JsonResource
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

        $expiryDate = '';

        if ($voucher->expiry_date) {
            /** @var Carbon $expiryDateFormat */
            $expiryDateFormat = Carbon::createFromFormat('Y-m-d', $voucher->expiry_date);
            $expiryDate = $expiryDateFormat->format('d-m-Y');
        }

        return [
            'id' => $voucher->id,
            'name' => $voucher->voucherConfiguration?->title,
            'created_by_location_id' => $voucher->created_by_location_id,
            'discount_type' => DiscountTypes::getCaseNameByValue($voucher->discount_type),
            'number' => $voucher->number,
            'minimum_spend_amount' => $voucher->minimum_spend_amount,
            'percentage' => $voucher->percentage,
            'flat_amount' => $voucher->flat_amount,
            'used_at' => $voucher->used_at,
            'expiry_date' => $expiryDate,
            'transactions' => $voucher->getVoucherTransactions(),
            'status' => VoucherStatusTypes::getFormattedArrayForPos($voucher->status),
        ];
    }
}
