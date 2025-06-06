<?php

declare(strict_types=1);

namespace App\Domains\Order\Services;

use App\CommonFunctions;

class OrderTaxService
{
    public CheckOrderDetailsService $checkOrderDetailsService;

    public function setDetails(CheckOrderDetailsService $checkOrderDetailsService): void
    {
        $this->checkOrderDetailsService = $checkOrderDetailsService;
    }

    public function getTotalTaxAmountFor(float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.00;
        }

        return CommonFunctions::numberFormat(
            $subtotal * $this->checkOrderDetailsService->location->sales_tax_percentage / 100
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
        if (null === $this->checkOrderDetailsService->orderData->total_tax_amount) {
            abort(412, 'Tax is required but not specified.');
        }

        if (! CommonFunctions::compareFloatNumbers(
            $calculatedTotalTaxAmount,
            $this->checkOrderDetailsService->orderData->total_tax_amount
        )) {
            abort(412, 'Tax mismatch. Expected: ' . $calculatedTotalTaxAmount . '; ' .
                'Received: ' . $this->checkOrderDetailsService->orderData->total_tax_amount);
        }
    }
}
