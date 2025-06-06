<?php

declare(strict_types=1);

use App\CommonFunctions;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sale\Services\CheckSaleDetailsService;
use App\Domains\Sale\Services\SaleTaxService;
use App\Models\Location;

beforeEach(function (): void {
    $this->saleTaxService = new SaleTaxService();
    $this->checkSaleDetailsService = new CheckSaleDetailsService();

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);
});

test('setDetails method works as expected', function (): void {
    $this->saleTaxService->setDetails($this->checkSaleDetailsService);

    $this->assertTrue($this->saleTaxService->checkSaleDetailsService === $this->checkSaleDetailsService);
});

test(
    'getTotalTaxAmountFor method returns total tax based on specified subtotal',
    function (float $subtotal, float $taxRate): void {
        $this->location->sales_tax_percentage = $taxRate;
        $this->checkSaleDetailsService->location = $this->location;
        $this->saleTaxService->checkSaleDetailsService = $this->checkSaleDetailsService;
        $response = $this->saleTaxService->getTotalTaxAmountFor($subtotal);

        $this->assertTrue(CommonFunctions::numberFormat($subtotal * $taxRate / 100) === $response);
    }
)->with([[111.12, 6.66], [222.29, 9.97], [333, 2]]);

test(
    'getItemTaxAmountFor method returns the tax amount for the specified item',
    function (float $itemSubtotal, float $totalTax, float $cartSubtotal): void {
        $response = $this->saleTaxService->getItemTaxAmountFor($itemSubtotal, $totalTax, $cartSubtotal);

        $this->assertTrue(CommonFunctions::numberFormat($itemSubtotal * $totalTax / $cartSubtotal) === $response);
    }
)->with([[33, 60, 200], [23.33, 99.98, 231.21]]);
