<?php

declare(strict_types=1);

namespace App\Domains\Company\Services;

use App\CommonFunctions;
use App\Domains\BookingPayment\DataObjects\BookingPaymentData;
use App\Domains\BookingPaymentPayments\DataObjects\BookingPaymentTopUpData;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\Sale\DataObjects\CompleteCreditSaleData;
use App\Domains\Sale\DataObjects\CompleteLayawaySaleData;
use App\Domains\Sale\DataObjects\SaleData;
use App\Models\CompanySetting;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

class CheckCompanySettingService
{
    protected CompanySetting $companySetting;

    public function setDetails(CompanySetting $companySetting): void
    {
        $this->companySetting = $companySetting;
    }

    public function checkBookingPaymentSettings(
        BookingPaymentData|BookingPaymentTopUpData $data,
        Collection $mismatches
    ): void {
        $this->checkAllowMultipleCurrency(
            $data,
            $mismatches,
            $this->companySetting->booking_payment_allow_multi_currency_in_payment,
            'Booking Payment'
        );
    }

    public function checkCompleteCreditSaleSettings(
        CompleteCreditSaleData $saleData,
        Collection $mismatches
    ): void {
        $this->checkUseCashback(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_use_cashback,
            'Credit Sale'
        );

        $this->checkRedeemLoyaltyPoints(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_redeem_loyalty_points,
            'Credit Sale'
        );

        $this->checkEarnLoyaltyPoints(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_earn_loyalty_points,
            'Credit Sale'
        );

        $this->checkGenerateVouchers(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_generate_vouchers,
            'Credit Sale'
        );

        $this->checkAllowMultipleCurrency(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_allow_multi_currency_in_payment,
            'Credit Sale'
        );
    }

    public function checkCompleteLayawaySaleSettings(
        CompleteLayawaySaleData $saleData,
        Collection $mismatches
    ): void {
        $this->checkUseCashback(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_use_cashback,
            'Layaway Sale'
        );

        $this->checkRedeemLoyaltyPoints(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_redeem_loyalty_points,
            'Layaway Sale'
        );

        $this->checkEarnLoyaltyPoints(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_earn_loyalty_points,
            'Layaway Sale'
        );

        $this->checkGenerateVouchers(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_generate_vouchers,
            'Layaway Sale'
        );

        $this->checkAllowMultipleCurrency(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_allow_multi_currency_in_payment,
            'Layaway Sale'
        );
    }

    public function checkCreditSaleSettings(SaleData $saleData, Collection $mismatches): void
    {
        $this->checkUseCashback(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_use_cashback,
            'Credit Sale'
        );

        $this->checkRedeemLoyaltyPoints(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_redeem_loyalty_points,
            'Credit Sale'
        );

        $this->checkEarnLoyaltyPoints(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_earn_loyalty_points,
            'Credit Sale'
        );

        $this->checkGenerateVouchers(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_generate_vouchers,
            'Credit Sale'
        );

        $this->checkAllowMultipleCurrency(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_allow_multi_currency_in_payment,
            'Credit Sale'
        );

        $this->checkRedeemVouchers(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_redeem_vouchers,
            'Credit Sale'
        );

        $this->checkCartWideAutomaticPromotions(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_cart_wide_automatic_promotions,
            'Credit Sale'
        );

        $this->checkCartWideManualPromotions(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_cart_wide_manual_promotions,
            'Credit Sale'
        );

        $this->checkItemWiseAutomaticPromotion(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_item_wise_automatic_promotions,
            'Credit Sale'
        );

        $this->checkItemWiseManualPromotion(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_item_wise_manual_promotions,
            'Credit Sale'
        );

        $this->checkComplimentaryItem(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_complimentary_item,
            'Credit Sale'
        );

        $this->checkManualCartDiscount(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_manual_cart_discount,
            'Credit Sale'
        );

        $this->checkManualItemDiscount(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_manual_item_discount,
            'Credit Sale'
        );

        $this->checkHappyHourDiscount(
            $saleData,
            $mismatches,
            $this->companySetting->credit_sale_happy_hour_discount,
            'Credit Sale'
        );
    }

    public function checkLayawaySaleSettings(SaleData $saleData, Collection $mismatches): void
    {
        $this->checkUseCashback(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_use_cashback,
            'Layaway Sale'
        );

        $this->checkRedeemLoyaltyPoints(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_redeem_loyalty_points,
            'Layaway Sale'
        );

        $this->checkEarnLoyaltyPoints(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_earn_loyalty_points,
            'Layaway Sale'
        );

        $this->checkGenerateVouchers(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_generate_vouchers,
            'Layaway Sale'
        );

        $this->checkAllowMultipleCurrency(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_allow_multi_currency_in_payment,
            'Layaway Sale'
        );

        $this->checkRedeemVouchers(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_redeem_vouchers,
            'Layaway Sale'
        );

        $this->checkCartWideAutomaticPromotions(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_cart_wide_automatic_promotions,
            'Layaway Sale'
        );

        $this->checkCartWideManualPromotions(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_cart_wide_manual_promotions,
            'Layaway Sale'
        );

        $this->checkItemWiseAutomaticPromotion(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_item_wise_automatic_promotions,
            'Layaway Sale'
        );

        $this->checkItemWiseManualPromotion(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_item_wise_manual_promotions,
            'Layaway Sale'
        );

        $this->checkComplimentaryItem(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_complimentary_item,
            'Layaway Sale'
        );

        $this->checkManualCartDiscount(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_manual_cart_discount,
            'Layaway Sale'
        );

        $this->checkManualItemDiscount(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_manual_item_discount,
            'Layaway Sale'
        );

        $this->checkHappyHourDiscount(
            $saleData,
            $mismatches,
            $this->companySetting->layaway_sale_happy_hour_discount,
            'Layaway Sale'
        );
    }

    public function checkUseCashback(
        SaleData|CompleteCreditSaleData|CompleteLayawaySaleData $saleData,
        Collection $mismatches,
        bool $isUseCashback,
        string $usedType,
    ): void {
        if ($isUseCashback) {
            return;
        }

        if (! $this->hasCashback($saleData)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort($mismatches, 'Cashback is not allowed for ' . $usedType);
    }

    public function hasCashback(CompleteCreditSaleData|CompleteLayawaySaleData|SaleData $saleData): bool
    {
        return $saleData->cashback_id && $saleData->cashback_amount > 0.00;
    }

    public function checkRedeemLoyaltyPoints(
        SaleData|CompleteCreditSaleData|CompleteLayawaySaleData $saleData,
        Collection $mismatches,
        bool $isRedeemLoyaltyPoints,
        string $usedType,
    ): void {
        if ($isRedeemLoyaltyPoints) {
            return;
        }

        $this->checkUsedLoyaltyPointsAsPayment($saleData, $mismatches, $usedType);

        if (! $saleData instanceof SaleData) {
            return;
        }

        $this->checkUsedLoyaltyPointsAsCartDiscount($saleData, $mismatches, $usedType);
        $this->checkUsedAsProductLoyaltyPoints($saleData, $mismatches, $usedType);
        $this->checkUsedLoyaltyPointsAsItemDiscount($saleData, $mismatches, $usedType);
    }

    public function checkUsedLoyaltyPointsAsPayment(
        SaleData|CompleteCreditSaleData|CompleteLayawaySaleData $saleData,
        Collection $mismatches,
        string $usedType
    ): void {
        if (! $this->isUsedLoyaltyPointsAsPayment($saleData)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort(
            $mismatches,
            'Use Loyalty Points as payment is not allowed for ' . $usedType
        );
    }

    public function isUsedLoyaltyPointsAsPayment(
        SaleData|CompleteCreditSaleData|CompleteLayawaySaleData $saleData
    ): bool {
        if (null === $saleData->payments) {
            return false;
        }

        if ([] === $saleData->payments) {
            return false;
        }

        $payments = collect($saleData->payments)->where('type_id', StaticPaymentTypes::LOYALTY_POINT->value);

        return ! $payments->isEmpty();
    }

    public function checkUsedLoyaltyPointsAsCartDiscount(
        SaleData $saleData,
        Collection $mismatches,
        string $usedType
    ): void {
        if (! $this->isUsedLoyaltyPointsAsCartDiscount($saleData)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort(
            $mismatches,
            'Use Loyalty Points as cart discount is not allowed for ' . $usedType
        );
    }

    public function isUsedLoyaltyPointsAsCartDiscount(SaleData $saleData): bool
    {
        return $saleData->cart_loyalty_point_amount > 0.00 && $saleData->cart_loyalty_points > 0;
    }

    public function checkUsedAsProductLoyaltyPoints(
        SaleData $saleData,
        Collection $mismatches,
        string $usedType
    ): void {
        if (! $this->isUsedAsProductLoyaltyPoints($saleData)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort(
            $mismatches,
            'Purchase product by Loyalty Points is not allowed for ' . $usedType
        );
    }

    public function isUsedAsProductLoyaltyPoints(SaleData $saleData): bool
    {
        return collect($saleData->items)->pluck('loyalty_points')->filter()->count() > 0;
    }

    public function checkUsedLoyaltyPointsAsItemDiscount(
        SaleData $saleData,
        Collection $mismatches,
        string $usedType
    ): void {
        if (! $this->isUsedLoyaltyPointsAsItemDiscount($saleData)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort(
            $mismatches,
            'Use Loyalty Points as item discount is not allowed for ' . $usedType
        );
    }

    public function isUsedLoyaltyPointsAsItemDiscount(SaleData $saleData): bool
    {
        return collect($saleData->items)->pluck('loyalty_point_item_discount')->filter()->count() > 0;
    }

    public function checkEarnLoyaltyPoints(
        SaleData|CompleteCreditSaleData|CompleteLayawaySaleData $saleData,
        Collection $mismatches,
        bool $isEarnLoyaltyPoints,
        string $usedType,
    ): void {
        if ($isEarnLoyaltyPoints) {
            return;
        }

        if (! $this->hasGenerateLoyaltyPoints($saleData)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort($mismatches, 'Earn Loyalty Points is not allowed for ' . $usedType);
    }

    public function hasGenerateLoyaltyPoints(SaleData|CompleteCreditSaleData|CompleteLayawaySaleData $saleData): bool
    {
        return collect($saleData->loyalty_points)->isNotEmpty();
    }

    public function checkGenerateVouchers(
        SaleData|CompleteCreditSaleData|CompleteLayawaySaleData $saleData,
        Collection $mismatches,
        bool $isGenerateVouchers,
        string $usedType,
    ): void {
        if ($isGenerateVouchers) {
            return;
        }

        if (! $this->hasGenerateVouchers($saleData)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort($mismatches, 'Generate Vouchers is not allowed for ' . $usedType);
    }

    public function hasGenerateVouchers(SaleData|CompleteCreditSaleData|CompleteLayawaySaleData $saleData): bool
    {
        return $saleData->vouchers instanceof DataCollection;
    }

    public function checkAllowMultipleCurrency(
        CompleteCreditSaleData|CompleteLayawaySaleData|SaleData|BookingPaymentData|BookingPaymentTopUpData $saleData,
        Collection $mismatches,
        bool $isAllowMultipleCurrency,
        string $usedType,
    ): void {
        if ($isAllowMultipleCurrency) {
            return;
        }

        if (! $this->isMultipleCurrencyUsed($saleData)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort($mismatches, 'Multiple Currency is not allowed for ' . $usedType);
    }

    public function isMultipleCurrencyUsed(
        CompleteCreditSaleData|CompleteLayawaySaleData|SaleData|BookingPaymentData|BookingPaymentTopUpData $saleData
    ): bool {
        if (null === $saleData->payments) {
            return false;
        }

        if ([] === $saleData->payments) {
            return false;
        }

        return collect($saleData->payments)->pluck('currency_id')->filter()->isNotEmpty();
    }

    public function checkRedeemVouchers(
        SaleData $saleData,
        Collection $mismatches,
        bool $isRedeemVouchers,
        string $usedType,
    ): void {
        if ($isRedeemVouchers) {
            return;
        }

        if (! $this->hasVoucher($saleData)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort($mismatches, 'Redeem Vouchers is not allowed for ' . $usedType);
    }

    public function hasVoucher(SaleData $saleData): bool
    {
        return (bool) $saleData->voucher_number;
    }

    public function checkCartWideAutomaticPromotions(
        SaleData $saleData,
        Collection $mismatches,
        bool $isCartWideAutomaticPromotionUsed,
        string $usedType,
    ): void {
        if ($isCartWideAutomaticPromotionUsed) {
            return;
        }

        if (! $this->isCartWideAutomaticPromotionUsed($saleData)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort(
            $mismatches,
            'Cart Wide Automatic Promotion is not allowed for ' . $usedType
        );
    }

    public function isCartWideAutomaticPromotionUsed(SaleData $saleData): bool
    {
        return (bool) $saleData->cart_promotion_id;
    }

    public function checkCartWideManualPromotions(
        SaleData $saleData,
        Collection $mismatches,
        bool $isCartWideManualPromotionUsed,
        string $usedType,
    ): void {
        if ($isCartWideManualPromotionUsed) {
            return;
        }

        if (! $this->isCartWideManualPromotionUsed($saleData)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort($mismatches, 'Cart Wide Manual Promotion is not allowed for ' . $usedType);
    }

    public function isCartWideManualPromotionUsed(SaleData $saleData): bool
    {
        return null !== $saleData->cart_promo_code;
    }

    public function checkItemWiseAutomaticPromotion(
        SaleData $saleData,
        Collection $mismatches,
        bool $isItemWiseAutomaticPromotionUsed,
        string $usedType,
    ): void {
        if ($isItemWiseAutomaticPromotionUsed) {
            return;
        }

        if (! $this->isItemWiseAutomaticPromotionUsed($saleData)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort(
            $mismatches,
            'Item Wise Automatic Promotion is not allowed for ' . $usedType
        );
    }

    public function isItemWiseAutomaticPromotionUsed(SaleData $saleData): bool
    {
        return collect($saleData->items)->pluck('promotion_id')->filter()->isNotEmpty() && collect(
            $saleData->items
        )->pluck('promo_code')->filter()->isEmpty();
    }

    public function checkItemWiseManualPromotion(
        SaleData $saleData,
        Collection $mismatches,
        bool $isItemWiseManualPromotionUsed,
        string $usedType,
    ): void {
        if ($isItemWiseManualPromotionUsed) {
            return;
        }

        if (! $this->isItemWiseManualPromotionUsed($saleData)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort($mismatches, 'Item Wise Manual Promotion is not allowed for ' . $usedType);
    }

    public function isItemWiseManualPromotionUsed(SaleData $saleData): bool
    {
        return collect($saleData->items)->pluck('promo_code')->filter()->isNotEmpty()
            && collect($saleData->items)->pluck('promotion_id')->filter()->isNotEmpty();
    }

    public function checkComplimentaryItem(
        SaleData $saleData,
        Collection $mismatches,
        bool $isComplimentaryItem,
        string $usedType,
    ): void {
        if ($isComplimentaryItem) {
            return;
        }

        if (! $this->isComplimentaryItem($saleData)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort($mismatches, 'Complimentary Item is not allowed for ' . $usedType);
    }

    public function isComplimentaryItem(SaleData $saleData): bool
    {
        return collect($saleData->items)->pluck('complimentary_item_reason_id')->filter()->isNotEmpty();
    }

    public function checkManualCartDiscount(
        SaleData $saleData,
        Collection $mismatches,
        bool $isManualCartDiscountUsed,
        string $usedType,
    ): void {
        if ($isManualCartDiscountUsed) {
            return;
        }

        if (! $this->isManualCartDiscountUsed($saleData)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort($mismatches, 'Manual Cart Discount is not allowed for ' . $usedType);
    }

    public function isManualCartDiscountUsed(SaleData $saleData): bool
    {
        if (CommonFunctions::compareFloatNumbers($saleData->cart_price_override_amount ?? 0.00, 0.00)) {
            return false;
        }

        if ($this->hasStoreManagerPriceOverrideForCart($saleData)) {
            return true;
        }

        if ($this->hasDirectorPriceOverrideForCart($saleData)) {
            return true;
        }

        return $this->hasCashierPriceOverrideForCart($saleData);
    }

    public function hasStoreManagerPriceOverrideForCart(SaleData $saleData): bool
    {
        return null !== $saleData->store_manager_id && null !== $saleData->store_manager_passcode && null !== $saleData->cart_price_override_amount;
    }

    public function hasDirectorPriceOverrideForCart(SaleData $saleData): bool
    {
        return null !== $saleData->director_id && null !== $saleData->director_passcode && null !== $saleData->cart_price_override_amount;
    }

    public function hasCashierPriceOverrideForCart(SaleData $saleData): bool
    {
        return null !== $saleData->cashier_id && null !== $saleData->cart_price_override_amount;
    }

    public function checkManualItemDiscount(
        SaleData $saleData,
        Collection $mismatches,
        bool $isManualItemDiscountUsed,
        string $usedType,
    ): void {
        if ($isManualItemDiscountUsed) {
            return;
        }

        if (! $this->isManualItemDiscountUsed($saleData)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort($mismatches, 'Manual Item Discount is not allowed for ' . $usedType);
    }

    public function isManualItemDiscountUsed(SaleData $saleData): bool
    {
        return collect($saleData->items)->pluck('price_override_amount')->filter()->isNotEmpty();
    }

    public function checkHappyHourDiscount(
        SaleData $saleData,
        Collection $mismatches,
        bool $isHappyHourDiscountUsed,
        string $usedType,
    ): void {
        if ($isHappyHourDiscountUsed) {
            return;
        }

        if (! $this->isHappyHourDiscountUsed($saleData)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort($mismatches, 'Happy Hour Discount is not allowed for ' . $usedType);
    }

    public function isHappyHourDiscountUsed(SaleData $saleData): bool
    {
        return collect($saleData->items)->pluck('happy_hours_discount_amount')->filter()->isNotEmpty();
    }
}
