<?php

declare(strict_types=1);

namespace App\Domains\Voucher\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Order\Services\CheckOrderEcommerceDetailsService;
use App\Domains\VoucherConfiguration\Enums\ExcludeByTypes;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Carbon\Carbon;

class VoucherEcommerceDiscountService
{
    public function checkForApplicability(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        ?Voucher $voucher,
        float $cartSubtotal
    ): void {
        if (! $voucher instanceof Voucher) {
            abort(412, 'Specified voucher is not available in our records.');
        }

        $memberId = $checkOrderEcommerceDetailsService->member?->id;

        if ($voucher->member_id && $memberId !== $voucher->member_id) {
            $saleMismatchMessage = 'The specified voucher belongs to another member.';
            CommonFunctions::addMismatchOrAbort(
                $checkOrderEcommerceDetailsService->orderMismatches,
                $saleMismatchMessage
            );
        }

        if ($voucher->used_at) {
            $saleMismatchMessage = 'The specified voucher has already been used.';
            CommonFunctions::addMismatchOrAbort(
                $checkOrderEcommerceDetailsService->orderMismatches,
                $saleMismatchMessage
            );
        }

        $happenedAtFormat = now();
        if ($checkOrderEcommerceDetailsService->orderECommerceData->happened_at) {
            /** @var Carbon $happenedAtFormat */
            $happenedAtFormat = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $checkOrderEcommerceDetailsService->orderECommerceData->happened_at
            );
        }

        $happenedAt = $happenedAtFormat->format('Y-m-d');

        if ($voucher->expiry_date && $voucher->expiry_date < $happenedAt) {
            $saleMismatchMessage = 'The specified voucher has expired.';
            CommonFunctions::addMismatchOrAbort(
                $checkOrderEcommerceDetailsService->orderMismatches,
                $saleMismatchMessage
            );
        }

        if ($cartSubtotal < $voucher->minimum_spend_amount) {
            $saleMismatchMessage = 'The specified voucher is not applicable because the member needs to spend a minimum amount ' . $voucher->minimum_spend_amount . ' but cart total is ' . $cartSubtotal . '.';
            CommonFunctions::addMismatchOrAbort(
                $checkOrderEcommerceDetailsService->orderMismatches,
                $saleMismatchMessage
            );
        }

        $amountToExclude = $this->getExcludeAmountForVoucher($checkOrderEcommerceDetailsService, $voucher);
        $cartSubtotalAfterExclude = $cartSubtotal - $amountToExclude;

        $discountAmount = $this->getCalculateDiscountAmount($cartSubtotalAfterExclude, $voucher);

        if (! $checkOrderEcommerceDetailsService->orderECommerceData->voucher_discount_amount) {
            return;
        }

        if (CommonFunctions::compareFloatNumbers(
            $discountAmount,
            $checkOrderEcommerceDetailsService->orderECommerceData->voucher_discount_amount
        )) {
            return;
        }

        $saleMismatchMessage = 'The specified voucher discount amount does not match our calculations. The actual discount amount is ' . $discountAmount . ' and requested discount amount is ' . $checkOrderEcommerceDetailsService->orderECommerceData->voucher_discount_amount . '.';
        CommonFunctions::addMismatchOrAbort($checkOrderEcommerceDetailsService->orderMismatches, $saleMismatchMessage);
    }

    public function getCalculateDiscountAmount(float $cartSubtotal, Voucher $voucher): float
    {
        if ($voucher->discount_type === DiscountTypes::FLAT->value) {
            if ($voucher->flat_amount > $cartSubtotal) {
                return CommonFunctions::numberFormat($cartSubtotal);
            }

            return CommonFunctions::numberFormat((float) $voucher->flat_amount);
        }

        return CommonFunctions::numberFormat($voucher->percentage * $cartSubtotal / 100);
    }

    public function getExcludeAmountForVoucher(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        Voucher $voucher
    ): int|float {
        $amountToExclude = 0;

        $orderItems = $checkOrderEcommerceDetailsService->orderItems;

        /** @var VoucherConfiguration $voucherConfiguration */
        $voucherConfiguration = $voucher->voucherConfiguration;

        if ($voucherConfiguration->exclude_by_type === ExcludeByTypes::CATEGORIES->value) {
            foreach ($orderItems as $orderItem) {
                $product = $checkOrderEcommerceDetailsService->products->firstWhere('id', $orderItem['id']);
                $orderItemCategoryIds = $product->categories->pluck('id');
                $voucherExcludeCategoryIds = $voucherConfiguration->categories->pluck('id');

                $isValidProductAccordingToCategories = $voucherExcludeCategoryIds->intersect($orderItemCategoryIds);

                if ($isValidProductAccordingToCategories->isNotEmpty()) {
                    $amountToExclude += $orderItem['price'] * $orderItem['quantity'];
                }
            }

            return $amountToExclude;
        }

        if ($voucherConfiguration->exclude_by_type === ExcludeByTypes::PRODUCTS->value) {
            $voucherExcludeProductIds = $voucherConfiguration->products->pluck('id');

            foreach ($orderItems as $orderItem) {
                if ($voucherExcludeProductIds->contains($orderItem['id'])) {
                    $amountToExclude += $orderItem['price'] * $orderItem['quantity'];
                }
            }

            return $amountToExclude;
        }

        return $amountToExclude;
    }

    public function getDiscountAmount(CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService): float
    {
        return (float) $checkOrderEcommerceDetailsService->orderECommerceData->voucher_discount_amount;
    }
}
