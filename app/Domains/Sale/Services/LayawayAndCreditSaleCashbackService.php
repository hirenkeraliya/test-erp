<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;
use App\Domains\Cashback\CashbackQueries;
use App\Domains\Cashback\Enums\ExcludeByTypes;
use App\Domains\CashMovement\CashMovementQueries;
use App\Domains\Common\Enums\DiscountTypes;
use App\Domains\Sale\DataObjects\CompleteCreditSaleData;
use App\Domains\Sale\DataObjects\CompleteLayawaySaleData;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\SaleCashback\SaleCashbackQueries;
use App\Models\Cashback;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LayawayAndCreditSaleCashbackService
{
    public Cashback $cashback;

    public function setDetails(int $cashbackId, int $companyId): void
    {
        $this->cashback = $this->getCashback($cashbackId, $companyId);
    }

    public function hasCashback(CompleteCreditSaleData|CompleteLayawaySaleData $completeSaleData): bool
    {
        return (
            $completeSaleData->cashback_id && null !== $completeSaleData->cashback_id
        ) && (
            $completeSaleData->cashback_amount && null !== $completeSaleData->cashback_amount
        );
    }

    public function getCashback(int $cashbackId, int $companyId): Cashback
    {
        $cashbackQueries = resolve(CashbackQueries::class);

        return $cashbackQueries->getByIdWithRelations($cashbackId, $companyId);
    }

    public function checkForApplicability(
        float $subtotal,
        CompleteCreditSaleData|CompleteLayawaySaleData $completeSaleData,
        Collection $saleMismatches,
        Location $location,
        Sale $sale
    ): void {
        $happenedAtFormat = $this->getHappenedAt($completeSaleData);
        $happenedAt = $happenedAtFormat->format('Y-m-d');

        if ($this->cashback->start_date > $happenedAt || $this->cashback->end_date < $happenedAt) {
            $saleMismatchMessage = 'Specified cashback is available between ' . $this->cashback->start_date . ' to ' . $this->cashback->end_date . ' only. The sale date is ' . $happenedAt . '.';
            CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
        }

        if (
            $this->cashback->locations->isNotEmpty() &&
            ! $this->cashback->locations->firstWhere('id', $location->id)
        ) {
            $saleMismatchMessage = 'Specified cashback is not available for the location ' . $location->name;
            CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
        }

        $totalAmountAfterExclude = $subtotal - $this->getExcludeAmountForCashback($sale, $subtotal);

        if ((float) $this->cashback->minimum_spend_amount > $totalAmountAfterExclude) {
            $saleMismatchMessage = 'Minimum spend amount for selected cashback is ' . $this->cashback->minimum_spend_amount . '. But, the cart total is ' . $totalAmountAfterExclude . '.';
            CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
        }

        $actualCashbackAmount = $this->getCashbackAmount($totalAmountAfterExclude);

        $cashbackAmount = (float) $completeSaleData->cashback_amount;
        $cashbackRoundingAmount = (float) $completeSaleData->cashback_round_off_amount;

        $actualCashbackAmount = CommonFunctions::numberFormat($actualCashbackAmount + $cashbackRoundingAmount);

        if ($cashbackAmount !== $actualCashbackAmount) {
            $saleMismatchMessage = 'Cashback amount mismatched. The expected cashback amount is ' . $actualCashbackAmount . '. And given cashback amount is ' . $cashbackAmount . '.';
            CommonFunctions::addMismatchOrAbort($saleMismatches, $saleMismatchMessage);
        }
    }

    public function getHappenedAt(CompleteCreditSaleData|CompleteLayawaySaleData $completeSaleData): Carbon
    {
        if ($completeSaleData->happened_at) {
            /** @var Carbon */
            return Carbon::createFromFormat('Y-m-d H:i:s', $completeSaleData->happened_at);
        }

        return now();
    }

    public function saveCashback(
        Sale $sale,
        CompleteCreditSaleData|CompleteLayawaySaleData $completeSaleData,
        int $counterUpdateId
    ): void {
        $cashbackAmount = (float) $completeSaleData->cashback_amount;
        $cashbackRoundingAmount = (float) $completeSaleData->cashback_round_off_amount;

        $happenedAtFormat = $this->getHappenedAt($completeSaleData);
        $happenedAt = $happenedAtFormat->format('Y-m-d H:i:s');

        $cashMovementQueries = resolve(CashMovementQueries::class);
        $cashMovementId = $cashMovementQueries->addNewForCashback(
            $sale->offline_sale_id,
            $counterUpdateId,
            $cashbackAmount,
            $happenedAt,
        );

        $saleCashbackQueries = resolve(SaleCashbackQueries::class);
        $saleCashbackQueries->addNew(
            $sale->getKey(),
            $this->cashback->id,
            $cashbackAmount,
            $cashbackRoundingAmount,
            $happenedAt,
            $cashMovementId,
        );
    }

    public function getExcludeAmountForCashback(Sale $sale, float $totalPaymentAmount): float
    {
        $amountToExclude = 0.0;
        foreach ($sale->saleItems as $saleItem) {
            $totalPricePaid = $this->getTotalPricePaid($sale, $saleItem, $totalPaymentAmount);

            /** @var Product $product */
            $product = $saleItem->product;

            if ($this->cashback->exclude_by_type === ExcludeByTypes::CATEGORIES->value) {
                $cashbackExcludeCategoryIds = $this->cashback->categories->pluck('id');
                $productCategoryIds = $product->categories->pluck('id');
                $isValidProductAccordingToCategories = $cashbackExcludeCategoryIds->intersect($productCategoryIds);

                if ($isValidProductAccordingToCategories->isNotEmpty()) {
                    $amountToExclude += $totalPricePaid;
                }
            }

            if ($this->cashback->exclude_by_type !== ExcludeByTypes::PRODUCTS->value) {
                continue;
            }

            if (! $this->cashback->products->firstWhere('id', $product->id)) {
                continue;
            }

            $amountToExclude += $totalPricePaid;
        }

        return $amountToExclude;
    }

    public function getTotalPricePaid(Sale $sale, SaleItem $saleItem, float $totalPaymentAmount): float
    {
        $totalAmount = $sale->total_amount_paid + $sale->layaway_pending_amount;

        if (SaleStatus::PENDING_CREDIT_SALE->value === $sale->status) {
            $totalAmount = $sale->total_amount_paid + $sale->credit_pending_amount;
        }

        $itemSubtotal = $saleItem->original_price_per_unit * $saleItem->quantity;
        $itemSubtotal -= $saleItem->total_discount_amount;
        $itemSubtotal += $saleItem->total_tax_amount;

        return CommonFunctions::numberFormat($totalPaymentAmount * $itemSubtotal / $totalAmount);
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
}
