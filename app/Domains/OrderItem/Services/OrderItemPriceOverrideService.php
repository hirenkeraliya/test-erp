<?php

declare(strict_types=1);

namespace App\Domains\OrderItem\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Common\Enums\PriceOverrideTypes;
use App\Domains\Order\Services\CheckOrderDetailsService;
use App\Domains\Product\Enums\ProductTypes;
use App\Models\Employee;
use App\Models\Product;
use App\Models\StoreManager;

class OrderItemPriceOverrideService
{
    public function checkForApplicability(
        CheckOrderDetailsService $checkOrderDetailsService,
        array $orderItem,
    ): void {
        $storeManager = $checkOrderDetailsService->storeManager;
        $storeManagerType = ModelMapping::STORE_MANAGER->name;

        $this->checkNonRegularProduct($checkOrderDetailsService, (int) $orderItem['id']);

        if (0.0 === $orderItem['price_override_amount'] || 0 === $orderItem['price_override_amount']) {
            /** @var Product $product */
            $product = $checkOrderDetailsService->products->firstWhere('id', $orderItem['id']);
            abort(
                412,
                'The specified product ' . $product->getName() . ' price override amount must be more than 0'
            );
        }

        /** @var Employee $employee */
        $employee = $storeManager->employee;

        $storeManagerStoreIds = $storeManager->locations->pluck('id');
        $locationId = $checkOrderDetailsService->location->id;

        if (! $storeManagerStoreIds->contains($locationId)) {
            abort(412, 'Specified ' . $storeManagerType . ' does not have access to the currently opened location.');
        }

        if (! $employee->getStatus()) {
            abort(
                412,
                'Specified ' . $storeManagerType . ': ' . $employee->getFullName() . ' account is inactive. Please contact the admin.'
            );
        }

        $itemPrice = $this->getItemPrice($checkOrderDetailsService, $orderItem);

        $allowedPriceOverrideDiscountAmount = $this->getPriceOverrideDiscountAmount(
            $storeManager,
            $checkOrderDetailsService,
            (int) $orderItem['id'],
            $itemPrice,
        );

        $actualPriceOverrideAmount = $this->getActualPriceOverrideAmount(
            $itemPrice,
            $allowedPriceOverrideDiscountAmount,
        );

        if (
            ($orderItem['price_override_amount'] > $itemPrice) ||
            ($actualPriceOverrideAmount > $itemPrice)
        ) {
            abort(412, 'Item price override amount should not be more than the item price.');
        }

        if (CommonFunctions::numberFormat((float) $orderItem['price_override_amount']) < CommonFunctions::numberFormat(
            $actualPriceOverrideAmount
        )) {
            abort(
                412,
                __('The requested price override amount of (:price_override_amount) is less than the minimum allowed amount for the :store_manager_type (:actual_price_override_amount).', [
                    'price_override_amount' => $orderItem['price_override_amount'],
                    'store_manager_type' => $storeManagerType,
                    'actual_price_override_amount' => $actualPriceOverrideAmount,
                ])
            );
        }
    }

    public function getItemPrice(CheckOrderDetailsService $checkOrderDetailsService, array $orderItem): float
    {
        return $orderItem['price'] ? (float) $orderItem['price'] : (float) $orderItem['open_price'];
    }

    public function getActualPriceOverrideAmount(
        float $itemPrice,
        float $allowedPriceOverrideDiscountAmount
    ): float {
        return CommonFunctions::numberFormat($itemPrice - $allowedPriceOverrideDiscountAmount);
    }

    public function getPriceOverrideLimitPercentage(
        StoreManager $storeManager,
        string $storeManagerType
    ): float|int {
        /** @var StoreManager $storeManager */
        return (float) $storeManager->price_override_limit_percentage_for_item;
    }

    public function getPriceOverrideLimitType(StoreManager $storeManager, string $storeManagerType): int
    {
        /** @var StoreManager $storeManager */
        return $storeManager->price_override_type;
    }

    public function getPriceOverrideLimitFlat(
        CheckOrderDetailsService $checkOrderDetailsService,
        int $orderItemId,
    ): float {
        /** @var Product $product */
        $product = $checkOrderDetailsService->products->firstWhere('id', $orderItemId);

        return (float) $product->wholesale_price;
    }

    public function getPriceOverrideDiscountAmount(
        StoreManager $storeManager,
        CheckOrderDetailsService $checkOrderDetailsService,
        int $orderItemId,
        float $itemPrice
    ): float {
        if ($storeManager->price_override_type === PriceOverrideTypes::FLAT->value) {
            return $this->getPriceOverrideLimitFlat($checkOrderDetailsService, $orderItemId);
        }

        $priceOverrideLimitPercentage = (float) $storeManager->price_override_limit_percentage_for_item;

        return CommonFunctions::numberFormat($priceOverrideLimitPercentage * $itemPrice / 100);
    }

    public function getItemDiscountAmount(array $orderItem): float
    {
        $itemPrice = $orderItem['price'] ?? $orderItem['open_price'];

        $discountAmount = CommonFunctions::numberFormat(
            (float) (($itemPrice - $orderItem['price_override_amount']) * $orderItem['quantity'])
        );

        if ($discountAmount < 0) {
            return 0.00;
        }

        return $discountAmount;
    }

    public function checkNonRegularProduct(CheckOrderDetailsService $checkOrderDetailsService, int $orderItemId): void
    {
        /** @var Product $product */
        $product = $checkOrderDetailsService->products->firstWhere('id', $orderItemId);

        $productType = ProductTypes::getFormattedCaseName($product->type_id);

        if ($product->type_id !== ProductTypes::REGULAR_PRODUCT->value) {
            abort(
                412,
                'Price override is applicable only on regular products. The type of product with the name ' . $product->name . ' is ' . $productType . '.'
            );
        }
    }
}
