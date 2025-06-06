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

class BuyAnyThreeOrMoreAndGetThirtyPercentageOffPromotionService implements SalePromotionInterface
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
            $saleMismatchMessage = 'group id is required for Buy any 3 or more and get 30% off promotions but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        if (! $cartItem['group_id']) {
            $saleMismatchMessage = 'group id is required for Buy any 3 or more and get 30% off promotions but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $discountableItems = $checkSaleDetailsService->cartItems->where('group_id', $cartItem['group_id'])
            ->where('promotion_id', $cartItem['promotion_id'])
            ->where('item_discount_amount', '>', 0);

        $totalDiscountableItems = $discountableItems->whereIn('id', $promotion->regularProducts->pluck('id'))->count();

        if ($discountableItems->count() !== $totalDiscountableItems) {
            $saleMismatchMessage = 'Some of the specified discountable products do not match with our records for the Buy any 3 or more and get 30% off promotion.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $discountAmount = $this->calculateItemDiscountAmount($promotion, $itemTotal, $discountableItems);
        if (CommonFunctions::compareFloatNumbers((float) $cartItem['item_discount_amount'], $discountAmount)) {
            return;
        }

        $saleMismatchMessage = 'Specified discount amount does not match with our calculations. The actual discount amount is ' . $discountAmount . ' and requested discount amount is ' . $cartItem['item_discount_amount'] . '.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function calculateItemDiscountAmount(
        Promotion $promotion,
        float $itemTotal,
        Collection $groupItems,
    ): float {
        $totalBuyQuantity = $groupItems->where('item_discount_amount', '>', 0)->sum('quantity');

        $discountPercentage = $this->getPromotionTierValue($totalBuyQuantity, $promotion);

        return CommonFunctions::numberFormat($discountPercentage * $itemTotal / 100);
    }

    public function getPromotionTierValue(float $cartQuantity, Promotion $promotion): float
    {
        foreach ($promotion->promotionTiers->sortByDesc('buy_value') as $promotionTier) {
            if ($promotionTier->buy_value <= $cartQuantity) {
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
