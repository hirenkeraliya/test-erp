<?php

declare(strict_types=1);

use App\Domains\Common\Enums\NegotiatorTypes;
use App\Domains\SalePriceOverride\SalePriceOverrideQueries;
use App\Models\Cashier;
use App\Models\Sale;

test('Sale price override can be added', function (): void {
    $sale = Sale::factory()->create();

    $cashier = Cashier::factory()->create();

    $salePriceOverrideQueries = new SalePriceOverrideQueries();
    $negotiatorType = NegotiatorTypes::getNegotiatorClass(NegotiatorTypes::CASHIER->value);
    $response = $salePriceOverrideQueries->addNew($sale->id, $cashier->id, $negotiatorType, 10.20);

    $this->assertDatabaseHas('sale_price_overrides', [
        'sale_id' => $sale->id,
        'negotiator_id' => $cashier->id,
        'negotiator_type' => $negotiatorType,
        'override_price' => 10.20,
    ]);

    expect($response->toArray())
        ->toHaveKey('sale_id', $sale->id)
        ->toHaveKey('negotiator_id', $cashier->id)
        ->toHaveKey('negotiator_type', $negotiatorType)
        ->toHaveKey('override_price', 10.20);
});
