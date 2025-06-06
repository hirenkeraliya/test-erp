<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ProductCollection\Enums\LogicalConnectorTypes;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\ProductCollectionProduct;

beforeEach(function (): void {
    $this->companyA = Company::factory()->create([
        'code' => '123456',
        'email' => 'companya@example.com',
    ]);

    $this->productCollection = ProductCollection::factory()->create([
        'company_id' => $this->companyA->id,
        'name' => 'ABC',
        'number_of_products' => 0,
        'pending_products' => 0,
        'logical_connector_type_id' => LogicalConnectorTypes::AND->value,
        'last_sync_at' => now()->format('Y-m-d H:i:s'),
        'status' => true,
        'created_by_type' => ModelMapping::ADMIN->name,
        'created_by_id' => Admin::factory()->create()->id,
    ]);
});

test('call removeByProductId method then remove particular record', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    ProductCollectionProduct::factory()->create([
        'product_collection_id' => $this->productCollection->id,
        'product_id' => $product->id,
    ]);

    $this->assertDatabaseHas('product_collection_products', [
        'product_collection_id' => $this->productCollection->id,
        'product_id' => $product->id,
    ]);

    $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);
    $productCollectionProductQueries->removeByProductId($product->id, $this->companyA->id);

    $this->assertDatabaseMissing('product_collection_products', [
        'product_collection_id' => $this->productCollection->id,
        'product_id' => $product->id,
    ]);
});

test('call removeByProductCollectionId method then remove particular record', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    ProductCollectionProduct::factory()->create([
        'product_collection_id' => $this->productCollection->id,
        'product_id' => $product->id,
    ]);

    $this->assertDatabaseHas('product_collection_products', [
        'product_collection_id' => $this->productCollection->id,
        'product_id' => $product->id,
    ]);

    $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);
    $productCollectionProductQueries->removeByProductCollectionId($this->productCollection->id, $this->companyA->id);

    $this->assertDatabaseMissing('product_collection_products', [
        'product_collection_id' => $this->productCollection->id,
        'product_id' => $product->id,
    ]);
});

test('call addNew method add the product collection product', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $productCollectionProductData = [
        'product_id' => $product->id,
        'product_collection_id' => $this->productCollection->id,
        'is_synced' => true,
    ];

    $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);
    $productCollectionProductQueries->addNew($productCollectionProductData);

    $this->assertDatabaseHas('product_collection_products', [
        'product_collection_id' => $this->productCollection->id,
        'product_id' => $product->id,
    ]);
});

test('call syncByProductCollectionId method and get product collection product', function (): void {
    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $productCollectionProduct = ProductCollectionProduct::factory()->create([
        'product_collection_id' => $this->productCollection->id,
        'product_id' => $product->id,
        'is_synced' => false,
    ]);

    $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);
    $response = $productCollectionProductQueries->syncByProductCollectionId($this->productCollection->id);

    expect($response->first()->toArray())
        ->toHaveKey('id', $productCollectionProduct->id);
});

test('It returns product collection products ids', function (): void {
    $filterData = [
        'search_text' => 'ABC',
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'per_page' => 10,
        'after_updated_at' => null,
        'product_collection_id' => $this->productCollection->id,
    ];

    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
    ]);

    $productCollectionProduct = ProductCollectionProduct::factory()->create([
        'product_collection_id' => $this->productCollection->id,
        'product_id' => $product->id,
        'is_synced' => false,
    ]);

    $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);

    $response = $productCollectionProductQueries->getProductCollectionProducts($filterData);

    expect($response->first()->toArray())
        ->toHaveKey('product_id', $productCollectionProduct->product_id);
});
