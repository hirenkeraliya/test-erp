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

class BundleBuyPromotionService implements SalePromotionInterface
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
            $saleMismatchMessage = 'group id is required for bundle buy promotion but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        if (! $cartItem['group_id']) {
            $saleMismatchMessage = 'group id is required for bundle buy promotion but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        if (! array_key_exists('discount_item_sequence', $cartItem)) {
            $saleMismatchMessage = 'discount item sequence is required for bundle buy promotion but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        if (! $cartItem['discount_item_sequence']) {
            $saleMismatchMessage = 'discount item sequence is required for bundle buy promotion but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $buyItems = $checkSaleDetailsService->cartItems->where('group_id', $cartItem['group_id'])
                ->where('promotion_id', $cartItem['promotion_id'])
                ->where('item_discount_amount', '>', 0);

        $totalBuyItems = $buyItems->whereIn('id', $promotion->regularProducts->pluck('id'))->count();

        if ($buyItems->count() !== $totalBuyItems) {
            $saleMismatchMessage = 'Some of the specified buy products do not match with our records for the bundle buy promotion.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $totalBuyQuantity = $buyItems->sum('quantity');

        $applicableQuantities = $this->getTotalApplicableQuantities($totalBuyQuantity, $promotion);

        if ($totalBuyQuantity > $applicableQuantities) {
            $saleMismatchMessage = 'Only ' . $applicableQuantities . ' units are eligible for the bundle buy promotion. But requested units are ' . $totalBuyQuantity . '.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $applicableItemTotal = $saleDiscountService->groupItemsSubtotalWithApplyDreamPriceAndPriceOverride($cartItem);

        $totalDiscountAmount = $this->getTotalDiscountAmount(
            $promotion,
            $applicableItemTotal,
            $totalBuyQuantity,
            0.00,
        );

        $discountAmount = $this->calculateItemDiscountAmount(
            $cartItem,
            $buyItems,
            $totalDiscountAmount,
            $applicableItemTotal,
            $saleDiscountService,
        );

        if ($discountAmount >= (float) $cartItem['item_discount_amount']) {
            return;
        }

        $saleMismatchMessage = 'Requested discount amount of ' . $cartItem['item_discount_amount'] . ' is more than the applicable discount amount of ' . $discountAmount . '.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function getTotalApplicableQuantities(
        float $totalBuyQuantity,
        Promotion $promotion,
        float $applicableQuantities = 0
    ): float {
        $promotionTier = $this->getPromotionTierValue($totalBuyQuantity, $promotion);

        if (! $promotionTier instanceof PromotionTier) {
            return $applicableQuantities;
        }

        $totalBuyQuantity -= $promotionTier->buy_value;
        $applicableQuantities += $promotionTier->buy_value;

        return $this->getTotalApplicableQuantities($totalBuyQuantity, $promotion, $applicableQuantities);
    }

    public function getTotalDiscountAmount(
        Promotion $promotion,
        float $applicableItemTotal,
        float $totalBuyQuantity,
        float $getQuantity,
    ): float {
        $applicableItemPrice = $this->getTotalGetValue(
            $promotion,
            $applicableItemTotal,
            $totalBuyQuantity,
            $getQuantity,
        );

        return CommonFunctions::numberFormat($applicableItemTotal - $applicableItemPrice);
    }

    public function getTotalGetValue(
        Promotion $promotion,
        float $applicableItemTotal,
        float $totalBuyQuantity,
        float $getQuantity,
    ): float {
        $promotionTier = $this->getPromotionTierValue($totalBuyQuantity, $promotion);

        if (! $promotionTier instanceof PromotionTier) {
            if ($getQuantity <= 0) {
                return $applicableItemTotal;
            }

            return $getQuantity;
        }

        $totalBuyQuantity -= $promotionTier->buy_value;
        $applicableItemTotal = CommonFunctions::numberFormat($applicableItemTotal - $promotionTier->get_value);
        $getQuantity += $promotionTier->get_value;

        return $this->getTotalGetValue($promotion, $applicableItemTotal, $totalBuyQuantity, $getQuantity);
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

    public function calculateItemDiscountAmount(
        array $item,
        Collection $groupItems,
        float $totalDiscount,
        float $groupItemsSubtotal,
        SaleDiscountService $saleDiscountService,
    ): float {
        $cartItems = $groupItems->where('item_discount_amount', '>', 0)->sortBy('discount_item_sequence')->values();
        $lastKey = $cartItems->keys()->last();
        $itemTotalDiscount = 0.00;
        foreach ($cartItems as $cartItemKey => $cartItem) {
            if ($cartItemKey === $lastKey) {
                return CommonFunctions::numberFormat($totalDiscount - $itemTotalDiscount);
            }

            $itemTotal = $saleDiscountService->applyDreamPriceOn($cartItem);
            $itemDiscount = CommonFunctions::numberFormat($itemTotal * $totalDiscount / $groupItemsSubtotal);

            if ($itemDiscount <= 0) {
                $itemDiscount = 0.00;
            }

            $itemTotalDiscount += $itemDiscount;

            if ($this->isDiscountReturn($cartItem, $item)) {
                return $itemDiscount;
            }
        }

        return 0.00;
    }

    public function isDiscountItemSequence(array $cartItem): bool
    {
        if (! array_key_exists('discount_item_sequence', $cartItem)) {
            return false;
        }

        return (bool) $cartItem['discount_item_sequence'];
    }

    public function isDiscountReturn(array $cartItem, array $item): bool
    {
        if ($this->isDiscountItemSequence($item) && $this->isDiscountItemSequence($cartItem)) {
            return $this->matchItemByDiscountItemSequence($cartItem, $item);
        }

        return $cartItem['id'] === $item['id'];
    }

    public function matchItemByDiscountItemSequence(array $cartItem, array $item): bool
    {
        return $cartItem['discount_item_sequence'] === $item['discount_item_sequence'];
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
