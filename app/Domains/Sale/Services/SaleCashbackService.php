<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;
use App\Domains\Cashback\CashbackQueries;
use App\Domains\Cashback\Enums\ConditionTypes;
use App\Domains\Cashback\Enums\ExcludeByTypes;
use App\Domains\CashMovement\CashMovementQueries;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\SaleCashback\SaleCashbackQueries;
use App\Models\Cashback;
use App\Models\Sale;
use Carbon\Carbon;

class SaleCashbackService
{
    public CheckSaleDetailsService $checkSaleDetailsService;

    public Cashback $cashback;

    public function setDetails(CheckSaleDetailsService $checkSaleDetailsService): void
    {
        $this->checkSaleDetailsService = $checkSaleDetailsService;
        $this->cashback = $this->getCashback();
    }

    public function getCashback(): Cashback
    {
        /** @var int $cashbackId */
        $cashbackId = $this->checkSaleDetailsService->saleData->cashback_id;
        $cashbackQueries = resolve(CashbackQueries::class);

        return $cashbackQueries->getByIdWithRelations($cashbackId, $this->checkSaleDetailsService->companyId);
    }

    public function checkForApplicability(float $subtotal): void
    {
        if ($this->checkSaleDetailsService->isLayawaySale()) {
            $saleMismatchMessage = 'Cashback cannot be generated for Layaway Sales.';
            CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        if ($this->checkSaleDetailsService->isCreditSale()) {
            $saleMismatchMessage = 'Cashback cannot be generated for Credit Sales.';
            CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $this->checkSaleDetailsService->saleData->happened_at
        );
        $happenedAt = $happenedAtFormat->format('Y-m-d');

        if ($this->cashback->start_date > $happenedAt || $this->cashback->end_date < $happenedAt) {
            $saleMismatchMessage = 'Specified cashback is available between ' . $this->cashback->start_date . ' to ' . $this->cashback->end_date . ' only. The sale date is ' . $happenedAt . '.';
            CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        if (
            $this->cashback->locations->isNotEmpty() &&
            ! $this->cashback->locations->firstWhere('id', $this->checkSaleDetailsService->location->id)
        ) {
            $saleMismatchMessage = 'Specified cashback is not available for the location ' . $this->checkSaleDetailsService->location->name;
            CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $totalAmountAfterExclude = $subtotal - $this->getExcludeAmountForCashback();

        if ((float) $this->cashback->minimum_spend_amount > $totalAmountAfterExclude) {
            $saleMismatchMessage = 'Minimum spend amount for selected cashback is ' . $this->cashback->minimum_spend_amount . '. But, the cart total is ' . $totalAmountAfterExclude . '.';
            CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }

        $actualCashbackAmount = $this->getCashbackAmount($totalAmountAfterExclude);
        $cashbackAmount = (float) $this->checkSaleDetailsService->saleData->cashback_amount;
        $cashbackRoundingAmount = (float) $this->checkSaleDetailsService->saleData->cashback_round_off_amount;
        $actualCashbackAmount = CommonFunctions::numberFormat($actualCashbackAmount + $cashbackRoundingAmount);

        if ($cashbackAmount !== $actualCashbackAmount) {
            $saleMismatchMessage = 'Cashback amount mismatched. The expected cashback amount is ' . $actualCashbackAmount . '. And given cashback amount is ' . $cashbackAmount . '.';
            CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }
    }

    public function saveCashback(Sale $sale, int $counterUpdateId): void
    {
        $cashbackAmount = (float) $this->checkSaleDetailsService->saleData->cashback_amount;
        $cashbackRoundingAmount = (float) $this->checkSaleDetailsService->saleData->cashback_round_off_amount;

        $cashMovementQueries = resolve(CashMovementQueries::class);
        $cashMovementId = $cashMovementQueries->addNewForCashback(
            $this->checkSaleDetailsService->saleData->offline_sale_id,
            $counterUpdateId,
            $cashbackAmount,
            $this->checkSaleDetailsService->saleData->happened_at,
        );

        $saleCashbackQueries = resolve(SaleCashbackQueries::class);
        $saleCashbackQueries->addNew(
            $sale->getKey(),
            $this->cashback->id,
            $cashbackAmount,
            $cashbackRoundingAmount,
            $this->checkSaleDetailsService->saleData->happened_at,
            $cashMovementId,
        );
    }

    public function getExcludeAmountForCashback(): float
    {
        $amountToExclude = 0.0;
        foreach ($this->checkSaleDetailsService->cartItems as $cartItem) {
            $product = $this->checkSaleDetailsService->products->firstWhere('id', $cartItem['id']);
            if ($this->cashback->exclude_by_type === ExcludeByTypes::CATEGORIES->value) {
                $cashbackExcludeCategoryIds = $this->cashback->categories->pluck('id');
                $productCategoryIds = $product->categories->pluck('id');
                $isValidProductAccordingToCategories = $cashbackExcludeCategoryIds->intersect($productCategoryIds);

                if ($isValidProductAccordingToCategories->isNotEmpty()) {
                    $amountToExclude += $cartItem['total_price_paid'];
                }
            }

            if ($this->cashback->exclude_by_type === ExcludeByTypes::ORIGINAL_ITEM_PRICE->value) {
                $isValidProductAccordingToOriginalItemPrice = $this->checkPriceCondition(
                    $this->cashback,
                    (float) $cartItem['price']
                );
                if ($isValidProductAccordingToOriginalItemPrice) {
                    $amountToExclude += $cartItem['total_price_paid'];
                }
            }

            if ($this->cashback->exclude_by_type === ExcludeByTypes::DISCOUNT_ITEM_PRICE->value) {
                $isValidProductAccordingToDiscountItemPrice = $this->checkPriceCondition(
                    $this->cashback,
                    (float) $cartItem['total_price_paid']
                );

                if ($isValidProductAccordingToDiscountItemPrice) {
                    $amountToExclude += $cartItem['total_price_paid'];
                }
            }

            if ($this->cashback->exclude_by_type !== ExcludeByTypes::PRODUCTS->value) {
                continue;
            }

            if (! $this->cashback->products->firstWhere('id', $cartItem['id'])) {
                continue;
            }

            $amountToExclude += $cartItem['total_price_paid'];
        }

        return $amountToExclude;
    }

    public function getCashbackAmount(float $totalAmountAfterExclude, float $totalCashbackAmount = 0): float
    {
        if ($this->cashback->discount_type_id === DiscountTypes::PERCENTAGE->value) {
            return CommonFunctions::numberFormat($totalAmountAfterExclude * $this->cashback->discount_value / 100);
        }

        if ($this->cashback->minimum_spend_amount <= $totalAmountAfterExclude) {
            $totalCashbackAmount += $this->cashback->discount_value;
            $totalAmountAfterExclude -= $this->cashback->minimum_spend_amount;

            return $this->getCashbackAmount($totalAmountAfterExclude, $totalCashbackAmount);
        }

        return $totalCashbackAmount;
    }

    private function checkPriceCondition(Cashback $cashback, float $amount): bool
    {
        foreach ($cashback->cashbackPrices as $cashbackPrice) {
            $cashbackPriceAmount = (float) $cashbackPrice->amount;
            $condition = ' ';
            if ($cashbackPrice->condition_operator_type_id === ConditionTypes::LESS_THAN->value) {
                $condition = '<';
            }

            if ($cashbackPrice->condition_operator_type_id === ConditionTypes::GREATER_THAN->value) {
                $condition = '>';
            }

            if ($cashbackPrice->condition_operator_type_id === ConditionTypes::EQUAL->value) {
                $condition = '===';
            }

            // Evaluate the condition with the total_price_paid
            $expression = sprintf('%s %s %s', $amount, $condition, $cashbackPriceAmount);

            // If any condition fails, the item exclude scenario does not match and it's eligible for the cashback
            if (! eval(sprintf('return %s;', $expression))) {
                return false;
            }
        }

        // If all condition true, the item exclude scenario match and it's not eligible for the cashback
        return true;
    }
}
