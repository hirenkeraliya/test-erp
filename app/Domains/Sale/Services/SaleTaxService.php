<?php

declare(strict_types=1);

namespace App\Domains\Sale\Services;

use App\CommonFunctions;

class SaleTaxService
{
    public CheckSaleDetailsService $checkSaleDetailsService;

    public function setDetails(CheckSaleDetailsService $checkSaleDetailsService): void
    {
        $this->checkSaleDetailsService = $checkSaleDetailsService;
    }

    public function getTotalTaxAmountFor(float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.00;
        }

        return CommonFunctions::numberFormat(
            $subtotal * $this->checkSaleDetailsService->location->sales_tax_percentage / 100
        );
    }

    public function getItemTaxAmountFor(float $itemSubtotal, float $totalTax, float $cartSubtotal): float
    {
        if ($cartSubtotal <= 0) {
            return 0.00;
        }

        return CommonFunctions::numberFormat($itemSubtotal * $totalTax / $cartSubtotal);
    }

    public function checkTaxDetails(float $calculatedTotalTaxAmount): void
    {
        if (null === $this->checkSaleDetailsService->saleData->total_tax_amount) {
            $saleMismatchMessage = 'Tax is required but not specified.';
            CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);

            return;
        }

        if (! CommonFunctions::compareFloatNumbers(
            $calculatedTotalTaxAmount,
            $this->checkSaleDetailsService->saleData->total_tax_amount
        )) {
            $saleMismatchMessage = 'Tax mismatch. Expected: ' . $calculatedTotalTaxAmount . '; ' .
                'Received: ' . $this->checkSaleDetailsService->saleData->total_tax_amount;
            CommonFunctions::addMismatchOrAbort($this->checkSaleDetailsService->saleMismatches, $saleMismatchMessage);
        }
    }
}
