<?php

declare(strict_types=1);

namespace App\Domains\Voucher\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\VoucherConfiguration\Enums\ExcludeByTypes;
use App\Models\Voucher;
use App\Models\VoucherConfiguration;
use Carbon\Carbon;

class VoucherDiscountService
{
    public function checkForApplicability(
        CheckSaleDetailsService $checkSaleDetailsService,
        ?Voucher $voucher,
        float $cartSubtotal
    ): void {
        if (! $voucher instanceof Voucher) {
            abort(412, 'Specified voucher is not available in our records.');
        }

        $memberId = $checkSaleDetailsService->saleUserService->getExistingMemberId();

        if ($voucher->member_id && $memberId !== $voucher->member_id) {
            $saleMismatchMessage = 'The specified voucher belongs to another member.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        if ($voucher->used_at) {
            $saleMismatchMessage = 'The specified voucher has already been used.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $checkSaleDetailsService->saleData->happened_at
        );
        $happenedAt = $happenedAtFormat->format('Y-m-d');

        if ($voucher->expiry_date && $voucher->expiry_date < $happenedAt) {
            $saleMismatchMessage = 'The specified voucher has expired.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        if ($cartSubtotal < $voucher->minimum_spend_amount) {
            $saleMismatchMessage = 'The specified voucher is not applicable because the member needs to spend a minimum amount ' . $voucher->minimum_spend_amount . ' but cart total is ' . $cartSubtotal . '.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $this->checkVoucherRestrictions($checkSaleDetailsService, $voucher);

        $amountToExclude = $this->getExcludeAmountForVoucher($checkSaleDetailsService, $voucher);
        $cartSubtotalAfterExclude = $cartSubtotal - $amountToExclude;

        $discountAmount = $this->getCalculateDiscountAmount($cartSubtotalAfterExclude, $voucher);

        if (! $checkSaleDetailsService->saleData->voucher_discount_amount) {
            return;
        }

        if (CommonFunctions::compareFloatNumbers(
            $discountAmount,
            $checkSaleDetailsService->saleData->voucher_discount_amount
        )) {
            return;
        }

        $saleMismatchMessage = 'The specified voucher discount amount does not match our calculations. The actual discount amount is ' . $discountAmount . ' and requested discount amount is ' . $checkSaleDetailsService->saleData->voucher_discount_amount . '.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkVoucherRestrictions(
        CheckSaleDetailsService $checkSaleDetailsService,
        Voucher $voucher
    ): void {
        $this->checkDreamPriceRestrictions($checkSaleDetailsService, $voucher);
        $this->checkItemWisePromotionRestrictions($checkSaleDetailsService, $voucher);
        $this->checkCartWidePromotionRestrictions($checkSaleDetailsService, $voucher);
    }

    public function checkDreamPriceRestrictions(
        CheckSaleDetailsService $checkSaleDetailsService,
        Voucher $voucher
    ): void {
        if ($voucher->dream_price_applicable) {
            return;
        }

        foreach ($checkSaleDetailsService->cartItems as $cartItem) {
            if ($checkSaleDetailsService->hasDreamPrice($cartItem)) {
                $saleMismatchMessage = 'Specified Voucher cannot be applied with the dream price';
                CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

                return;
            }
        }
    }

    public function checkItemWisePromotionRestrictions(
        CheckSaleDetailsService $checkSaleDetailsService,
        Voucher $voucher
    ): void {
        if ($voucher->item_wise_promotion_applicable) {
            return;
        }

        foreach ($checkSaleDetailsService->cartItems as $cartItem) {
            if ($checkSaleDetailsService->hasItemPromotion($cartItem)) {
                $saleMismatchMessage = 'Specified Voucher cannot be applied with the Item Wise Promotion';
                CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

                return;
            }
        }
    }

    public function checkCartWidePromotionRestrictions(
        CheckSaleDetailsService $checkSaleDetailsService,
        Voucher $voucher
    ): void {
        if ($voucher->cart_wide_promotion_applicable) {
            return;
        }

        if (! $checkSaleDetailsService->hasCartPromotion()) {
            return;
        }

        $saleMismatchMessage = 'Specified Voucher cannot be applied with the Cart Wide Promotion';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
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
        CheckSaleDetailsService $checkSaleDetailsService,
        Voucher $voucher
    ): int|float {
        $amountToExclude = 0;

        $cartItems = $checkSaleDetailsService->cartItems;

        /** @var VoucherConfiguration $voucherConfiguration */
        $voucherConfiguration = $voucher->voucherConfiguration;

        if ($voucherConfiguration->exclude_by_type === ExcludeByTypes::CATEGORIES->value) {
            foreach ($cartItems as $cartItem) {
                $product = $checkSaleDetailsService->products->firstWhere('id', $cartItem['id']);
                $cartItemCategoryIds = $product->categories->pluck('id');
                $voucherExcludeCategoryIds = $voucherConfiguration->categories->pluck('id');

                $isValidProductAccordingToCategories = $voucherExcludeCategoryIds->intersect($cartItemCategoryIds);

                if ($isValidProductAccordingToCategories->isNotEmpty()) {
                    $amountToExclude += $cartItem['price'] * $cartItem['quantity'];
                }
            }

            return $amountToExclude;
        }

        if ($voucherConfiguration->exclude_by_type === ExcludeByTypes::PRODUCTS->value) {
            $voucherExcludeProductIds = $voucherConfiguration->products->pluck('id');

            foreach ($cartItems as $cartItem) {
                if ($voucherExcludeProductIds->contains($cartItem['id'])) {
                    $amountToExclude += $cartItem['price'] * $cartItem['quantity'];
                }
            }

            return $amountToExclude;
        }

        return $amountToExclude;
    }

    public function getDiscountAmount(CheckSaleDetailsService $checkSaleDetailsService): float
    {
        return (float) $checkSaleDetailsService->saleData->voucher_discount_amount;
    }
}
