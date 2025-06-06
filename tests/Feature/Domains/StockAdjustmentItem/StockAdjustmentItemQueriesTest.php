<?php

declare(strict_types=1);

use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StockAdjustment\Enums\StockAdjustmentReportType;
use App\Domains\StockAdjustmentItem\StockAdjustmentItemQueries;
use App\Models\Company;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;

beforeEach(function (): void {
    $this->companyId = Company::factory()->create()->id;

    $this->location = Location::factory()->create([
        'company_id' => $this->companyId,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $this->stockAdjustmentA = StockAdjustment::factory()->create([
        'reason' => 'stock adjustment 1',
        'company_id' => $this->companyId,
    ]);

    $this->product = Product::factory()->create([
        'company_id' => $this->companyId,
    ]);

    $this->stockAdjustmentItemA = StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $this->stockAdjustmentA->id,
        'location_id' => $this->location->id,
        'product_id' => $this->product->id,
    ]);

    $this->stockAdjustmentItemB = StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $this->stockAdjustmentA->id,
        'location_id' => $this->location->id,
        'product_id' => $this->product->id,
    ]);

    $this->stockAdjustmentItemQueries = new StockAdjustmentItemQueries();
});

test('new stock adjustment item can be added', function (): void {
    $stockAdjustmentId = StockAdjustment::factory()->create()->id;
    $productId = Product::factory()->create()->id;

    $stockAdjustmentProduct = [
        'quantity' => 10,
    ];

    $locationId = $this->location->id;
    $stockAdjustmentItemQueries = new StockAdjustmentItemQueries();
    $stockAdjustmentItemQueries->addNew(
        $stockAdjustmentProduct['quantity'],
        $stockAdjustmentId,
        $productId,
        $locationId,
        null,
        null,
        null,
        null,
        null,
    );

    $this->assertDatabaseHas('stock_adjustment_items', [
        'stock_adjustment_id' => $stockAdjustmentId,
        'product_id' => $productId,
        'location_id' => $locationId,
        'quantity' => 10.00,
    ]);
});

test('if product is merged then the product id is updated', function (): void {
    $productId = Product::factory()->create()->id;

    $stockAdjustmentItemQueries = new StockAdjustmentItemQueries();
    $stockAdjustmentItemQueries->updateProductId($this->companyId, $this->product->id, $productId);

    $this->assertDatabaseHas('stock_adjustment_items', [
        'stock_adjustment_id' => $this->stockAdjustmentA->id,
        'product_id' => $productId,
    ]);
});

test('getItemsByStockAdjustmentId method returns the stock adjustment items with product', function (): void {
    $response = $this->stockAdjustmentItemQueries->getItemsByStockAdjustmentId(
        $this->stockAdjustmentA->id,
        $this->companyId
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $this->stockAdjustmentItemA->id)
        ->toHaveKey('quantity', $this->stockAdjustmentItemA->quantity)
        ->toHaveKey('product.name', $this->product->name)
        ->toHaveKey('location.name', $this->location->name);
});

test('getItemsByDateAndLocations method returns the stock adjustment items', function (): void {
    $this->product->is_non_inventory = false;
    $this->product->save();

    $this->stockAdjustmentA->adjustment_date = now()->format('Y-m-d');
    $this->stockAdjustmentA->save();

    $filterData = [
        'location_ids' => [$this->location->id],
        'stock_adjustment_type' => null,
        'product_id' => null,
        'date_range' => [now()->format('Y-m-d'), now()->format('Y-m-d')],
        'report_type' => StockAdjustmentReportType::BY_SUMMARY->value,
        'filter_by' => null,
        'article_number' => null,
    ];

    $response = $this->stockAdjustmentItemQueries->getItemsByDateAndLocations($filterData, $this->companyId);
    expect($response->first()->toArray())
        ->toHaveKey('id', $this->stockAdjustmentItemA->id)
        ->toHaveKey('stock_adjustment_id', $this->stockAdjustmentItemA->stock_adjustment_id)
        ->toHaveKey('quantity', $this->stockAdjustmentItemA->quantity)
        ->toHaveKey('location_id', $this->stockAdjustmentItemA->location_id)
        ->toHaveKey('product.id', $this->product->id)
        ->toHaveKey('product.name', $this->product->name);
});
