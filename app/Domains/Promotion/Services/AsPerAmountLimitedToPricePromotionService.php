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

class AsPerAmountLimitedToPricePromotionService implements SalePromotionInterface
{
    public function checkForApplicability(
        CheckSaleDetailsService $checkSaleDetailsService,
        Promotion $promotion,
        array $cartItem,
        Product $product,
        float $itemTotal,
        SaleDiscountService $saleDiscountService,
    ): void {
        $itemDiscountAmount = $this->getDiscountAmount($cartItem, $itemTotal, $promotion);

        if (CommonFunctions::compareFloatNumbers((float) $cartItem['item_discount_amount'], $itemDiscountAmount)) {
            return;
        }

        $saleMismatchMessage = 'Specified discount amount does not match with our calculations. The actual discount amount is ' . $itemDiscountAmount . '. and requested discount amount is ' . $cartItem['item_discount_amount'] . '.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function getDiscountAmount(array $cartItem, float $itemTotal, Promotion $promotion): float
    {
        if ($promotion->discount_type_id === DiscountTypes::PERCENTAGE->value) {
            return $this->getPercentageDiscountAmount($cartItem, $itemTotal, $promotion);
        }

        return $this->getFlatDiscountAmount($cartItem, $itemTotal, $promotion);
    }

    public function getFlatDiscountAmount(array $cartItem, float $itemTotal, Promotion $promotion): float
    {
        $itemPrice = $this->getItemPrice($itemTotal, $cartItem);
        $discountAmount = CommonFunctions::numberFormat($this->getPromotionTierValue($itemPrice, $promotion));

        $discountAmount *= $cartItem['quantity'];
        if ($discountAmount > $itemTotal) {
            return $itemTotal;
        }

        return $discountAmount;
    }

    public function getPercentageDiscountAmount(array $cartItem, float $itemTotal, Promotion $promotion): float
    {
        $itemPrice = $this->getItemPrice($itemTotal, $cartItem);
        $discountPercentage = $this->getPromotionTierValue($itemPrice, $promotion);
        if (0.00 !== $discountPercentage) {
            return CommonFunctions::numberFormat($discountPercentage * $itemTotal / 100);
        }

        return 0.00;
    }

    public function getPromotionTierValue(float $itemPrice, Promotion $promotion): float
    {
        foreach ($promotion->promotionTiers->sortBy('buy_value') as $promotionTier) {
            if ($promotionTier->buy_value > $itemPrice) {
                continue;
            }

            if ($promotionTier->max_value < $itemPrice) {
                continue;
            }

            return (float) $promotionTier->get_value;
        }

        return 0.00;
    }

    public function getItemPrice(float $itemTotal, array $cartItem): float
    {
        return CommonFunctions::numberFormat($itemTotal / $cartItem['quantity']);
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
