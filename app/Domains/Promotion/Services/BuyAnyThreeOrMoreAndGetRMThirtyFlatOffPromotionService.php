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

class BuyAnyThreeOrMoreAndGetRMThirtyFlatOffPromotionService implements SalePromotionInterface
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
            $saleMismatchMessage = 'group id is required for Buy any 3 or more and get RM30 off promotions but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        if (! $cartItem['group_id']) {
            $saleMismatchMessage = 'group id is required for Buy any 3 or more and get RM30 off promotions but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $discountableItems = $checkSaleDetailsService->cartItems->where('group_id', $cartItem['group_id'])
            ->where('promotion_id', $cartItem['promotion_id'])
            ->where('item_discount_amount', '>', 0);

        $totalDiscountableItems = $discountableItems->whereIn('id', $promotion->regularProducts->pluck('id'))->count();

        if ($discountableItems->count() !== $totalDiscountableItems) {
            $saleMismatchMessage = 'Some of the specified discountable products do not match with our records for the Buy any 3 or more and get RM30 off promotion.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $buyItems = $checkSaleDetailsService->cartItems->where('group_id', $cartItem['group_id'])
            ->where('promotion_id', $cartItem['promotion_id'])
            ->where('item_discount_amount', '>', 0);

        $applicableItemTotal = $saleDiscountService->groupItemsSubtotalWithApplyDreamPriceAndPriceOverride($cartItem);

        $totalGetQuantity = $buyItems->sum('quantity');

        $totalDiscountAmount = $this->getTotalDiscountAmount($promotion, $totalGetQuantity);

        $discountAmount = $this->calculateItemDiscountAmount(
            $cartItem,
            $buyItems,
            $totalDiscountAmount,
            $applicableItemTotal,
            $saleDiscountService,
        );

        if (CommonFunctions::compareFloatNumbers((float) $cartItem['item_discount_amount'], $discountAmount)) {
            return;
        }

        $saleMismatchMessage = 'Specified discount amount does not match with our calculations. The actual discount amount is ' . $discountAmount . ' and requested discount amount is ' . $cartItem['item_discount_amount'] . '.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
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

    public function getTotalDiscountAmount(Promotion $promotion, float $getQuantity): float
    {
        $discount = $this->getPromotionTierValue($getQuantity, $promotion);

        return CommonFunctions::numberFormat($discount);
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
