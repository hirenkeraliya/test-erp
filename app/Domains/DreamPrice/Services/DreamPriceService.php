<?php

declare(strict_types=1);

namespace App\Domains\DreamPrice\Services;

use App\CommonFunctions;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Models\DreamPrice;
use App\Models\DreamPriceProduct;
use App\Models\Employee;
use App\Models\Member;
use App\Models\Product;
use Carbon\Carbon;

class DreamPriceService
{
    public function checkForApplicability(
        CheckSaleDetailsService $checkSaleDetailsService,
        DreamPrice $dreamPrice,
        array $cartItem
    ): void {
        /** @var Product $product */
        $product = $checkSaleDetailsService->products->firstWhere('id', $cartItem['id']);

        $this->checkProductBox($checkSaleDetailsService, $cartItem);
        $this->checkMember($checkSaleDetailsService, $dreamPrice);
        $this->checkWalkInMember($checkSaleDetailsService, $dreamPrice);

        $this->checkEmployee($checkSaleDetailsService, $dreamPrice);

        $this->checkNonRegularProduct($checkSaleDetailsService, $product);

        $this->checkDreamPriceDateRange($checkSaleDetailsService, $dreamPrice);

        $this->checkDreamPriceAmount($checkSaleDetailsService, $dreamPrice, $cartItem, $product);

        $this->checkDreamPriceStores($checkSaleDetailsService, $dreamPrice);

        $this->checkDreamPriceIsActive($checkSaleDetailsService, $dreamPrice);
    }

    public function getDiscountFor(array $cartItem): float
    {
        $itemPrice = $cartItem['price'] ?? $cartItem['open_price'];

        $discountAmount = CommonFunctions::numberFormat(
            (float) (($itemPrice - $cartItem['dream_price_amount']) * $cartItem['quantity'])
        );

        if ($discountAmount < 0) {
            return 0.00;
        }

        return $discountAmount;
    }

    public function checkDreamPriceDateRange(
        CheckSaleDetailsService $checkSaleDetailsService,
        DreamPrice $dreamPrice
    ): void {
        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $checkSaleDetailsService->saleData->happened_at);
        $happenedAt = $happenedAtFormat->format('Y-m-d');
        if ($dreamPrice->start_date > $happenedAt || $dreamPrice->end_date < $happenedAt) {
            $saleMismatchMessage = 'Specified dream price is available between ' . $dreamPrice->start_date . ' and ' . $dreamPrice->end_date . '. only. But the specified sale date is ' . $happenedAt . '.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkDreamPriceAmount(
        CheckSaleDetailsService $checkSaleDetailsService,
        DreamPrice $dreamPrice,
        array $cartItem,
        Product $product,
    ): void {
        $dreamPriceProduct = $dreamPrice->dreamPriceProducts->firstWhere('product_id', $cartItem['id']);

        if (! $dreamPriceProduct instanceof DreamPriceProduct) {
            $saleMismatchMessage = 'The dream price is not available for the product ' . $product->name . ' but is specified in the request as ' . $cartItem['dream_price_amount'] . '.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        if ((float) $dreamPriceProduct['price'] !== (float) $cartItem['dream_price_amount']) {
            $saleMismatchMessage = 'The dream price of the product ' . $product->name . ' is ' . $dreamPriceProduct['price'] . ' but the specified price is ' . $cartItem['dream_price_amount'] . '.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }
    }

    public function checkProductBox(CheckSaleDetailsService $checkSaleDetailsService, array $cartItem): void
    {
        if (! $checkSaleDetailsService->isBoxProductAttached($cartItem)) {
            return;
        }

        $saleMismatchMessage = 'Dream Price is not applicable on product bundle.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkNonRegularProduct(CheckSaleDetailsService $checkSaleDetailsService, Product $product): void
    {
        if ($product->type_id === ProductTypes::REGULAR_PRODUCT->value) {
            return;
        }

        if ($product->type_id === ProductTypes::ASSEMBLY_PRODUCT->value) {
            return;
        }

        $productType = ProductTypes::getFormattedCaseName($product->type_id);

        $saleMismatchMessage = 'Dream Price is applicable on regular products only. The type of the product with the name ' . $product->name . ' is ' . $productType . '.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkDreamPriceStores(
        CheckSaleDetailsService $checkSaleDetailsService,
        DreamPrice $dreamPrice,
    ): void {
        if ($dreamPrice->locations->isEmpty()) {
            return;
        }

        if ($dreamPrice->locations->firstWhere('id', $checkSaleDetailsService->location->id)) {
            return;
        }

        $saleMismatchMessage = 'The dream price is not available for the location ' . $checkSaleDetailsService->location->name;
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkMember(CheckSaleDetailsService $checkSaleDetailsService, DreamPrice $dreamPrice): void
    {
        if (! $dreamPrice->allow_registered_member && $this->isMemberAttached($checkSaleDetailsService)) {
            $saleMismatchMessage = 'Specified dream price is not allowed for the registered members.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        if (! $this->isMemberAttached($checkSaleDetailsService)) {
            return;
        }

        if ($dreamPrice->memberGroups->isEmpty()) {
            return;
        }

        if (
            $checkSaleDetailsService->member instanceof Member
            && $checkSaleDetailsService->member->memberGroupMembers->whereIn(
                'member_group_id',
                $dreamPrice->memberGroups->pluck('id')
            )->isNotEmpty()
        ) {
            return;
        }

        $saleMismatchMessage = 'Member is not valid for the specified dream price.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkWalkInMember(CheckSaleDetailsService $checkSaleDetailsService, DreamPrice $dreamPrice): void
    {
        if ($dreamPrice->allow_walk_in_member) {
            return;
        }

        if ($this->isMemberAttached($checkSaleDetailsService)) {
            return;
        }

        if ($checkSaleDetailsService->saleData->member_id) {
            return;
        }

        $saleMismatchMessage = 'Specified dream price is not allowed for the walk in member.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function isMemberAttached(CheckSaleDetailsService $checkSaleDetailsService): bool
    {
        if ($checkSaleDetailsService->isMemberAttached()) {
            return true;
        }

        return $checkSaleDetailsService->hasMemberDetails();
    }

    public function checkEmployee(CheckSaleDetailsService $checkSaleDetailsService, DreamPrice $dreamPrice): void
    {
        if (! $dreamPrice->allow_employee && $checkSaleDetailsService->saleData->is_employee) {
            $saleMismatchMessage = 'Specified dream price is not allowed for the employees.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        if (! $checkSaleDetailsService->saleData->is_employee) {
            return;
        }

        if ($dreamPrice->employeeGroups->isEmpty()) {
            return;
        }

        if (
            $checkSaleDetailsService->employee instanceof Employee
            && $checkSaleDetailsService->employee->group_id
            && $dreamPrice->employeeGroups->firstWhere('id', $checkSaleDetailsService->employee->group_id)
        ) {
            return;
        }

        $saleMismatchMessage = 'Employees is not valid for the specified dream price.';
        CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
    }

    public function checkDreamPriceIsActive(
        CheckSaleDetailsService $checkSaleDetailsService,
        DreamPrice $dreamPrice
    ): void {
        if (false === $dreamPrice->status) {
            $saleMismatchMessage = 'Specified dream price is inactive.';
            CommonFunctions::addMismatchOrAbort($checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }
    }
}
