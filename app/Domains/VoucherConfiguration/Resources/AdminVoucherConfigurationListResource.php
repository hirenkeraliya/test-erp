<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfiguration\Resources;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\VoucherConfiguration\Enums\RestrictedByTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfiguration\Services\VoucherConfigurationService;
use App\Models\VoucherConfiguration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminVoucherConfigurationListResource extends JsonResource
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
        $vouchers = $voucherConfiguration->vouchers;

        $voucherConfigurationService = resolve(VoucherConfigurationService::class);

        [$totalUsedCounts, $totalDiscountAmount] = $voucherConfigurationService::calculateTotalCountsAndAmount(
            $vouchers
        );

        /** @var Carbon $startDateFormat */
        $startDateFormat = Carbon::createFromFormat('Y-m-d', $voucherConfiguration->start_date);
        /** @var Carbon $endDateFormat */
        $endDateFormat = Carbon::createFromFormat('Y-m-d', $voucherConfiguration->end_date);
        $startDate = $startDateFormat->format('d-m-Y');
        $endDate = $endDateFormat->format('d-m-Y');

        return [
            'id' => $voucherConfiguration->id,
            'restricted_by_type' => RestrictedByTypes::getFormattedCaseName(
                $voucherConfiguration->restricted_by_type
            ),
            'voucher_type' => VoucherTypes::getFormattedCaseName($voucherConfiguration->voucher_type),
            'discount_type' => DiscountTypes::getFormattedCaseName($voucherConfiguration->discount_type),
            'get_value' => $voucherConfiguration->get_value,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $voucherConfiguration->status,
            'total_used_counts' => $totalUsedCounts,
            'total_discount_amount' => $totalDiscountAmount,
            'mystery_gift_id' => $voucherConfiguration->mystery_gift_id,
        ];
    }
}
