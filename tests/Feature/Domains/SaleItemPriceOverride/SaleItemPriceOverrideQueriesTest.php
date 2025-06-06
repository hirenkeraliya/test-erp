<?php

declare(strict_types=1);

use App\Domains\Common\Enums\NegotiatorTypes;
use App\Domains\SaleItemPriceOverride\SaleItemPriceOverrideQueries;
use App\Models\Cashier;
use App\Models\SaleItem;

test('Sale item price override can be added', function (): void {
    $saleItem = SaleItem::factory()->create();

    $cashier = Cashier::factory()->create();

    $saleItemPriceOverrideQueries = new SaleItemPriceOverrideQueries();
    $negotiatorType = NegotiatorTypes::getNegotiatorClass(NegotiatorTypes::CASHIER->value);
    $response = $saleItemPriceOverrideQueries->addNew($saleItem->id, $cashier->id, $negotiatorType, 10.20);

    $this->assertDatabaseHas('sale_item_price_overrides', [
        'sale_item_id' => $saleItem->id,
        'negotiator_id' => $cashier->id,
        'negotiator_type' => $negotiatorType,
        'override_price' => 10.20,
    ]);

    expect($response->toArray())
        ->toHaveKey('sale_item_id', $saleItem->id)
        ->toHaveKey('negotiator_id', $cashier->id)
        ->toHaveKey('negotiator_type', $negotiatorType)
        ->toHaveKey('override_price', 10.20);
});
