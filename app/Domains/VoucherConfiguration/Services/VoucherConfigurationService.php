<?php

declare(strict_types=1);

namespace App\Domains\VoucherConfiguration\Services;

use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\VoucherConfiguration\Enums\RestrictedByTypes;
use App\Domains\VoucherConfiguration\Enums\VouchersTypes;
use App\Domains\VoucherConfiguration\Enums\VoucherTypes;
use Illuminate\Support\Collection;

class VoucherConfigurationService
{
    public static function getVoucherType(?int $restrictedByType, ?int $voucherType, ?int $discountType): string
    {
        if ($voucherType === VoucherTypes::TIER_VOUCHER->value) {
            if ($restrictedByType === RestrictedByTypes::ALL->value) {
                if ($discountType === DiscountTypes::PERCENTAGE->value) {
                    return VouchersTypes::TIER_RESTRICTED_BY_ALL_PERCENTAGE_VOUCHER->name;
                }

                return VouchersTypes::TIER_RESTRICTED_BY_ALL_FLAT_VOUCHER->name;
            }

            if ($restrictedByType === RestrictedByTypes::MEMBER_ONLY->value) {
                if ($discountType === DiscountTypes::PERCENTAGE->value) {
                    return VouchersTypes::TIER_RESTRICTED_BY_MEMBER_PERCENTAGE_VOUCHER->name;
                }

                return VouchersTypes::TIER_RESTRICTED_BY_MEMBER_FLAT_VOUCHER->name;
            }

            if ($restrictedByType === RestrictedByTypes::NON_MEMBER_ONLY->value) {
                if ($discountType === DiscountTypes::PERCENTAGE->value) {
                    return VouchersTypes::TIER_RESTRICTED_BY_NON_MEMBER_PERCENTAGE_VOUCHER->name;
                }

                return VouchersTypes::TIER_RESTRICTED_BY_NON_MEMBER_FLAT_VOUCHER->name;
            }
        }

        if ($voucherType === VoucherTypes::MULTIPLE_VOUCHER->value) {
            if ($restrictedByType === RestrictedByTypes::ALL->value) {
                if ($discountType === DiscountTypes::PERCENTAGE->value) {
                    return VouchersTypes::MULTIPLE_RESTRICTED_BY_ALL_PERCENTAGE_VOUCHER->name;
                }

                return VouchersTypes::MULTIPLE_RESTRICTED_BY_ALL_FLAT_VOUCHER->name;
            }

            if ($restrictedByType === RestrictedByTypes::MEMBER_ONLY->value) {
                if ($discountType === DiscountTypes::PERCENTAGE->value) {
                    return VouchersTypes::MULTIPLE_RESTRICTED_BY_MEMBER_PERCENTAGE_VOUCHER->name;
                }

                return VouchersTypes::MULTIPLE_RESTRICTED_BY_MEMBER_FLAT_VOUCHER->name;
            }

            if ($restrictedByType === RestrictedByTypes::NON_MEMBER_ONLY->value) {
                if ($discountType === DiscountTypes::PERCENTAGE->value) {
                    return VouchersTypes::MULTIPLE_RESTRICTED_BY_NON_MEMBER_PERCENTAGE_VOUCHER->name;
                }

                return VouchersTypes::MULTIPLE_RESTRICTED_BY_NON_MEMBER_FLAT_VOUCHER->name;
            }
        }

        if ($voucherType === VoucherTypes::WELCOME_MEMBER->value) {
            if ($discountType === DiscountTypes::PERCENTAGE->value) {
                return VouchersTypes::WELCOME_MEMBER_RESTRICTED_BY_MEMBER_PERCENTAGE_VOUCHER->name;
            }

            return VouchersTypes::WELCOME_MEMBER_RESTRICTED_BY_MEMBER_FLAT_VOUCHER->name;
        }

        if ($voucherType === VoucherTypes::LOYALTY_POINT->value) {
            if ($discountType === DiscountTypes::PERCENTAGE->value) {
                return VouchersTypes::LOYALTY_POINT_RESTRICTED_BY_MEMBER_PERCENTAGE_VOUCHER->name;
            }

            return VouchersTypes::LOYALTY_POINT_RESTRICTED_BY_MEMBER_FLAT_VOUCHER->name;
        }

        if ($discountType === DiscountTypes::PERCENTAGE->value) {
            return VouchersTypes::BIRTHDAY_RESTRICTED_BY_MEMBER_PERCENTAGE_VOUCHER->name;
        }

        return VouchersTypes::BIRTHDAY_RESTRICTED_BY_MEMBER_FLAT_VOUCHER->name;
    }

    public static function calculateTotalCountsAndAmount(Collection $vouchers): array
    {
        $totalUsedCounts = 0;
        $totalDiscountAmount = 0;

        foreach ($vouchers as $voucher) {
            /** @var Collection $saleDiscounts */
            $saleDiscounts = $voucher->saleDiscounts;
            $totalUsedCounts += $saleDiscounts->count();
            $totalDiscountAmount += $saleDiscounts->sum('amount');
        }

        return [$totalUsedCounts, $totalDiscountAmount];
    }
}
