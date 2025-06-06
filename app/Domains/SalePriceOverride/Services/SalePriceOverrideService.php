<?php

declare(strict_types=1);

namespace App\Domains\SalePriceOverride\Services;

use App\CommonFunctions;
use App\Domains\Cashier\CashierQueries;
use App\Domains\Common\Enums\NegotiatorTypes;
use App\Domains\Director\DirectorQueries;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Models\Cashier;
use App\Models\Director;
use App\Models\Employee;
use App\Models\StoreManager;

class SalePriceOverrideService
{
    public function checkForApplicability(
        CheckSaleDetailsService $checkSaleDetailsService,
        float $cartSubtotal,
        float $subtotalBeforePriceOverrideDiscount
    ): void {
        $cartSubtotal = CommonFunctions::numberFormat($cartSubtotal);
        $negotiator = null;
        $negotiatorType = '';

        if (! $checkSaleDetailsService->company->allow_price_override_cart_level) {
            $saleMismatchMessage = 'The ability to override prices at the cart level has been deactivated.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        // TODO: Temporary commenting due to frontend is not able to take this task
        // if (! $checkSaleDetailsService->saleData->cart_price_override_discount_amount) {
        //     $saleMismatchMessage = 'offline id: ' . $checkSaleDetailsService->saleData->offline_sale_id . ' cart price override discount amount required';
        //     CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        // }

        if (0.0 === $checkSaleDetailsService->saleData->cart_price_override_amount) {
            $saleMismatchMessage = 'offline id: ' . $checkSaleDetailsService->saleData->offline_sale_id . ' cart price override amount must be more than 0';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        if ($checkSaleDetailsService->hasStoreManagerPriceOverrideForCart()) {
            $storeManagerQueries = resolve(StoreManagerQueries::class);
            $negotiator = $storeManagerQueries->getByIdWithStores(
                (int) $checkSaleDetailsService->saleData->store_manager_id,
                $checkSaleDetailsService->companyId
            );
            $negotiatorType = NegotiatorTypes::STORE_MANAGER->value;
        }

        if ($checkSaleDetailsService->hasDirectorPriceOverrideForCart()) {
            $directorQueries = resolve(DirectorQueries::class);
            $negotiator = $directorQueries->getByIdWithEmployeeAndLocations(
                (int) $checkSaleDetailsService->saleData->director_id,
                $checkSaleDetailsService->companyId
            );
            $negotiatorType = NegotiatorTypes::DIRECTOR->value;
        }

        if ($checkSaleDetailsService->hasCashierPriceOverrideForCart()) {
            $cashierQueries = resolve(CashierQueries::class);
            $negotiator = $cashierQueries->getByIdWithLocations(
                (int) $checkSaleDetailsService->saleData->cashier_id,
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
            $saleMismatchMessage = 'The ' . $negotiatorType . ' you selected does not have permission to access the currently open location.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        if ($negotiatorType === NegotiatorTypes::CASHIER->value && ! $employee->getStatus()) {
            $saleMismatchMessage = 'Specified ' . $negotiatorType . ': ' . $employee->getFullName() . ' account is inactive. Please contact admin.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        if (
            $negotiatorType === NegotiatorTypes::STORE_MANAGER->value ||
            $negotiatorType === NegotiatorTypes::DIRECTOR->value
        ) {
            if (! $employee->getStatus()) {
                $saleMismatchMessage = 'Specified ' . $negotiatorType . ': ' . $employee->getFullName() . ' account is inactive. Please contact admin.';
                CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
            }

            $requestPasscode = $negotiatorType === NegotiatorTypes::STORE_MANAGER->value ? $checkSaleDetailsService->saleData->store_manager_passcode : $checkSaleDetailsService->saleData->director_passcode;

            /** @var StoreManager|Director $negotiator */
            if ($negotiator->passcode && $negotiator->passcode !== $requestPasscode) {
                $saleMismatchMessage = 'The ' . $negotiatorType . ' The provided passcode for price override does not correspond with our records.';
                CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
            }

            if ($negotiatorType === NegotiatorTypes::STORE_MANAGER->value) {
                $this->checkStoreManagerAuthorizationCode($checkSaleDetailsService);
            }
        }

        $priceOverrideLimitPercentage = $this->getPriceOverrideLimitPercentage($negotiator, $negotiatorType);

        $allowedPriceOverrideDiscountAmount = $this->getPriceOverrideDiscountAmount(
            (float) $priceOverrideLimitPercentage,
            $cartSubtotal
        );

        // TODO: Temporary commenting due to frontend is not able to take this task
        // if ($checkSaleDetailsService->saleData->cart_price_override_discount_amount > $allowedPriceOverrideDiscountAmount) {
        //     $saleMismatchMessage = 'Requested Price override discount amount (' . $checkSaleDetailsService->saleData->cart_price_override_amount . ') is more than what is minimum discount amount allowed to the ' . $negotiatorType . ' (' . $allowedPriceOverrideDiscountAmount . ')';
        //     CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        // }

        $actualPriceOverrideAmount = CommonFunctions::numberFormat(
            $subtotalBeforePriceOverrideDiscount - $allowedPriceOverrideDiscountAmount
        );

        if (
            ($checkSaleDetailsService->saleData->cart_price_override_amount > $cartSubtotal) ||
            ($actualPriceOverrideAmount > $cartSubtotal)
        ) {
            $saleMismatchMessage = 'The price override amount for the cart should not exceed the subtotal of the cart.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        if ($checkSaleDetailsService->saleData->cart_price_override_amount < $actualPriceOverrideAmount) {
            $saleMismatchMessage = 'Requested Price override amount (' . $checkSaleDetailsService->saleData->cart_price_override_amount . ') is less than what is minimum allowed to the ' . $negotiatorType . ' (' . $actualPriceOverrideAmount . ')';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkStoreManagerAuthorizationCode(CheckSaleDetailsService $checkSaleDetailsService): void
    {
        $storeManagerAuthorizationCodeUsageService = resolve(StoreManagerAuthorizationCodeUsageService::class);
        $storeManagerAuthorizationCodeUsageService->checkStoreManagerAuthorizationCode(
            $checkSaleDetailsService->saleMismatches,
            (int) $checkSaleDetailsService->saleData->store_manager_id,
            $checkSaleDetailsService->saleData->store_manager_authorization_code,
            $checkSaleDetailsService->saleData->happened_at
        );
    }

    public function getPriceOverrideLimitPercentage(
        StoreManager|Director|Cashier $negotiator,
        string $negotiatorType
    ): float|int {
        if ($negotiatorType === NegotiatorTypes::CASHIER->value) {
            /** @var Cashier $negotiator */
            return $negotiator->cashierGroup ? (float) $negotiator->cashierGroup->price_override_limit_percentage_for_cart : 0;
        }

        /** @var StoreManager|Director $negotiator */
        return (float) $negotiator->price_override_limit_percentage_for_cart;
    }

    public function getPriceOverrideDiscountAmount(float $priceOverrideLimitPercentage, float $cartSubtotal): float
    {
        return CommonFunctions::numberFormat($priceOverrideLimitPercentage * $cartSubtotal / 100);
    }

    public function getDiscountAmount(
        float $cartSubtotal,
        float $priceOverrideAmount,
        ?float $cartPriceOverrideDiscountAmount
    ): float {
        if ($cartPriceOverrideDiscountAmount > 0) {
            return $cartPriceOverrideDiscountAmount;
        }

        $discountAmount = CommonFunctions::numberFormat($cartSubtotal - $priceOverrideAmount);

        if ($discountAmount < 0) {
            return 0.00;
        }

        return $discountAmount;
    }
}
