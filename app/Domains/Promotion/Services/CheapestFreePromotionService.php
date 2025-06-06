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

class CheapestFreePromotionService implements SalePromotionInterface
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
            $saleMismatchMessage = 'group id is required for cheapest free promotion but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        if (! $cartItem['group_id']) {
            $saleMismatchMessage = 'group id is required for cheapest free promotion but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $freeItems = $checkSaleDetailsService->cartItems->where('group_id', $cartItem['group_id'])
            ->where('promotion_id', $cartItem['promotion_id'])
            ->where('item_discount_amount', '>', 0);

        $totalFreeItems = $freeItems->whereIn('id', $promotion->regularProducts->pluck('id'))->count();

        if ($freeItems->count() !== $totalFreeItems) {
            $saleMismatchMessage = 'Some of the specified free products do not qualify for the cheapest free promotion as per our records.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $buyItems = $checkSaleDetailsService->cartItems->where('group_id', $cartItem['group_id'])
            ->where('promotion_id', $cartItem['promotion_id'])
            ->where('item_discount_amount', '<=', 0);

        $totalBuyItems = $buyItems->whereIn('id', $promotion->regularProducts->pluck('id'))->count();

        if ($buyItems->count() !== $totalBuyItems) {
            $saleMismatchMessage = 'Some of the specified buy products do not match with our records for the cheapest free promotion.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $totalFreeQuantities = $freeItems->sum('quantity');
        $totalApplicableQuantity = $buyItems->sum('quantity') + $totalFreeQuantities;
        $applicableFreeQuantities = $this->getTotalFreeQuantities($totalApplicableQuantity, $promotion);

        if ($totalFreeQuantities > $applicableFreeQuantities) {
            $saleMismatchMessage = 'Only ' . $applicableFreeQuantities . ' units can be given for free for this cheapest free promotion. But requested free quantities are ' . $totalFreeQuantities . '.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $totalCheapestFreeItem = $this->getTotalCheapestFree(
            $checkSaleDetailsService->cartItems,
            (int) $cartItem['group_id'],
            (int) $cartItem['promotion_id']
        );

        if ($freeItems->count() === $totalCheapestFreeItem) {
            return;
        }

        $saleMismatchMessage = 'Some of the free items are not the cheapest from the list.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function getTotalCheapestFree(Collection $cartItems, int $groupId, int $promotionId): int
    {
        $cartItems = $cartItems->where('group_id', $groupId)
            ->where('promotion_id', $promotionId)
            ->sortBy([['price', 'asc'], ['item_discount_amount', 'desc']]);

        $totalCheapestFreeItem = 0;
        foreach ($cartItems as $cartItem) {
            if (! array_key_exists('item_discount_amount', $cartItem)) {
                return $totalCheapestFreeItem;
            }

            if ($cartItem['item_discount_amount'] <= 0) {
                return $totalCheapestFreeItem;
            }

            $totalCheapestFreeItem += 1;
        }

        return $totalCheapestFreeItem;
    }

    public function getTotalFreeQuantities(
        float $totalBuyQuantity,
        Promotion $promotion,
        float $freeQuantities = 0
    ): float {
        $promotionTier = $this->getPromotionTierValue($totalBuyQuantity, $promotion);
        if (! $promotionTier instanceof PromotionTier) {
            return $freeQuantities;
        }

        $totalBuyQuantity -= $promotionTier->buy_value;
        $freeQuantities += $promotionTier->get_value;

        return $this->getTotalFreeQuantities($totalBuyQuantity, $promotion, $freeQuantities);
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
