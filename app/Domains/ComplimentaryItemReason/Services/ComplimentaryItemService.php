<?php

declare(strict_types=1);

namespace App\Domains\ComplimentaryItemReason\Services;

use App\CommonFunctions;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\StoreManagerAuthorizationCodeUsage\Services\StoreManagerAuthorizationCodeUsageService;
use App\Models\ComplimentaryItemReason;
use App\Models\Director;
use App\Models\Employee;
use App\Models\Product;
use App\Models\StoreManager;
use Illuminate\Support\Collection;

class ComplimentaryItemService
{
    public function checkForApplicability(
        CheckSaleDetailsService $checkSaleDetailsService,
        ?ComplimentaryItemReason $complimentaryItemReason,
        array $cartItem,
        Collection $directors,
        Collection $storeManagers
    ): void {
        if (! $complimentaryItemReason instanceof ComplimentaryItemReason) {
            abort(412, 'Specified Complimentary Item Reason is not available in our records.');
        }

        if (! $checkSaleDetailsService->hasComplimentaryAuthorizer($cartItem)) {
            abort(
                412,
                'Complimentary item not allowed without an authorization from the director or store manager.'
            );
        }

        $this->checkNonRegularProduct($checkSaleDetailsService, (int) $cartItem['id']);

        if ($checkSaleDetailsService->hasDirector($cartItem)) {
            $director = $directors->firstWhere('id', '===', (int) $cartItem['director_id']);

            if (! $director instanceof Director) {
                abort(412, 'Specified Director is not available in our records.');
            }

            /** @var Employee $employee */
            $employee = $director->employee;

            if (! $employee->getStatus()) {
                $saleMismatchMessage = 'Specified Director: ' . $employee->getFullName() . ' account is inactive. Please contact admin.';
                CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
            }

            if ($director->passcode !== $cartItem['director_passcode']) {
                $saleMismatchMessage = 'Specified ' . $cartItem['director_passcode'] . ' passcode of director id: ' . $director->id . ' for complimentary item does not match with our records.';
                CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
            }
        }

        if ($checkSaleDetailsService->hasStoreManager($cartItem)) {
            $storeManager = $storeManagers->firstWhere('id', '===', (int) $cartItem['store_manager_id']);

            if (! $storeManager instanceof StoreManager) {
                abort(412, 'Specified StoreManager is not available in our records.');
            }

            /** @var Employee $employee */
            $employee = $storeManager->employee;

            if (! $employee->getStatus()) {
                $saleMismatchMessage = 'Specified Store Manager: ' . $employee->getFullName() . ' account is inactive. Please contact admin.';
                CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
            }

            if ($storeManager->passcode !== $cartItem['store_manager_passcode']) {
                $saleMismatchMessage = 'Specified ' . $cartItem['store_manager_passcode'] . ' passcode of store manager id: ' . $storeManager->id . ' for complimentary item does not match with our records.';
                CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
            }

            $this->checkStoreManagerAuthorizationCode($checkSaleDetailsService, $cartItem);
        }

        $itemSubtotal = $checkSaleDetailsService->getItemSubtotal($cartItem);

        if (! array_key_exists('item_discount_amount', $cartItem)) {
            $saleMismatchMessage = 'Item discount amount is required for the complimentary item discount.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        if (! CommonFunctions::compareFloatNumbers((float) $cartItem['item_discount_amount'], $itemSubtotal)) {
            $saleMismatchMessage = 'Specified discount amount does not match with our calculations. The actual discount amount is ' . $itemSubtotal . ' and requested discount amount is ' . $cartItem['item_discount_amount'] . '.';
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

    public function getItemDiscountAmount(float $itemTotal): float
    {
        return $itemTotal;
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

        $saleMismatchMessage = 'Complimentary is applicable on regular products only. The type of the product with the name ' . $product->name . ' is ' . $productType . '.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }
}
