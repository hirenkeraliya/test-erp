<?php

declare(strict_types=1);

namespace App\Domains\SaleItemPriceOverride\Services;

use App\CommonFunctions;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Common\Enums\NegotiatorTypes;
use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Company\Enums\DiscountApplicableTypes;
use App\Domains\Director\DirectorQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Models\Cashier;
use App\Models\Director;
use App\Models\Employee;
use App\Models\Member;
use App\Models\Product;
use App\Models\StoreManager;

class SaleItemPriceOverrideService
{
    public function checkForApplicability(
        CheckSaleDetailsService $checkSaleDetailsService,
        array $cartItem,
    ): void {
        $negotiator = null;
        $negotiatorType = '';
        // TODO: Temporary commenting due to frontend is not able to take this task.
        // if (! array_key_exists('price_override_discount_amount', $cartItem)) {
        //     $saleMismatchMessage = 'Price override discount amount is required for item price override amount.';
        //     CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        // }

        $this->checkNonRegularProduct($checkSaleDetailsService, (int) $cartItem['id']);

        if (0.0 === $cartItem['price_override_amount'] || 0 === $cartItem['price_override_amount']) {
            /** @var Product $product */
            $product = $checkSaleDetailsService->products->firstWhere('id', $cartItem['id']);
            $saleMismatchMessage = 'The specified product ' . $product->getName() . ' price override amount must be more than 0';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        if ($checkSaleDetailsService->hasStoreManagerPriceOverride($cartItem)) {
            $storeManagerQueries = resolve(StoreManagerQueries::class);
            $negotiator = $storeManagerQueries->getByIdWithStores(
                (int) $cartItem['store_manager_id'],
                $checkSaleDetailsService->companyId
            );
            $negotiatorType = NegotiatorTypes::STORE_MANAGER->value;

            $product = $checkSaleDetailsService->products->firstWhere('id', $cartItem['id']);

            if ($negotiator->brands->count() !== 0 && ! $negotiator->brands->firstWhere('id', $product->brand_id)) {
                $saleMismatchMessage = 'Unfortunately, ' . $negotiatorType . ' does not permit the sale of ' . $product->brand->name . ' brand products.';
                CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
            }
        }

        if ($checkSaleDetailsService->hasDirectorPriceOverride($cartItem)) {
            $directorQueries = resolve(DirectorQueries::class);
            $negotiator = $directorQueries->getByIdWithEmployeeAndLocations(
                (int) $cartItem['director_id'],
                $checkSaleDetailsService->companyId
            );
            $negotiatorType = NegotiatorTypes::DIRECTOR->value;
        }

        if ($checkSaleDetailsService->hasCashierPriceOverride($cartItem)) {
            $cashierQueries = resolve(CashierQueries::class);
            $negotiator = $cashierQueries->getByIdWithLocations(
                (int) $cartItem['cashier_id'],
                $checkSaleDetailsService->companyId
            );
            $negotiatorType = NegotiatorTypes::CASHIER->value;
        }

        if (null === $negotiator) {
            return;
        }

        /** @var Employee $employee */
        $employee = $negotiator->employee;

        $negotiatorLocationIds = $negotiator->locations->pluck('id');
        $locationId = $checkSaleDetailsService->location->id;

        if (! $negotiatorLocationIds->contains($locationId)) {
            $saleMismatchMessage = 'Specified ' . $negotiatorType . ' does not have access to the currently opened location.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        if ($negotiatorType === NegotiatorTypes::CASHIER->value && ! $employee->getStatus()) {
            $saleMismatchMessage = 'Specified ' . $negotiatorType . ': ' . $employee->getFullName() . ' account is inactive. Please contact the admin.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        if (
            $negotiatorType === NegotiatorTypes::STORE_MANAGER->value ||
            $negotiatorType === NegotiatorTypes::DIRECTOR->value
        ) {
            if (! $employee->getStatus()) {
                $saleMismatchMessage = 'Specified ' . $negotiatorType . ': ' . $employee->getFullName() . ' account is inactive. Please contact the admin.';
                CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
            }

            $requestPasscode = $negotiatorType === NegotiatorTypes::STORE_MANAGER->value ? $cartItem['store_manager_passcode'] : $cartItem['director_passcode'];

            /** @var StoreManager|Director $negotiator */
            if ($negotiator->passcode && $negotiator->passcode !== $requestPasscode) {
                $saleMismatchMessage = 'The specified ' . $negotiatorType . ' passcode for price override does not match our records.';
                CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
            }

            if ($negotiatorType === NegotiatorTypes::STORE_MANAGER->value) {
                $this->checkStoreManagerAuthorizationCode($checkSaleDetailsService, $cartItem);
            }
        }

        $itemPrice = $this->getItemPrice($checkSaleDetailsService, $cartItem);

        $allowedPriceOverrideDiscountAmount = $this->getPriceOverrideDiscountAmount(
            $negotiator,
            $checkSaleDetailsService,
            $negotiatorType,
            $itemPrice,
            (int) $cartItem['id']
        );

        $itemDiscountAmount = CommonFunctions::numberFormat(
            $allowedPriceOverrideDiscountAmount * $cartItem['quantity']
        );

        if (
            array_key_exists('price_override_discount_amount', $cartItem)
            && $cartItem['price_override_discount_amount'] > $itemDiscountAmount
        ) {
            $saleMismatchMessage = 'The requested price override discount amount of (' . $cartItem['price_override_discount_amount'] . ') is more than the minimum allowed amount for the ' . $negotiatorType . ' (' . $itemDiscountAmount . ')';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $actualPriceOverrideAmount = $this->getActualPriceOverrideAmount(
            $allowedPriceOverrideDiscountAmount,
            (int) $cartItem['id'],
            $cartItem,
            $checkSaleDetailsService
        );

        if (
            ($cartItem['price_override_amount'] > $itemPrice) ||
            ($actualPriceOverrideAmount > $itemPrice)
        ) {
            $saleMismatchMessage = 'Item price override amount should not be more than the item price.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        if (
            $checkSaleDetailsService->hasDreamPrice($cartItem) &&
            (
                ($cartItem['price_override_amount'] > $cartItem['dream_price_amount']) ||
                ($actualPriceOverrideAmount > $cartItem['dream_price_amount'])
            )
        ) {
            $saleMismatchMessage = 'The item price override amount should not exceed the item dream price amount.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        if ((float) $cartItem['price_override_amount'] < $actualPriceOverrideAmount) {
            $saleMismatchMessage = 'The requested price override amount of (' . $cartItem['price_override_amount'] . ') is less than the minimum allowed amount for the ' . $negotiatorType . ' (' . $actualPriceOverrideAmount . ')';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkStoreManagerAuthorizationCode(
        CheckSaleDetailsService $checkSaleDetailsService,
        array $cartItem
    ): void {
        if (! array_key_exists('store_manager_authorization_code', $cartItem)) {
            return;
        }

        $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
        $storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
            $checkSaleDetailsService->saleMismatches,
            (int) $cartItem['store_manager_id'],
            $cartItem['store_manager_authorization_code'],
            $checkSaleDetailsService->saleData->happened_at
        );
    }

    public function getItemPrice(CheckSaleDetailsService $checkSaleDetailsService, array $cartItem): float
    {
        if ($checkSaleDetailsService->company->discount_applicable_type === DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value) {
            $itemTotal = $checkSaleDetailsService->saleDiscountService->applyDreamPriceAndItemPromotionOn($cartItem);

            return CommonFunctions::numberFormat($itemTotal / $cartItem['quantity']);
        }

        return $cartItem['price'] ? (float) $cartItem['price'] : (float) $cartItem['open_price'];
    }

    public function getItemPriceAfterDreamPriceAndItemPromotion(
        CheckSaleDetailsService $checkSaleDetailsService,
        array $cartItem
    ): float {
        $itemTotal = $checkSaleDetailsService->saleDiscountService->applyDreamPriceAndItemPromotionOn($cartItem);

        return CommonFunctions::numberFormat($itemTotal / $cartItem['quantity']);
    }

    public function getActualPriceOverrideAmount(
        float $allowedPriceOverrideDiscountAmount,
        int $cartItemId,
        array $cartItem,
        CheckSaleDetailsService $checkSaleDetailsService
    ): float {
        /** @var Product $product */
        $product = $checkSaleDetailsService->products->firstWhere('id', $cartItemId);

        if ($checkSaleDetailsService->saleData->employee_id || ($checkSaleDetailsService->member instanceof Member && $checkSaleDetailsService->member->employee_id)) {
            return (float) $product->staff_price;
        }

        $itemPrice = $this->getItemPriceAfterDreamPriceAndItemPromotion($checkSaleDetailsService, $cartItem);

        return CommonFunctions::numberFormat($itemPrice - $allowedPriceOverrideDiscountAmount);
    }

    public function getPriceOverrideLimitPercentage(
        StoreManager|Director|Cashier $negotiator,
        string $negotiatorType
    ): float|int {
        if ($negotiatorType === NegotiatorTypes::CASHIER->value) {
            /** @var Cashier $negotiator */
            return $negotiator->cashierGroup ? (float) $negotiator->cashierGroup->price_override_limit_percentage_for_item : 0;
        }

        /** @var StoreManager|Director $negotiator */
        return (float) $negotiator->price_override_limit_percentage_for_item;
    }

    public function getPriceOverrideLimitType(
        StoreManager|Director|Cashier $negotiator,
        string $negotiatorType
    ): int {
        if ($negotiatorType === NegotiatorTypes::CASHIER->value) {
            /** @var Cashier $negotiator */
            return $negotiator->cashierGroup ? $negotiator->cashierGroup->price_override_type : 0;
        }

        /** @var StoreManager|Director $negotiator */
        return $negotiator->price_override_type;
    }

    public function getPriceOverrideLimitFlat(
        CheckSaleDetailsService $checkSaleDetailsService,
        int $cartItemId,
        string $negotiatorType,
        float $itemPrice,
    ): float {
        /** @var Product $product */
        $product = $checkSaleDetailsService->products->firstWhere('id', $cartItemId);

        if (($checkSaleDetailsService->member instanceof Member && $checkSaleDetailsService->member->employee_id) || $checkSaleDetailsService->saleData->employee_id) {
            return CommonFunctions::numberFormat($itemPrice - $product->staff_price);
        }

        if ($negotiatorType === NegotiatorTypes::CASHIER->value) {
            return CommonFunctions::numberFormat($itemPrice - $product->minimum_price);
        }

        return CommonFunctions::numberFormat($itemPrice - $product->wholesale_price);
    }

    public function getPriceOverrideDiscountAmount(
        StoreManager|Director|Cashier $negotiator,
        CheckSaleDetailsService $checkSaleDetailsService,
        string $negotiatorType,
        float $itemPrice,
        int $cartItemId,
    ): float {
        $priceOverrideLimitType = $this->getPriceOverrideLimitType($negotiator, $negotiatorType);

        if ($priceOverrideLimitType === PriceOverrideTypes::FLAT->value) {
            return $this->getPriceOverrideLimitFlat($checkSaleDetailsService, $cartItemId, $negotiatorType, $itemPrice);
        }

        $priceOverrideLimitPercentage = $this->getPriceOverrideLimitPercentage($negotiator, $negotiatorType);

        return CommonFunctions::numberFormat($priceOverrideLimitPercentage * $itemPrice / 100);
    }

    public function checkNonRegularProduct(CheckSaleDetailsService $checkSaleDetailsService, int $cartItemId): void
    {
        /** @var Product $product */
        $product = $checkSaleDetailsService->products->firstWhere('id', $cartItemId);

        if ($product->type_id === ProductTypes::REGULAR_PRODUCT->value) {
            return;
        }

        if ($product->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            return;
        }

        $productType = ProductTypes::getFormattedCaseName($product->type_id);

        $saleMismatchMessage = 'Price override is applicable only on regular products. The type of product with the name ' . $product->name . ' is ' . $productType . '.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function getItemTotal(CheckSaleDetailsService $checkSaleDetailsService, array $cartItem): float
    {
        if ($checkSaleDetailsService->company->discount_applicable_type === DiscountApplicableTypes::ADDITIONAL_DISCOUNT_ON_ALREADY_DISCOUNTED_PRICES->value) {
            return $checkSaleDetailsService->saleDiscountService->applyDreamPriceAndItemPromotionOn($cartItem);
        }

        return $cartItem['price'] ? (float) ($cartItem['price'] * $cartItem['quantity']) : (float) ($cartItem['open_price'] * $cartItem['quantity']);
    }

    public function getItemDiscountAmount(CheckSaleDetailsService $checkSaleDetailsService, array $cartItem): float
    {
        if (array_key_exists(
            'price_override_discount_amount',
            $cartItem
        ) && $cartItem['price_override_discount_amount']) {
            return (float) $cartItem['price_override_discount_amount'];
        }

        $itemTotal = $this->getItemTotal($checkSaleDetailsService, $cartItem);
        $discountAmount = CommonFunctions::numberFormat(
            $itemTotal - ($cartItem['price_override_amount'] * $cartItem['quantity'])
        );

        if ($discountAmount < 0) {
            return 0.00;
        }

        return $discountAmount;
    }
}
