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
use Illuminate\Support\Collection;

class AsPerAmountLimitedToBrandsPromotionService implements SalePromotionInterface
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
            $saleMismatchMessage = 'group id is required for as per amount limited to brands promotion but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        if (! $cartItem['group_id']) {
            $saleMismatchMessage = 'group id is required for as per amount limited to brands promotion but not passed.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        $promotionBrand = $promotion->brands->firstWhere('id', $product->brand_id);
        if (! $promotionBrand) {
            $saleMismatchMessage = 'Specified promotion is not applicable on the given product brand ' . $product->name . '.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $discountableItemsTotal = $saleDiscountService->groupItemsSubtotalWithApplyDreamPriceAndPriceOverride(
            $cartItem
        );

        $discountValue = $this->getPromotionTierValue($discountableItemsTotal, $promotion);

        if (0.0 === $discountValue) {
            $saleMismatchMessage = 'As per amount limited to brands promotion is applied but it is not applicable as per our records. Subtotal: ' . $discountableItemsTotal;
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $getItems = $checkSaleDetailsService->cartItems->where('group_id', $cartItem['group_id'])
                ->where('promotion_id', $cartItem['promotion_id'])
                ->where('item_discount_amount', '>', 0);

        $itemDiscountAmount = $this->getDiscountAmount(
            $cartItem,
            $getItems,
            $itemTotal,
            $discountableItemsTotal,
            (float) $cartItem['quantity'],
            $promotion,
            $saleDiscountService
        );

        if (CommonFunctions::compareFloatNumbers((float) $cartItem['item_discount_amount'], $itemDiscountAmount)) {
            return;
        }

        $saleMismatchMessage = 'Specified discount amount does not match with our calculations. The actual discount amount is ' . $itemDiscountAmount . '. and requested discount amount is ' . $cartItem['item_discount_amount'] . '.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function getPromotionTierValue(float $discountableItemsTotal, Promotion $promotion): float
    {
        foreach ($promotion->promotionTiers->sortByDesc('buy_value') as $promotionTier) {
            if ($promotionTier->buy_value <= $discountableItemsTotal) {
                return (float) $promotionTier->get_value;
            }
        }

        return 0.00;
    }

    public function getDiscountAmount(
        array $cartItem,
        Collection $getItems,
        float $itemTotal,
        float $discountableItemsTotal,
        float $quantity,
        Promotion $promotion,
        SaleDiscountService $saleDiscountService
    ): float {
        if ($promotion->discount_type_id === DiscountTypes::PERCENTAGE->value) {
            return $this->getPercentageDiscountAmount($itemTotal, $discountableItemsTotal, $promotion);
        }

        return $this->getFlatDiscountAmount(
            $cartItem,
            $getItems,
            $itemTotal,
            $discountableItemsTotal,
            $quantity,
            $promotion,
            $saleDiscountService
        );
    }

    public function getPercentageDiscountAmount(
        float $itemTotal,
        float $discountableItemsTotal,
        Promotion $promotion
    ): float {
        $discountPercentage = $this->getPromotionTierValue($discountableItemsTotal, $promotion);
        if (0.00 !== $discountPercentage) {
            return CommonFunctions::numberFormat($discountPercentage * $itemTotal / 100);
        }

        return 0.00;
    }

    public function getFlatDiscountAmount(
        array $cartItem,
        Collection $getItems,
        float $itemTotal,
        float $discountableItemsTotal,
        float $quantity,
        Promotion $promotion,
        SaleDiscountService $saleDiscountService
    ): float {
        $totalDiscountAmount = $this->getTotalFlatDiscountAmount(
            $itemTotal,
            $discountableItemsTotal,
            $quantity,
            $promotion
        );

        return $this->calculateFlatItemDiscountAmount(
            $cartItem,
            $getItems,
            $totalDiscountAmount,
            $discountableItemsTotal,
            $saleDiscountService
        );
    }

    public function getTotalFlatDiscountAmount(
        float $itemTotal,
        float $discountableItemsTotal,
        float $quantity,
        Promotion $promotion
    ): float {
        $discountAmount = CommonFunctions::numberFormat(
            $this->getPromotionTierValue($discountableItemsTotal, $promotion)
        );

        if ($discountAmount > $itemTotal) {
            return $itemTotal;
        }

        return $discountAmount;
    }

    public function calculateFlatItemDiscountAmount(
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

    public function isDiscountReturn(array $cartItem, array $item): bool
    {
        if ($this->isDiscountItemSequence($item) && $this->isDiscountItemSequence($cartItem)) {
            return $this->matchItemByDiscountItemSequence($cartItem, $item);
        }

        return $cartItem['id'] === $item['id'];
    }

    public function isDiscountItemSequence(array $cartItem): bool
    {
        if (! array_key_exists('discount_item_sequence', $cartItem)) {
            return false;
        }

        return (bool) $cartItem['discount_item_sequence'];
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
