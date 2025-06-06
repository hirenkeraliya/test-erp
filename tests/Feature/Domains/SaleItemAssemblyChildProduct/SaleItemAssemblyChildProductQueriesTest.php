<?php

declare(strict_types=1);

use App\Domains\SaleItemAssemblyChildProduct\SaleItemAssemblyChildProductQueries;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\SaleItemAssemblyChildProduct;

beforeEach(function (): void {
    $this->saleItemAssemblyChildProductQueries = new SaleItemAssemblyChildProductQueries();
});

test('sale item assembly product can be added', function (): void {
    $saleItem = SaleItem::factory()->create();
    $productA = Product::factory()->create();

    $saleItemAssemblyChildProduct = SaleItemAssemblyChildProduct::factory()->make([
        'sale_item_id' => $saleItem->id,
        'child_product_id' => $productA->id,
        'units' => 20,
    ]);

    $this->saleItemAssemblyChildProductQueries->addNew($saleItemAssemblyChildProduct->toArray());

    $this->assertDatabaseHas('sale_item_assembly_child_products', [
        'sale_item_id' => $saleItemAssemblyChildProduct->sale_item_id,
        'child_product_id' => $saleItemAssemblyChildProduct->child_product_id,
        'units' => $saleItemAssemblyChildProduct->units,
    ]);
});
