<?php

declare(strict_types=1);

use App\Domains\HoldSaleReturnItem\HoldSaleReturnItemQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\HoldSaleDetail;
use App\Models\HoldSaleReturnItem;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturnReason;

beforeEach(function (): void {
    $this->holdSaleReturnItemQueries = new HoldSaleReturnItemQueries();
});

test('new hold sale return item can be added', function (): void {
    $holdSaleDetail = HoldSaleDetail::factory()->create();
    $product = Product::factory()->create();
    $saleItem = SaleItem::factory()->create();
    $saleReturnReason = SaleReturnReason::factory()->create();

    $item = [
        'product_id' => $product->id,
        'sale_item_id' => $saleItem->id,
        'sale_return_reason_id' => $saleReturnReason->id,
        'price' => 10,
        'quantity' => '1',
    ];

    $this->holdSaleReturnItemQueries->addNew($holdSaleDetail->id, $item);

    $this->assertDatabaseHas('hold_sale_return_items', [
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

    HoldSaleReturnItem::factory()->create([
        'product_id' => $productBId,
        'sale_item_id' => $saleItem->id,
    ]);

    $this->holdSaleReturnItemQueries->updateProductId($companyId, $productBId, $productAId);

    $this->assertDatabaseHas('hold_sale_return_items', [
        'sale_item_id' => $saleItem->id,
        'product_id' => $productAId,
    ]);
});
