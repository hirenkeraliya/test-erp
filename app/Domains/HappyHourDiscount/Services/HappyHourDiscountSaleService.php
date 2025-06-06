<?php

declare(strict_types=1);

namespace App\Domains\HappyHourDiscount\Services;

use App\CommonFunctions;
use App\Domains\HappyHourDiscount\Enums\ProductTypes;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Models\HappyHourDiscount;
use App\Models\Product;

class HappyHourDiscountSaleService
{
    public function checkForApplicability(
        CheckSaleDetailsService $checkSaleDetailsService,
        array $cartItem,
    ): void {
        $saleDiscountService = $checkSaleDetailsService->saleDiscountService;
        $happyHourDiscount = $saleDiscountService->happyHourDiscounts
            ->firstWhere('happyHourDiscountTransaction.offline_id', '===', $cartItem['happy_hours_offline_id']);

        if (! $happyHourDiscount instanceof HappyHourDiscount) {
            abort(412, 'Specified Happy Hour Discount is not available in our records.');
        }

        $this->checkStore($checkSaleDetailsService, $happyHourDiscount);

        $this->checkDate($checkSaleDetailsService, $happyHourDiscount);

        $this->checkProduct($checkSaleDetailsService, $happyHourDiscount, $cartItem);

        $this->checkDiscountAmount($checkSaleDetailsService, $happyHourDiscount, $cartItem);
    }

    public function checkProduct(
        CheckSaleDetailsService $checkSaleDetailsService,
        HappyHourDiscount $happyHourDiscount,
        array $cartItem,
    ): void {
        if ($happyHourDiscount->product_type_id === ProductTypes::ALL->value) {
            return;
        }

        $product = $checkSaleDetailsService->products->firstWhere('id', $cartItem['id']);

        if ($happyHourDiscount->product_type_id === ProductTypes::BRAND->value) {
            $this->checkProductBrand($checkSaleDetailsService, $happyHourDiscount, $product);

            return;
        }

        if ($happyHourDiscount->product_type_id === ProductTypes::STYLE->value) {
            $this->checkProductStyle($checkSaleDetailsService, $happyHourDiscount, $product);

            return;
        }

        if ($happyHourDiscount->product_type_id === ProductTypes::DEPARTMENTS->value) {
            $this->checkProductDepartment($checkSaleDetailsService, $happyHourDiscount, $product);

            return;
        }

        $this->checkProductCategory($checkSaleDetailsService, $happyHourDiscount, $product);
    }

    public function checkProductBrand(
        CheckSaleDetailsService $checkSaleDetailsService,
        HappyHourDiscount $happyHourDiscount,
        Product $product,
    ): void {
        $brands = $happyHourDiscount->brands;

        if ($brands->firstWhere('id', $product->brand_id)) {
            return;
        }

        $saleMismatchMessage = 'Specified Happy Hour Discount is not available for Specified product brand';

        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkProductStyle(
        CheckSaleDetailsService $checkSaleDetailsService,
        HappyHourDiscount $happyHourDiscount,
        Product $product,
    ): void {
        $styles = $happyHourDiscount->styles;

        if ($styles->firstWhere('id', $product->style_id)) {
            return;
        }

        $saleMismatchMessage = 'Specified Happy Hour Discount is not available for Specified product style';

        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkProductCategory(
        CheckSaleDetailsService $checkSaleDetailsService,
        HappyHourDiscount $happyHourDiscount,
        Product $product,
    ): void {
        $happyHourDiscountCategoryIds = $happyHourDiscount->categories->pluck('id');

        $cartItemCategoryIds = $product->categories->pluck('id');

        $isValidProductAccordingToCategories = $happyHourDiscountCategoryIds->intersect($cartItemCategoryIds);

        if ($isValidProductAccordingToCategories->isNotEmpty()) {
            return;
        }

        $saleMismatchMessage = 'Specified Happy Hour Discount is not available for Specified product category';

        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkProductDepartment(
        CheckSaleDetailsService $checkSaleDetailsService,
        HappyHourDiscount $happyHourDiscount,
        Product $product,
    ): void {
        $departments = $happyHourDiscount->departments;

        if ($departments->firstWhere('id', $product->department_id)) {
            return;
        }

        $saleMismatchMessage = 'Specified Happy Hour Discount is not available for Specified product department';

        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkDiscountAmount(
        CheckSaleDetailsService $checkSaleDetailsService,
        HappyHourDiscount $happyHourDiscount,
        array $cartItem
    ): void {
        $discountAmount = $this->getCalculateItemDiscountAmount(
            $checkSaleDetailsService,
            $cartItem,
            (float) $happyHourDiscount->new_price
        );

        if (CommonFunctions::compareFloatNumbers((float) $cartItem['happy_hours_discount_amount'], $discountAmount)) {
            return;
        }

        $saleMismatchMessage = 'Specified discount amount does not match with our calculations. The actual discount amount is ' . $discountAmount . ' and requested discount amount is ' . $cartItem['happy_hours_discount_amount'] . '.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkDate(
        CheckSaleDetailsService $checkSaleDetailsService,
        HappyHourDiscount $happyHourDiscount,
    ): void {
        $happenedAt = $checkSaleDetailsService->saleData->happened_at;

        if ($happyHourDiscount->start_date > $happenedAt || $happyHourDiscount->end_date < $happenedAt) {
            $saleMismatchMessage = 'Specified Happy Hour Discount is available between ' . $happyHourDiscount->start_date . ' to ' . $happyHourDiscount->end_date . ' only. The sale date is ' . $happenedAt . '.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkStore(
        CheckSaleDetailsService $checkSaleDetailsService,
        HappyHourDiscount $happyHourDiscount,
    ): void {
        if ($happyHourDiscount->location_id === $checkSaleDetailsService->location->id) {
            return;
        }

        $saleMismatchMessage = 'Specified Happy Hour Discount is not available for ' . $checkSaleDetailsService->location->name . ' location';

        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function getCalculateItemDiscountAmount(
        CheckSaleDetailsService $checkSaleDetailsService,
        array $cartItem,
        float $happyHourDiscountPrice
    ): float {
        $itemSubtotal = $checkSaleDetailsService->getItemSubtotal($cartItem);
        $happyHourDiscountItemSubtotal = CommonFunctions::numberFormat($happyHourDiscountPrice * $cartItem['quantity']);

        return $itemSubtotal - $happyHourDiscountItemSubtotal;
    }

    public function getItemDiscountAmount(array $cartItem): float
    {
        return (float) $cartItem['happy_hours_discount_amount'];
    }
}
