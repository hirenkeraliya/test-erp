<?php

declare(strict_types=1);

namespace App\Domains\Voucher\Services;

use App\Domains\Voucher\DataObjects\LoyaltyPointVoucherData;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Models\Member;
use App\Models\VoucherConfiguration;

class LoyaltyPointVoucherService
{
    public function checkRequestDetails(
        Member $member,
        VoucherConfiguration $voucherConfiguration,
        LoyaltyPointVoucherData $loyaltyPointVoucherData,
    ): void {
        if ($voucherConfiguration->voucher_type !== VoucherTypes::LOYALTY_POINT->value) {
            abort(412, 'The specified voucher configuration is not Loyalty Point voucher configuration.');
        }

        if ($voucherConfiguration->start_date > now()->format(
            'Y-m-d'
        ) || $voucherConfiguration->end_date < now()->format('Y-m-d')) {
            abort(
                412,
                'The specified voucher configuration is available only between ' . $voucherConfiguration->start_date . ' and ' . $voucherConfiguration->end_date . ' However, the requested date is ' . now()->format(
                    'Y-m-d'
                ) . '.'
            );
        }

        if (! $voucherConfiguration->status) {
            abort(412, 'The specified voucher configuration is not active.');
        }

        if (! $member->membership_id) {
            abort(
                412,
                'The specified voucher configuration can only be used when membership is assigned to the member.'
            );
        }

        if (! $voucherConfiguration->memberships->firstWhere('id', $member->membership_id)) {
            abort(412, 'The member membership is not in over voucher configuration.');
        }

        if ($member->loyalty_points < $loyaltyPointVoucherData->loyalty_points) {
            abort(412, 'The specified loyalty point is more then the member loyalty point.');
        }

        $getValue = $this->getVoucherTierValue($loyaltyPointVoucherData->loyalty_points, $voucherConfiguration);

        if ($getValue > 0) {
            return;
        }

        abort(412, 'The specified voucher configuration is not valid.');
    }

    public function getVoucherTierValue(int $loyaltyPoint, VoucherConfiguration $voucherConfiguration): float
    {
        foreach ($voucherConfiguration->voucherConfigurationTiers->sortByDesc(
            'minimum_spend_amount'
        ) as $voucherConfigurationTier) {
            if ($voucherConfigurationTier->minimum_spend_amount <= $loyaltyPoint) {
                return (float) $voucherConfigurationTier->get_value;
            }
        }

        return 0.00;
    }
}
