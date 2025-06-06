<?php

declare(strict_types=1);

namespace App\Domains\Voucher\Resources;

use App\Domains\Voucher\Enums\VoucherStatusTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Models\Location;
use App\Models\Member;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherListResource extends JsonResource
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

        /** @var ?Member $member */
        $member = $voucher->member;

        /** @var VoucherConfiguration $voucherConfiguration */
        $voucherConfiguration = $voucher->voucherConfiguration;

        /** @var ?Location $location */
        $location = $voucher->createdByLocation;

        /** @var Carbon $createdAt */
        $createdAt = $voucher->created_at;

        /** @var Carbon|string $expiryDate */
        $expiryDate = '';

        if ($voucher->expiry_date) {
            /** @var Carbon $expiryDateFormat */
            $expiryDateFormat = Carbon::createFromFormat('Y-m-d', $voucher->expiry_date);
            $expiryDate = $expiryDateFormat->format('d-m-Y');
        }

        return [
            'id' => $voucher->id,
            'member' => $member instanceof Member ? $member->getFullName() : 'Walk in member',
            'location' => $location instanceof Location ? $location->name : 'N/A',
            'voucher_type' => VoucherTypes::getFormattedCaseName($voucherConfiguration->voucher_type),
            'number' => $voucher->number,
            'minimum_spend_amount' => $voucher->minimum_spend_amount,
            'discount' => $voucher->getDiscountValue($voucher->discount_type),
            'discount_type' => $voucher->discount_type,
            'status' => VoucherStatusTypes::getFormattedCaseName($voucher->status),
            'created_at' => $createdAt->format('d-m-Y h:i:s A'),
            'expiry_date' => $expiryDate,
        ];
    }
}
