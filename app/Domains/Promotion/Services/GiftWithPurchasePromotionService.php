<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Services;

use App\CommonFunctions;
use App\Domains\Promotion\Interfaces\SalePromotionInterface;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Models\Product;
use App\Models\Promotion;

class GiftWithPurchasePromotionService implements SalePromotionInterface
{
    public function checkForApplicability(
        CheckSaleDetailsService $checkSaleDetailsService,
        Promotion $promotion,
        array $cartItem,
        Product $product,
        float $itemTotal,
        SaleDiscountService $saleDiscountService,
    ): void {
        if (! array_key_exists('is_gift_with_purchase', $cartItem)) {
            return;
        }

        if (! $cartItem['is_gift_with_purchase']) {
            return;
        }

        $freeItems = $checkSaleDetailsService->cartItems->where('is_gift_with_purchase', true);
        $promotionFreeItems = $freeItems->whereIn('id', $promotion->regularProducts->pluck('id'))->count();

        if ($freeItems->count() !== $promotionFreeItems) {
            $saleMismatchMessage = 'Some of the specified products do not qualify for the gift with purchase promotion as per our records.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $cartSubTotal = $checkSaleDetailsService->getCartSubtotal();

        $totalFreeQuantities = $this->getPromotionTierValue($cartSubTotal, $promotion);
        if ($totalFreeQuantities < $freeItems->sum('quantity')) {
            $saleMismatchMessage = 'Only ' . $totalFreeQuantities . ' units can be given for this gift with purchase promotion. But requested quantities are ' . $freeItems->sum(
                'quantity'
            ) . '.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        if (
            CommonFunctions::compareFloatNumbers((float) $cartItem['item_discount_amount'], $itemTotal)
        ) {
            return;
        }

        $saleMismatchMessage = 'Specified discount amount does not match with our calculations. The actual discount amount is ' . $itemTotal . '. and requested discount amount is ' . $cartItem['item_discount_amount'] . '.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
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

    public function getItemDiscountAmount(array $cartItem): float
    {
        if (! array_key_exists('item_discount_amount', $cartItem)) {
            return 0.0;
        }

        if (! $cartItem['item_discount_amount']) {
            return 0.0;
        }

        return (float) $cartItem['item_discount_amount'];
    }
}
