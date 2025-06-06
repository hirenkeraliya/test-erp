<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Order\Services\CheckOrderEcommerceDetailsService;
use App\Models\Promotion;

class CartWideAsPerPaymentTypePromotionServiceForEcommerce
{
    public function checkForApplicability(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        Promotion $promotion
    ): void {
        $applicablePaymentAmount = (float) $checkOrderEcommerceDetailsService->orderECommerceData->payment_amount;
        $applicablePaymentAmount += (float) $checkOrderEcommerceDetailsService->orderECommerceData->cart_discount_amount;

        $discountValue = $this->getPromotionTierValue($applicablePaymentAmount, $promotion);

        if (0.0 === $discountValue) {
            $saleMismatchMessage = 'Cart Wide as per payment discount is applied but it is not applicable as per our records. Subtotal: ' . $applicablePaymentAmount;
            CommonFunctions::addMismatchOrAbort(
                $checkOrderEcommerceDetailsService->orderMismatches,
                $saleMismatchMessage
            );
        }

        $isCartDiscountAmountSpecified = null !== $checkOrderEcommerceDetailsService->orderECommerceData->cart_discount_amount;

        $cartDiscountAmountCalculated = $this->getCalculateCartDiscountAmount($applicablePaymentAmount, $promotion);

        $cartDiscountAmountSpecified = (float) $checkOrderEcommerceDetailsService->orderECommerceData->cart_discount_amount;

        if (! $checkOrderEcommerceDetailsService->hasCartPromotion()) {
            return;
        }

        if (! $isCartDiscountAmountSpecified) {
            $saleMismatchMessage = 'Cart discount amount not specified';
            CommonFunctions::addMismatchOrAbort(
                $checkOrderEcommerceDetailsService->orderMismatches,
                $saleMismatchMessage
            );

            return;
        }

        if (
            ! CommonFunctions::compareFloatNumbers($cartDiscountAmountSpecified, $cartDiscountAmountCalculated)
        ) {
            $saleMismatchMessage = 'Cart discount amount does not match with the calculated value. Expected: ' . $cartDiscountAmountCalculated . '. ' .
                'Specified: ' . $cartDiscountAmountSpecified;
            CommonFunctions::addMismatchOrAbort(
                $checkOrderEcommerceDetailsService->orderMismatches,
                $saleMismatchMessage
            );
        }
    }

    public function getCalculateCartDiscountAmount(float $cartSubTotal, Promotion $promotion): float
    {
        if ($promotion->discount_type_id === DiscountTypes::PERCENTAGE->value) {
            return $this->getCartPercentageDiscountAmount($cartSubTotal, $promotion);
        }

        return $this->getCartFlatDiscountAmount($cartSubTotal, $promotion);
    }

    public function getCartFlatDiscountAmount(float $cartSubTotal, Promotion $promotion): float
    {
        return CommonFunctions::numberFormat($this->getPromotionTierValue($cartSubTotal, $promotion));
    }

    public function getCartPercentageDiscountAmount(float $cartSubTotal, Promotion $promotion): float
    {
        $discountPercentage = $this->getPromotionTierValue($cartSubTotal, $promotion);
        if (0.00 !== $discountPercentage) {
            return CommonFunctions::numberFormat($discountPercentage * $cartSubTotal / 100);
        }

        return 0.00;
    }

    public function getPromotionTierValue(float $cartSubTotal, Promotion $promotion): float
    {
        foreach ($promotion->promotionTiers->sortByDesc('buy_value') as $promotionTier) {
            if ($promotionTier->buy_value <= $cartSubTotal) {
                return (float) $promotionTier->get_value;
            }
        }

        return 0.00;
    }

    public function getCartDiscountAmount(CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService): float
    {
        return (float) $checkOrderEcommerceDetailsService->orderECommerceData->cart_discount_amount;
    }
}
