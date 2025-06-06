<?php

declare(strict_types=1);

use App\Domains\AssemblyProduct\AssemblyChildProductQueries;
use App\Models\AssemblyChildProduct;
use App\Models\Product;

beforeEach(function (): void {
    $this->assemblyChildProductQueries = new AssemblyChildProductQueries();
});

test('assembly product can be added', function (): void {
    $product = Product::factory()->create()->id;
    $productA = Product::factory()->create()->id;

    $assemblyChildProductRecord = AssemblyChildProduct::factory()->make([
        'product_id' => $product,
        'child_product_id' => $productA,
        'units' => 20,
    ]);

    $this->assemblyChildProductQueries->addNew($assemblyChildProductRecord->toArray());

    $this->assertDatabaseHas('assembly_child_products', [
        'product_id' => $assemblyChildProductRecord->product_id,
        'child_product_id' => $assemblyChildProductRecord->child_product_id,
        'units' => $assemblyChildProductRecord->units,
    ]);
});
