<?php

declare(strict_types=1);

use App\Domains\SaleLoyaltyPoint\SaleLoyaltyPointQueries;
use App\Models\Product;

beforeEach(function (): void {
    $this->saleLoyaltyPointQueries = new SaleLoyaltyPointQueries();
});

test('new sale loyalty point can be added', function (): void {
    $product = Product::factory()->create();

    $this->saleLoyaltyPointQueries->addNew(100, 10.20, null, $product->id);

    $this->assertDatabaseHas('sale_loyalty_points', [
        'loyalty_points' => 100,
        'amount' => 10.20,
        'sale_id' => null,
        'product_id' => $product->id,
    ]);
});
