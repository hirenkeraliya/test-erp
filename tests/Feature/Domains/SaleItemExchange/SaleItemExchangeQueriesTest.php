<?php

declare(strict_types=1);

use App\Domains\SaleItemExchange\SaleItemExchangeQueries;
use App\Models\SaleItem;

beforeEach(function (): void {
    $this->saleItemExchangeQueries = new SaleItemExchangeQueries();
});

test('A sale item exchange can be added', function (): void {
    $saleItem = SaleItem::factory()->create();

    $data = [
        'sale_item_id' => $saleItem->id,
        'old_item_price' => 10,
        'current_item_price' => 20,
        'price_difference' => 10,
        'old_discount_amount' => 0.00,
    ];

    $this->saleItemExchangeQueries->addNew($data);

    $this->assertDatabaseHas('sale_item_exchanges', [
        'sale_item_id' => $saleItem->id,
        'old_item_price' => '10.00',
        'current_item_price' => '20.00',
    ]);
});
