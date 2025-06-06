<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Order\Services\CheckOrderEcommerceDetailsService;
use App\Models\Promotion;

class CartWideAsPerAmountPromotionServiceForEcommerce
{
    public function checkForApplicability(
        CheckOrderEcommerceDetailsService $checkOrderEcommerceDetailsService,
        float $subtotal,
        Promotion $promotion
    ): void {
        $discountValue = $this->getPromotionTierValue($subtotal, $promotion);

        if (0.0 === $discountValue) {
            $orderMismatchMessage = 'Cart Wide automatic discount is applied but it is not applicable as per our records. Subtotal: ' . $subtotal;
            CommonFunctions::addMismatchOrAbort(
                $checkOrderEcommerceDetailsService->orderMismatches,
                $orderMismatchMessage
            );
        }

        $isCartDiscountAmountSpecified = null !== $checkOrderEcommerceDetailsService->orderECommerceData->cart_discount_amount;

        $cartDiscountAmountCalculated = $this->getCalculateCartDiscountAmount($subtotal, $promotion);

        $cartDiscountAmountSpecified = (float) $checkOrderEcommerceDetailsService->orderECommerceData->cart_discount_amount;

        if ($checkOrderEcommerceDetailsService->hasCartPromotion()) {
            if (! $isCartDiscountAmountSpecified) {
                $orderMismatchMessage = 'Cart discount amount not specified';
                CommonFunctions::addMismatchOrAbort(
                    $checkOrderEcommerceDetailsService->orderMismatches,
                    $orderMismatchMessage
                );

                return;
            }

            if (
                ! CommonFunctions::compareFloatNumbers($cartDiscountAmountSpecified, $cartDiscountAmountCalculated)
            ) {
                $orderMismatchMessage = 'Cart discount amount does not match with the calculated value. Expected: ' . $cartDiscountAmountCalculated . '. ' .
                    'Specified: ' . $cartDiscountAmountSpecified;
                CommonFunctions::addMismatchOrAbort(
                    $checkOrderEcommerceDetailsService->orderMismatches,
                    $orderMismatchMessage
                );
            }
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
