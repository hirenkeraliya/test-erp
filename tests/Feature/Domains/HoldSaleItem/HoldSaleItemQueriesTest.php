<?php

declare(strict_types=1);

use App\Domains\HoldSaleItem\HoldSaleItemQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\HoldSaleDetail;
use App\Models\HoldSaleItem;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;

beforeEach(function (): void {
    $this->holdSaleItemQueries = new HoldSaleItemQueries();
});

test('new hold sale item can be added', function (): void {
    $holdSaleDetail = HoldSaleDetail::factory()->create();
    $product = Product::factory()->create();

    $item = [
        'id' => $product->id,
        'price' => 10,
        'quantity' => '1',
    ];

    $this->holdSaleItemQueries->addNew($holdSaleDetail->id, $item);

    $this->assertDatabaseHas('hold_sale_items', [
        'hold_sale_detail_id' => $holdSaleDetail->id,
    ]);
});

test('if product is merged then the product id is updated', function (): void {
    $companyId = Company::factory()->create()->id;
    $locationId = Location::factory()->create([
        'company_id' => $companyId,
        'type_id' => LocationTypes::STORE->value,
    ])->id;
    $counterId = Counter::factory()->create([
        'location_id' => $locationId,
    ])->id;
    $counterUpdateId = CounterUpdate::factory()->create([
        'counter_id' => $counterId,
    ])->id;
    $sale = Sale::factory()->create([
        'counter_update_id' => $counterUpdateId,
    ]);

    $productAId = Product::factory()->create()->id;
    $productBId = Product::factory()->create()->id;

    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $productBId,
    ]);

    HoldSaleItem::factory()->create([
        'product_id' => $productBId,
        'original_sale_item_id' => $saleItem->id,
    ]);

    $this->holdSaleItemQueries->updateProductId($companyId, $productBId, $productAId);

    $this->assertDatabaseHas('hold_sale_items', [
        'original_sale_item_id' => $saleItem->id,
        'product_id' => $productAId,
    ]);
});
