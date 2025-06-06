<?php

declare(strict_types=1);

namespace App\Domains\MysteryGift\Services;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\VoucherConfiguration\DataObjects\VoucherConfigurationData;
use App\Domains\VoucherConfiguration\Enums\ExcludeByTypes;
use App\Domains\VoucherConfiguration\Enums\RestrictedByTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use App\Domains\VoucherConfiguration\VoucherConfigurationQueries;
use App\Models\Admin;
use App\Models\MysteryGift;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MysteryGiftService
{
    public function generateVoucherForMysteryGift(MysteryGift $mysteryGift, Admin $user): void
    {
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);
        $voucherConfigurations = $voucherConfigurationQueries->checkVoucherExistForMysteryGift($mysteryGift->id);

        $startDate = Carbon::parse($mysteryGift->start_date);
        $endDate = Carbon::parse($mysteryGift->end_date);

        $validityDays = $startDate->diffInDays($endDate);

        $title = 'System Generated Mystery gift voucher get upto ' . DiscountTypes::getFormattedCaseName(
            DiscountTypes::FLAT->value
        ) . ' ' . $mysteryGift->max_flat_amount . ' off';

        $voucherConfigurationData = new VoucherConfigurationData(
            RestrictedByTypes::MEMBER_ONLY->value,
            VoucherTypes::TIER_VOUCHER->value,
            ExcludeByTypes::NONE->value,
            0.0,
            (float) $mysteryGift->minimum_spend_amount_for_flat_amount,
            $validityDays,
            DiscountTypes::FLAT->value,
            null,
            $mysteryGift->start_date,
            $mysteryGift->end_date,
            null,
            null,
            [
                [
                    'minimum_spend_amount' => (float) $mysteryGift->minimum_spend_amount_for_flat_amount,
                    'maximum_spend_amount' => ((float) $mysteryGift->minimum_spend_amount_for_flat_amount + 100),
                    'get_value' => $mysteryGift->max_flat_amount,
                ],
            ],
            true,
            true,
            true,
            null,
            null,
            null,
            $title,
            null,
            null,
            null,
            null,
            $mysteryGift->id
        );

        $this->addOrUpdateFlatVoucher($mysteryGift, $voucherConfigurationData, $user, $voucherConfigurations);

        $this->addOrUpdatePercentageVoucher(
            $mysteryGift,
            $voucherConfigurationData,
            $user,
            $voucherConfigurations
        );
    }

    public function addOrUpdateFlatVoucher(
        MysteryGift $mysteryGift,
        VoucherConfigurationData $voucherConfigurationData,
        Admin $user,
        Collection $voucherConfigurations
    ): void {
        $voucherConfiguration = $voucherConfigurations->firstWhere('discount_type', DiscountTypes::FLAT->value);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);

        if ($voucherConfiguration && ! $mysteryGift->is_flat_amount) {
            $voucherConfigurationQueries->inactiveVoucherConfiguration($voucherConfiguration);

            return;
        }

        if (! $mysteryGift->is_flat_amount) {
            return;
        }

        if ($voucherConfiguration) {
            $voucherConfigurationQueries->update(
                $voucherConfigurationData,
                $voucherConfiguration->id,
                $mysteryGift->company_id,
                true
            );

            return;
        }

        $voucherConfigurationQueries->addNew($voucherConfigurationData, $mysteryGift->company_id, $user);
    }

    public function addOrUpdatePercentageVoucher(
        MysteryGift $mysteryGift,
        VoucherConfigurationData $voucherConfigurationData,
        Admin $user,
        Collection $voucherConfigurations
    ): void {
        $voucherConfiguration = $voucherConfigurations->firstWhere('discount_type', DiscountTypes::PERCENTAGE->value);
        $voucherConfigurationQueries = resolve(VoucherConfigurationQueries::class);

        if ($voucherConfiguration && ! $mysteryGift->is_percentage) {
            $voucherConfigurationQueries->inactiveVoucherConfiguration($voucherConfiguration);

            return;
        }

        if (! $mysteryGift->is_percentage) {
            return;
        }

        $voucherConfigurationData->title = 'System Generated Mystery gift voucher get upto ' . $mysteryGift->max_percentage . '% off';
        $voucherConfigurationData->discount_type = DiscountTypes::PERCENTAGE->value;
        $voucherConfigurationData->use_minimum_spend_amount = (float) $mysteryGift->minimum_spend_amount_for_percentage;

        $voucherConfigurationData->tiers = [
            [
                'minimum_spend_amount' => (float) $mysteryGift->minimum_spend_amount_for_percentage,
                'maximum_spend_amount' => ((float) $mysteryGift->minimum_spend_amount_for_percentage + 100),
                'get_value' => $mysteryGift->max_percentage,
            ],
        ];

        if ($voucherConfiguration) {
            $voucherConfigurationQueries->update(
                $voucherConfigurationData,
                $voucherConfiguration->id,
                $mysteryGift->company_id,
                true
            );

            return;
        }

        $voucherConfigurationQueries->addNew($voucherConfigurationData, $mysteryGift->company_id, $user);
    }
}
