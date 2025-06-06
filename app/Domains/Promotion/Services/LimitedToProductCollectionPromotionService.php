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

class LimitedToProductCollectionPromotionService implements SalePromotionInterface
{
    public function checkForApplicability(
        CheckSaleDetailsService $checkSaleDetailsService,
        Promotion $promotion,
        array $cartItem,
        Product $product,
        float $itemTotal,
        SaleDiscountService $saleDiscountService,
    ): void {
        $productProductCollectionIds = $product->productCollectionProducts->pluck('product_collection_id');
        $promotionProductCollectionIds = $promotion->productCollections->pluck('id');

        $isValidProductAccordingToProductCollection = $promotionProductCollectionIds->intersect(
            $productProductCollectionIds
        );

        if ($isValidProductAccordingToProductCollection->isEmpty()) {
            $saleMismatchMessage = 'Specified promotion is not applicable on the given product collections ' . $product->name . '.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $itemDiscountAmount = $this->calculateItemDiscountAmount($promotion, $cartItem, $itemTotal);

        if ((float) $cartItem['item_discount_amount'] !== $itemDiscountAmount) {
            $saleMismatchMessage = 'Requested discount amount of ' . $cartItem['item_discount_amount'] . ' does not match with our calculations. The calculated discount amount is ' . $itemDiscountAmount;
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }
    }

    public function calculateItemDiscountAmount(Promotion $promotion, array $cartItem, float $itemTotal): float
    {
        if ($promotion->discount_type_id === DiscountTypes::PERCENTAGE->value) {
            return $this->getItemPercentageDiscountAmount($itemTotal, $promotion);
        }

        $flatDiscountAmounts = CommonFunctions::numberFormat($promotion->flat_amount * $cartItem['quantity']);

        return $itemTotal > $flatDiscountAmounts ? $flatDiscountAmounts : $itemTotal;
    }

    public function getItemPercentageDiscountAmount(float $itemTotal, Promotion $promotion): float
    {
        if ($promotion->percentage) {
            return CommonFunctions::numberFormat($promotion->percentage * $itemTotal / 100);
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
