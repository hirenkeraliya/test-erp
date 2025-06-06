<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;
use App\Domains\Batch\BatchQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Promotion\Enums\ItemWisePromotionTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleDiscount\Enums\DiscountableTypes;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleItemDiscount\Enums\DiscountableTypes as EnumsDiscountableTypes;
use App\Domains\SaleReturnReason\SaleReturnReasonQueries;
use App\Domains\SerialNumber\SerialNumberQueries;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SaleReturnService
{
    public CheckSaleDetailsService $checkSaleDetailsService;

    public Collection $saleReturnMismatches;

    public Collection $returnItems;

    public Collection $returnedSaleItems;

    public Collection $saleReturnReasons;

    public Collection $batches;

    public function setDetails(CheckSaleDetailsService $checkSaleDetailsService): void
    {
        $this->checkSaleDetailsService = $checkSaleDetailsService;
        $this->returnItems = collect($checkSaleDetailsService->saleData->return_items);
        $this->saleReturnMismatches = collect([]);

        $this->returnedSaleItems = $this->getReturnedSaleItems(
            $this->returnItems->pluck('sale_item_id')->unique()->toArray()
        );

        $returnReasonIds = $this->getReturnReasonIds();
        $this->saleReturnReasons = $this->getSaleReturnReasons($returnReasonIds);

        $batchNumbers = $this->getReturnBatchNumbers();
        $this->batches = $this->getBatches($batchNumbers);
    }

    public function getReturnedSaleItems(array $saleItemIds): Collection
    {
        if ($this->hasReturnItems()) {
            $saleItemQueries = resolve(SaleItemQueries::class);

            return $saleItemQueries->getByIdsWithRelations($saleItemIds);
        }

        return collect([]);
    }

    /**
     * @return mixed[]
     */
    public function getReturnReasonIds(): array
    {
        return $this->returnItems->pluck('sale_return_details')
            ->collapse()
            ->pluck('sale_return_reason_id')
            ->unique()
            ->toArray();
    }

    public function getSaleReturnReasons(array $saleReturnReasonIds): Collection
    {
        if ($this->hasReturnItems()) {
            $saleReturnReasonQueries = resolve(SaleReturnReasonQueries::class);

            return $saleReturnReasonQueries->getByIdsAndCompanyId(
                $saleReturnReasonIds,
                $this->checkSaleDetailsService->companyId
            );
        }

        return collect([]);
    }

    /**
     * @return mixed[]
     */
    public function getReturnBatchNumbers(): array
    {
        return $this->returnItems->pluck('sale_return_details')
            ->collapse()
            ->pluck('batch_number')
            ->unique()
            ->toArray();
    }

    public function getBatches(array $batchNumbers): Collection
    {
        if ($this->hasReturnItems()) {
            $batchQueries = resolve(BatchQueries::class);

            return $batchQueries->getByNumbers($batchNumbers, $this->checkSaleDetailsService->companyId);
        }

        return collect([]);
    }

    public function checkReturnItems(int $newLocationId, bool $companyAllowExchangeToDifferentStore): void
    {
        if ($this->returnedSaleItems->pluck('sale_id')->unique()->count() !== 1) {
            abort(412, 'You cannot return items from multiple sales in a single request');
        }

        $sale = $this->returnedSaleItems->first()->sale;

        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        $locationId = $counterUpdateQueries->getStoreIdByCounterUpdateId($sale->counter_update_id);

        if (! $companyAllowExchangeToDifferentStore && $newLocationId !== $locationId) {
            CommonFunctions::addMismatchOrAbort(
                $this->saleReturnMismatches,
                'Return or Exchange is not allowed when company has set allow_exchange_to_different_store to false'
            );
        }

        if ($sale->status === SaleStatus::PENDING_LAYAWAY_SALE->value && $sale->layaway_pending_amount > 0) {
            CommonFunctions::addMismatchOrAbort(
                $this->saleReturnMismatches,
                'Pending Layaway sale cannot be returned.'
            );
        }

        if ($sale->status === SaleStatus::PENDING_CREDIT_SALE->value) {
            CommonFunctions::addMismatchOrAbort(
                $this->saleReturnMismatches,
                'Pending Credit sale cannot be returned.'
            );
        }

        if ($sale->status === SaleStatus::VOID_SALE->value) {
            CommonFunctions::addMismatchOrAbort($this->saleReturnMismatches, 'Void sale cannot be returned.');
        }

        $returnReasonIds = $this->getReturnReasonIds();

        if (
            $this->saleReturnReasons->count()
            !== count($returnReasonIds)
        ) {
            abort(412, 'Some of the sale return reasons are not available in our records.');
        }

        $salesReturnDaysLimit = $this->checkSaleDetailsService->location->sales_return_days_limit;

        if ($salesReturnDaysLimit > 0) {
            /** @var Carbon $returnDate */
            $returnDate = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $this->checkSaleDetailsService->saleData->happened_at
            );

            $saleDate = $sale->happened_at;
            $saleAndSaleReturnDifferentDays = $returnDate->diffInDays($saleDate);

            if ($saleAndSaleReturnDifferentDays > $salesReturnDaysLimit) {
                CommonFunctions::addMismatchOrAbort(
                    $this->saleReturnMismatches,
                    'Sale cannot be returned after ' . $salesReturnDaysLimit . ' days.'
                );
            }
        }

        $this->checkCashbackApplied($sale);

        $this->checkLoyaltyPointsAsPaymentTypeInOriginalSale($sale->payments);

        $saleQueries = resolve(SaleQueries::class);
        $sale = $saleQueries->loadSaleItems($sale);

        $this->checkCartWideDiscountOnOriginalSale($sale);

        $this->checkAllowOnlyReturn();

        foreach ($this->returnItems as $returnItem) {
            $returnedSaleItem = $this->returnedSaleItems->firstWhere('id', $returnItem['sale_item_id']);

            /** @var Product $product */
            $product = $returnedSaleItem->product;

            if (
                $product->type_id !== ProductTypes::REGULAR_PRODUCT->value
                && $product->type_id !== ProductTypes::ASSEMBLY_PRODUCT->value
                && $product->type_id !== ProductTypes::SERIAL_PRODUCT->value
            ) {
                CommonFunctions::addMismatchOrAbort(
                    $this->saleReturnMismatches,
                    'Returns are not permitted for non-regular items.'
                );
            }

            $this->checkExchangeProductOnOriginalSale($returnedSaleItem);

            $this->checkItemWiseDiscountOnOriginalSale($sale, $returnedSaleItem);

            if ($returnedSaleItem->product->has_batch || $product->type_id === ProductTypes::SERIAL_PRODUCT->value) {
                foreach ($returnItem['sale_return_details'] as $returnItemDetails) {
                    if ($returnedSaleItem->product->type_id === ProductTypes::SERIAL_PRODUCT->value) {
                        $this->checkSerialNumber($product, $returnItemDetails);
                    }

                    if (! $returnedSaleItem->product->has_batch) {
                        continue;
                    }

                    if (! $this->hasBatchNumber($returnItemDetails)) {
                        abort(
                            412,
                            'Batch number is required for the specified product (Name: ' . $returnedSaleItem->product->name . ').'
                        );
                    }

                    $batch = $this->batches
                        ->where('product_id', $returnedSaleItem->product->id)
                        ->firstWhere('number', $returnItemDetails['batch_number']);

                    if (! $batch) {
                        abort(412, $returnItemDetails['batch_number'] . ' is not available in our records');
                    }

                    $saleItemUnits = $returnedSaleItem->saleItemUnits->where('batch_id', $batch->id);

                    $productBoxUnits = $returnedSaleItem->product_box_units > 0 ? $returnedSaleItem->product_box_units : 1;

                    $availableUnitQuantities = CommonFunctions::numberFormat(
                        (
                            $saleItemUnits->sum('quantity')
                            - $saleItemUnits->sum('returned_quantity')
                        )
                        / $productBoxUnits
                    );

                    if ($returnItemDetails['quantity'] > $availableUnitQuantities) {
                        CommonFunctions::addMismatchOrAbort(
                            $this->saleReturnMismatches,
                            'Number of units sold for the specified product for the return named: ' . $returnedSaleItem->product->name . ' is only ' . $availableUnitQuantities . '. But requested return quantities are ' . $returnItemDetails['quantity'] . '.'
                        );
                    }
                }
            }

            $availableQuantities = $returnedSaleItem['quantity'] - $returnedSaleItem['returned_quantity'];

            if ($returnItem['quantity'] > $availableQuantities) {
                CommonFunctions::addMismatchOrAbort(
                    $this->saleReturnMismatches,
                    'Only ' . $availableQuantities . ' units can be given for return. But requested return quantities are ' . $returnItem['quantity'] . '.'
                );
            }

            /** @var array $saleReturnDetails */
            $saleReturnDetails = $returnItem['sale_return_details'];
            if (
                (float) collect($saleReturnDetails)->pluck('quantity')->sum()
                !== (float) $returnItem['quantity']
            ) {
                CommonFunctions::addMismatchOrAbort($this->saleReturnMismatches, 'Sale Return total quantity mismatch');
            }

            if (
                ! CommonFunctions::compareFloatNumbers(
                    (float) $returnedSaleItem->price_paid_per_unit,
                    (float) $returnItem['price_paid_per_unit']
                )
            ) {
                CommonFunctions::addMismatchOrAbort($this->saleReturnMismatches, 'Return Item Price mismatched.');
            }
        }
    }

    public function checkCashbackApplied(Sale $sale): void
    {
        if (! $this->checkSaleDetailsService->company->allow_only_return) {
            return;
        }

        if (! $sale->cashback) {
            return;
        }

        if ($this->areAllOfTheReturnItemsBeingExchanged()) {
            return;
        }

        CommonFunctions::addMismatchOrAbort(
            $this->saleReturnMismatches,
            'You cannot return items because cashback was applied in the respective sale. You can just exchange the items.'
        );
    }

    public function checkLoyaltyPointsAsPaymentTypeInOriginalSale(Collection $payments): void
    {
        if (! $this->checkSaleDetailsService->company->allow_only_return) {
            return;
        }

        if (! $this->hasLoyaltyPointsAsPaymentTypeInOriginalSale($payments)) {
            return;
        }

        CommonFunctions::addMismatchOrAbort(
            $this->saleReturnMismatches,
            'You cannot return items of the sale that used loyalty points as payment. You can just exchange the items.'
        );
    }

    public function checkAllowOnlyReturn(): void
    {
        if ($this->checkSaleDetailsService->company->allow_only_return) {
            return;
        }

        if ($this->checkSaleDetailsService->hasCartItems()) {
            return;
        }

        CommonFunctions::addMismatchOrAbort(
            $this->saleReturnMismatches,
            'You cannot return items. You can just exchange the items or return items with purchase new items.'
        );
    }

    public function getReturnItemsSubtotal(): float
    {
        $subtotal = 0.00;
        foreach ($this->returnItems as $returnItem) {
            $returnedSaleItem = $this->returnedSaleItems->firstWhere('id', $returnItem['sale_item_id']);

            $subtotal += CommonFunctions::numberFormat(
                $returnedSaleItem->price_paid_per_unit * $returnItem['quantity']
            );
        }

        return $subtotal;
    }

    public function hasReturnItems(): bool
    {
        return $this->returnItems->isNotEmpty();
    }

    public function isProductBeingExchanged(int $saleItemId): bool
    {
        /** @var SaleItem $returnedSaleItem */
        $returnedSaleItem = $this->returnedSaleItems->firstWhere('id', $saleItemId);
        $returnItem = $this->returnItems->firstWhere('sale_item_id', $saleItemId);

        $exchangeCartItem = $this->getExchangeItem($returnedSaleItem);

        if (null === $exchangeCartItem) {
            return false;
        }

        return CommonFunctions::compareFloatNumbers(
            (float) $exchangeCartItem['quantity'],
            (float) $returnItem['quantity']
        );
    }

    public function areAllOfTheReturnItemsBeingExchanged(): bool
    {
        foreach ($this->returnItems as $returnItem) {
            if (! $this->isProductBeingExchanged((int) $returnItem['sale_item_id'])) {
                return false;
            }
        }

        return true;
    }

    public function areAllTheGroupItemsBeingReturned(Sale $sale, ?int $groupId): bool
    {
        $saleItems = $sale->saleItems->where('group_id', $groupId);
        foreach ($saleItems as $saleItem) {
            $returnItem = $this->returnItems->firstWhere('sale_item_id', $saleItem->id);
            if (! $returnItem) {
                return false;
            }

            if ($saleItem->returned_quantity > 0) {
                return false;
            }

            if ($this->isProductBeingExchanged($saleItem->id)) {
                return false;
            }

            if (! CommonFunctions::compareFloatNumbers($saleItem->quantity, (float) $returnItem['quantity'])) {
                return false;
            }
        }

        return true;
    }

    public function areAllOfTheSaleItemsBeingReturned(Sale $sale): bool
    {
        foreach ($sale->saleItems as $saleItem) {
            if ($saleItem->returned_quantity > 0) {
                return false;
            }

            $returnItem = $this->returnItems->firstWhere('sale_item_id', $saleItem->id);
            if (! $returnItem) {
                return false;
            }

            if ($this->isProductBeingExchanged($saleItem->id)) {
                return false;
            }

            if (! CommonFunctions::compareFloatNumbers(
                (float) $saleItem->quantity,
                (float) $returnItem['quantity']
            )) {
                return false;
            }
        }

        return true;
    }

    public function hasLoyaltyPointsAsPaymentTypeInOriginalSale(Collection $payments): bool
    {
        foreach ($payments as $payment) {
            if ($payment->payment_type_id !== StaticPaymentTypes::LOYALTY_POINT->value) {
                continue;
            }

            if ($this->areAllOfTheReturnItemsBeingExchanged()) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function checkCartWideDiscountOnOriginalSale(Sale $sale): void
    {
        if (! $this->checkSaleDetailsService->company->allow_only_return) {
            return;
        }

        foreach ($sale->saleDiscounts as $saleDiscount) {
            if (
                DiscountableTypes::getDiscountableClass(DiscountableTypes::PROMOTION->value)
                !== $saleDiscount->discountable_type
            ) {
                continue;
            }

            if ($this->areAllOfTheReturnItemsBeingExchanged()) {
                continue;
            }

            if ($this->areAllOfTheSaleItemsBeingReturned($sale)) {
                continue;
            }

            CommonFunctions::addMismatchOrAbort(
                $this->saleReturnMismatches,
                'You cannot return items when the cart-wide promotion is applied to the original sale. You can just exchange the items or return all sale items.'
            );
        }
    }

    public function checkExchangeProductOnOriginalSale(SaleItem $saleItem): void
    {
        if (! $this->checkSaleDetailsService->company->allow_only_return) {
            return;
        }

        if (! $saleItem->is_exchange) {
            return;
        }

        if ($this->isProductBeingExchanged($saleItem->getKey())) {
            return;
        }

        CommonFunctions::addMismatchOrAbort(
            $this->saleReturnMismatches,
            'You cannot return exchanged items. You can just exchange the items.'
        );
    }

    public function checkItemWiseDiscountOnOriginalSale(Sale $sale, SaleItem $saleItem): void
    {
        if (! $this->checkSaleDetailsService->company->allow_only_return) {
            return;
        }

        if ($this->areAllOfTheSaleItemsBeingReturned($sale)) {
            return;
        }

        if ($this->areAllOfTheReturnItemsBeingExchanged()) {
            return;
        }

        foreach ($saleItem->saleItemDiscounts as $saleItemDiscount) {
            if (
                EnumsDiscountableTypes::getDiscountableClass(EnumsDiscountableTypes::PROMOTION->value)
                !== $saleItemDiscount->discountable_type
            ) {
                continue;
            }

            $promotion = $saleItemDiscount->discountable;

            if ($promotion->item_wise_promotion_type_id === ItemWisePromotionTypes::LIMITED_TO_PRODUCTS->value) {
                continue;
            }

            if ($promotion->item_wise_promotion_type_id === ItemWisePromotionTypes::LIMITED_TO_CATEGORIES->value) {
                continue;
            }

            if ($promotion->item_wise_promotion_type_id === ItemWisePromotionTypes::GIFT_WITH_PURCHASE->value) {
                CommonFunctions::addMismatchOrAbort(
                    $this->saleReturnMismatches,
                    'You cannot return items when the Gift With Purchase promotion is applied to the original sale. You can just exchange the items or return all sale items.'
                );
            }

            if ($this->isProductBeingExchanged($saleItem->getKey())) {
                continue;
            }

            if ($this->areAllTheGroupItemsBeingReturned($sale, $saleItem->group_id)) {
                continue;
            }

            CommonFunctions::addMismatchOrAbort(
                $this->saleReturnMismatches,
                'You cannot return partial items from the promotion of the original sale. You can either exchange the partial items or return all of the items from this promotion.'
            );
        }
    }

    public function hasBatchNumber(array $returnItem): bool
    {
        return array_key_exists('batch_number', $returnItem) && $returnItem['batch_number'];
    }

    public function getExchangeItemsTotal(): float
    {
        $returnItemsTotal = 0.00;
        foreach ($this->returnItems as $returnItem) {
            if (! $this->isProductBeingExchanged((int) $returnItem['sale_item_id'])) {
                continue;
            }

            $returnItemsTotal += (float) ($returnItem['quantity'] * $returnItem['price_paid_per_unit']);
        }

        return $returnItemsTotal;
    }

    public function checkRoundOffValue(): void
    {
        $saleReturnRoundOffAmount = $this->checkSaleDetailsService->saleData->sale_return_round_off_amount;

        if (null !== $saleReturnRoundOffAmount) {
            return;
        }

        CommonFunctions::addMismatchOrAbort(
            $this->saleReturnMismatches,
            'We regret to inform you that the round-off amount for the sale return has not been specified or included.'
        );
    }

    public function checkSerialNumber(Product $product, array $returnItemDetails): void
    {
        if (
            $product->type_id !== ProductTypes::SERIAL_PRODUCT->value && $this->hasSerialNumberAttached(
                $returnItemDetails
            )
        ) {
            $saleMismatchMessage = 'Serial Number is not required for one of the selected return product type.';
            CommonFunctions::addMismatchOrAbort($this->saleReturnMismatches, $saleMismatchMessage);

            return;
        }

        if (
            $product->type_id === ProductTypes::SERIAL_PRODUCT->value && ! $this->hasSerialNumberAttached(
                $returnItemDetails
            )
        ) {
            abort(412, 'Serial Number is required for one of the selected return product type');
        }

        $serialNumberQueries = resolve(SerialNumberQueries::class);
        $serialNumberExists = $serialNumberQueries->checkSerialNumberBySoldStatus(
            $product->id,
            $this->checkSaleDetailsService->companyId,
            $returnItemDetails['serial_number']
        );

        if ($serialNumberExists) {
            return;
        }

        abort(412, $returnItemDetails['serial_number'] . ' specified serial number is already return or not exists.');
    }

    public function hasSerialNumberAttached(array $cartItem): bool
    {
        return array_key_exists('serial_number', $cartItem) && $cartItem['serial_number'];
    }

    private function getExchangeItem(SaleItem $returnedSaleItem): ?array
    {
        /** @var Product $returnItemProduct */
        $returnItemProduct = $returnedSaleItem->product;

        $exchangeCartItems = $this->checkSaleDetailsService->cartItems
            ->where('is_exchange', true);

        foreach ($exchangeCartItems as $exchangeCartItem) {
            /** @var Product $product */
            $product = $this->checkSaleDetailsService->products
                ->firstWhere('id', $exchangeCartItem['id']);

            if ($returnItemProduct->id === $product->id) {
                return $exchangeCartItem;
            }

            if (! $product->article_number) {
                continue;
            }

            if (! $returnItemProduct->article_number) {
                continue;
            }

            if ($returnItemProduct->article_number !== $product->article_number) {
                continue;
            }

            return $exchangeCartItem;
        }

        return null;
    }
}
