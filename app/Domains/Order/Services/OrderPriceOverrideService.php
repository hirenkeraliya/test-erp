<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Models\Employee;
use App\Models\StoreManager;

class OrderPriceOverrideService
{
    public function checkForApplicability(CheckOrderDetailsService $checkOrderDetailsService, float $cartSubtotal): void
    {
        $cartSubtotal = CommonFunctions::numberFormat($cartSubtotal);
        $storeManager = $checkOrderDetailsService->storeManager;
        $storeManagerType = ModelMapping::STORE_MANAGER->name;

        if (! $checkOrderDetailsService->company->allow_price_override_cart_level) {
            abort(412, 'The ability to override prices at the cart level has been deactivated.');
        }

        /** @var Employee $employee */
        $employee = $storeManager->employee;

        $storeManagerStoreIds = $storeManager->locations->pluck('id');
        $locationId = $checkOrderDetailsService->location->id;

        if (! $storeManagerStoreIds->contains($locationId)) {
            abort(
                412,
                'The ' . $storeManagerType . ' you selected does not have permission to access the currently open location.'
            );
        }

        if (! $employee->getStatus()) {
            abort(412, $employee->getFullName() . ' account is inactive. Please contact admin.');
        }

        $priceOverrideLimitPercentage = $this->getPriceOverrideLimitPercentage($storeManager);

        $allowedPriceOverrideDiscountAmount = $this->getPriceOverrideDiscountAmount(
            $priceOverrideLimitPercentage,
            $cartSubtotal
        );
        $actualPriceOverrideAmount = CommonFunctions::numberFormat($cartSubtotal - $allowedPriceOverrideDiscountAmount);

        if (
            ($checkOrderDetailsService->orderData->cart_price_override_amount > $cartSubtotal) ||
            ($actualPriceOverrideAmount > $cartSubtotal)
        ) {
            abort(412, 'The price override amount for the cart should not exceed the subtotal of the cart.');
        }

        if ($checkOrderDetailsService->orderData->cart_price_override_amount < $actualPriceOverrideAmount) {
            abort(
                412,
                'Requested Price override amount (' . $checkOrderDetailsService->orderData->cart_price_override_amount . ') is less than what is minimum allowed to the ' . $storeManagerType . ' (' . $actualPriceOverrideAmount . ')'
            );
        }
    }

    public function getPriceOverrideLimitPercentage(StoreManager $storeManager): float
    {
        /** @var StoreManager $storeManager */
        return (float) $storeManager->price_override_limit_percentage_for_cart;
    }

    public function getPriceOverrideDiscountAmount(float $priceOverrideLimitPercentage, float $cartSubtotal): float
    {
        return CommonFunctions::numberFormat($priceOverrideLimitPercentage * $cartSubtotal / 100);
    }

    public function getDiscountAmount(float $cartSubtotal, float $priceOverrideAmount): float
    {
        $discountAmount = CommonFunctions::numberFormat($cartSubtotal - $priceOverrideAmount);

        if ($discountAmount < 0) {
            return 0.00;
        }

        return $discountAmount;
    }
}
