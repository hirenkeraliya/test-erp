<?php

declare(strict_types=1);

namespace App\Domains\Promotion\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Promotion\Interfaces\SalePromotionInterface;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleDiscountService;
use App\Models\Product;
use App\Models\Promotion;

class AsPerAmountGetOffOnOthersPromotionService implements SalePromotionInterface
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
            $saleMismatchMessage = 'group id is required for As per amount get off on others promotions but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        if (! $cartItem['group_id']) {
            $saleMismatchMessage = 'group id is required for As per amount get off on others promotions but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        $discountableItems = $checkSaleDetailsService->cartItems->where('group_id', $cartItem['group_id'])
            ->where('promotion_id', $cartItem['promotion_id'])
            ->where('item_discount_amount', '>', 0);

        $totalDiscountableItems = $discountableItems->whereIn('id', $promotion->getProducts->pluck('id'))->count();

        if ($discountableItems->count() !== $totalDiscountableItems) {
            $saleMismatchMessage = 'Some of the specified discounted products do not match with our records for the As per amount get off on others promotion.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $buyItems = $checkSaleDetailsService->cartItems->where('group_id', $cartItem['group_id'])
            ->where('promotion_id', $cartItem['promotion_id'])
            ->where('item_discount_amount', '<=', 0);

        $totalBuyItems = $buyItems->whereIn('id', $promotion->buyProducts->pluck('id'))->count();

        if ($buyItems->count() !== $totalBuyItems) {
            $saleMismatchMessage = 'Some of the specified buy products do not match with our records for the As per amount get off on others promotion.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $totalDiscountableQuantities = $discountableItems->sum('quantity');

        if (1 < $totalDiscountableQuantities) {
            $saleMismatchMessage = 'Only 1 unit can be eligible for a discount for this As per amount get off on others promotion. However, the requested quantity is ' . $totalDiscountableQuantities . '.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $discountableItemsTotal = $saleDiscountService->buyItemsSubtotalWithApplyDreamPriceAndPriceOverride(
            $cartItem
        );

        $itemDiscountAmount = $this->getDiscountAmount($discountableItemsTotal, $itemTotal, $promotion);

        if (CommonFunctions::compareFloatNumbers((float) $cartItem['item_discount_amount'], $itemDiscountAmount)) {
            return;
        }

        $saleMismatchMessage = 'Specified discount amount does not match with our calculations. The actual discount amount is ' . $itemDiscountAmount . '. and requested discount amount is ' . $cartItem['item_discount_amount'] . '.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function getPromotionTierValue(float $discountableItemsTotal, Promotion $promotion): float
    {
        foreach ($promotion->promotionTiers->sortBy('buy_value') as $promotionTier) {
            if ($promotionTier->buy_value > $discountableItemsTotal) {
                continue;
            }

            if ($promotionTier->max_value < $discountableItemsTotal) {
                continue;
            }

            return (float) $promotionTier->get_value;
        }

        return 0.00;
    }

    public function getDiscountAmount(float $discountableItemsTotal, float $itemTotal, Promotion $promotion): float
    {
        if ($promotion->discount_type_id === DiscountTypes::PERCENTAGE->value) {
            return $this->getPercentageDiscountAmount($discountableItemsTotal, $itemTotal, $promotion);
        }

        return $this->getFlatDiscountAmount($discountableItemsTotal, $itemTotal, $promotion);
    }

    public function getFlatDiscountAmount(float $discountableItemsTotal, float $itemTotal, Promotion $promotion): float
    {
        $discountAmount = CommonFunctions::numberFormat(
            $this->getPromotionTierValue($discountableItemsTotal, $promotion)
        );

        if ($discountAmount > $itemTotal) {
            return $itemTotal;
        }

        return $discountAmount;
    }

    public function getPercentageDiscountAmount(
        float $discountableItemsTotal,
        float $itemTotal,
        Promotion $promotion
    ): float {
        $discountPercentage = $this->getPromotionTierValue($discountableItemsTotal, $promotion);
        if (0.00 !== $discountPercentage) {
            return CommonFunctions::numberFormat($discountPercentage * $itemTotal / 100);
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
