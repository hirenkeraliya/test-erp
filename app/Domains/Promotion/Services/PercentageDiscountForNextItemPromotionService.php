<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Services;

use App\CommonFunctions;
use App\Domains\Promotion\Interfaces\SalePromotionInterface;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Support\Collection;

class PercentageDiscountForNextItemPromotionService implements SalePromotionInterface
{
    public function checkForApplicability(
        CheckSaleDetailsService $checkSaleDetailsService,
        Promotion $promotion,
        array $cartItem,
        Product $product,
        float $itemTotal,
        SaleDiscountService $saleDiscountService,
    ): void {
        if (! array_key_exists('group_id', $cartItem)) {
            $saleMismatchMessage = 'group id is required for Percentage Discount For Next Item promotions but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        if (! $cartItem['group_id']) {
            $saleMismatchMessage = 'group id is required for Percentage Discount For Next Item promotions but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        if (! array_key_exists('discount_item_sequence', $cartItem)) {
            $saleMismatchMessage = 'discount item sequence is required for Percentage Discount For Next Item promotions but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        if (! $cartItem['discount_item_sequence']) {
            $saleMismatchMessage = 'discount item sequence is required for Percentage Discount For Next Item promotions but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $discountableItems = $checkSaleDetailsService->cartItems->where('group_id', $cartItem['group_id'])
            ->where('promotion_id', $cartItem['promotion_id']);

        $totalDiscountableItems = $discountableItems->whereIn('id', $promotion->regularProducts->pluck('id'))->count();

        if ($discountableItems->count() !== $totalDiscountableItems) {
            $saleMismatchMessage = 'Some of the specified discountable products do not match with our records for the Percentage Discount For Next Item promotion.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $discountAmount = $this->calculateItemDiscountAmount(
            $promotion,
            $cartItem,
            $itemTotal,
            $discountableItems,
            0.00
        );

        if (CommonFunctions::compareFloatNumbers((float) $cartItem['item_discount_amount'], $discountAmount)) {
            return;
        }

        $saleMismatchMessage = 'Specified discount amount does not match with our calculations. The actual discount amount is ' . $discountAmount . ' and requested discount amount is ' . $cartItem['item_discount_amount'] . '.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function calculateItemDiscountAmount(
        Promotion $promotion,
        array $cartItem,
        float $itemTotal,
        Collection $groupItems,
        float $totalDiscount
    ): float {
        if (! array_key_exists('discount_item_sequence', $cartItem)) {
            return (float) $cartItem['item_discount_amount'];
        }

        $totalBuyQuantity = $groupItems
            ->where('discount_item_sequence', '<=', $cartItem['discount_item_sequence'])
            ->sum('quantity');

        $totalDiscount = 0.00;
        $promotionTiers = $this->getPromotionTier($totalBuyQuantity, $promotion);

        foreach ($promotionTiers as $promotionTier) {
            $discount = CommonFunctions::numberFormat($promotionTier->get_value * $itemTotal / 100);
            $itemTotal -= $discount;
            $totalDiscount += $discount;
        }

        return $totalDiscount;
    }

    public function getPromotionTier(float $cartQuantity, Promotion $promotion): Collection
    {
        return $promotion->promotionTiers
            ->sortByDesc('buy_value')
            ->where('buy_value', '<=', $cartQuantity)
            ->sortBy('buy_value');
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
