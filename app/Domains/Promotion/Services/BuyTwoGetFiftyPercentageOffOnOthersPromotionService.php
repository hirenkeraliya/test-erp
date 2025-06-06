<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Services;

use App\CommonFunctions;
use App\Domains\Promotion\Interfaces\SalePromotionInterface;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionTier;
use Illuminate\Support\Collection;

class BuyTwoGetFiftyPercentageOffOnOthersPromotionService implements SalePromotionInterface
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
            $saleMismatchMessage = 'group id is required for Buy 2 get 50% off on others promotions but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        if (! $cartItem['group_id']) {
            $saleMismatchMessage = 'group id is required for Buy 2 get 50% off on others promotions but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $discountableItems = $checkSaleDetailsService->cartItems->where('group_id', $cartItem['group_id'])
            ->where('promotion_id', $cartItem['promotion_id'])
            ->where('item_discount_amount', '>', 0);

        $totalDiscountableItems = $discountableItems->whereIn('id', $promotion->getProducts->pluck('id'))->count();

        if ($discountableItems->count() !== $totalDiscountableItems) {
            $saleMismatchMessage = 'Some of the specified discounted products do not match with our records for the Buy 2 get 50% off on others promotion.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $buyItems = $checkSaleDetailsService->cartItems->where('group_id', $cartItem['group_id'])
            ->where('promotion_id', $cartItem['promotion_id'])
            ->where('item_discount_amount', '<=', 0);

        $totalBuyItems = $buyItems->whereIn('id', $promotion->buyProducts->pluck('id'))->count();

        if ($buyItems->count() !== $totalBuyItems) {
            $saleMismatchMessage = 'Some of the specified buy products do not match with our records for the Buy 2 get 50% off on others promotion.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $totalDiscountableQuantities = $discountableItems->sum('quantity');
        $totalBuyQuantity = $buyItems->sum('quantity');
        $applicableDiscountableQuantities = $this->getTotalDiscountableQuantities($totalBuyQuantity, $promotion);

        if ($applicableDiscountableQuantities < $totalDiscountableQuantities) {
            $saleMismatchMessage = 'Only ' . $applicableDiscountableQuantities . ' units can be given for free for this Buy 2 get 50% off on others promotion. But requested free quantities are ' . $totalDiscountableQuantities . '.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $cartBuyItems = $checkSaleDetailsService->cartItems->where('group_id', $cartItem['group_id'])
            ->where('promotion_id', $cartItem['promotion_id'])
            ->where('item_discount_amount', '<=', 0);

        $discountAmount = $this->calculateItemDiscountAmount($promotion, $itemTotal, $cartBuyItems);

        if (CommonFunctions::compareFloatNumbers((float) $cartItem['item_discount_amount'], $discountAmount)) {
            return;
        }

        $saleMismatchMessage = 'Requested discount amount of ' . $cartItem['item_discount_amount'] . ' is more than the applicable discount amount of ' . $discountAmount . '.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function getTotalDiscountableQuantities(
        float $totalBuyQuantity,
        Promotion $promotion,
        float $discountableQuantities = 0
    ): float {
        $promotionTier = $this->getPromotionTierValue($totalBuyQuantity, $promotion);
        if (! $promotionTier instanceof PromotionTier) {
            return $discountableQuantities;
        }

        $totalBuyQuantity -= $promotionTier->buy_value;
        $discountableQuantities += 1;

        return $this->getTotalDiscountableQuantities($totalBuyQuantity, $promotion, $discountableQuantities);
    }

    public function calculateItemDiscountAmount(
        Promotion $promotion,
        float $itemTotal,
        Collection $groupItems,
    ): float {
        $totalBuyQuantity = $groupItems->where('item_discount_amount', '<=', 0)->sum('quantity');

        $promotionTier = $this->getPromotionTierValue($totalBuyQuantity, $promotion);

        if (! $promotionTier instanceof PromotionTier) {
            return 0.00;
        }

        return CommonFunctions::numberFormat($promotionTier->get_value * $itemTotal / 100);
    }

    public function getPromotionTierValue(float $cartQuantity, Promotion $promotion): ?PromotionTier
    {
        foreach ($promotion->promotionTiers->sortByDesc('buy_value') as $promotionTier) {
            if ($promotionTier->buy_value <= $cartQuantity) {
                return $promotionTier;
            }
        }

        return null;
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
