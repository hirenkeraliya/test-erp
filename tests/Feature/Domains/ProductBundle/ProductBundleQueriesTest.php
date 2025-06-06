<?php

declare(strict_types=1);

use App\Domains\BoxProduct\BoxProductQueries;
use App\Domains\Product\Enums\ProductBatches;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\Enums\Statuses;
use App\Models\BoxProduct;
use App\Models\Company;
use App\Models\PackageType;
use App\Models\Product;

beforeEach(function (): void {
    $this->boxProductQueries = new BoxProductQueries();
});

test('a product Box price can be added', function (): void {
    $product = Product::factory()->create()->id;

    $packageType = PackageType::factory()->create()->id;

    $productBoxRecord = BoxProduct::factory()->make([
        'product_id' => $product,
        'package_type_id' => $packageType,
    ]);

    $this->boxProductQueries->addNew($productBoxRecord->toArray());

    $this->assertDatabaseHas('box_products', [
        'product_id' => $productBoxRecord->product_id,
        'package_type_id' => $productBoxRecord->package_type_id,
        'units' => $productBoxRecord->units,
    ]);
});

test('It updateProductId method call and update the product id.', function (): void {
    $product = Product::factory()->create();

    $packageType = PackageType::factory()->create()->id;

    $productBoxRecord = BoxProduct::factory()->create([
        'package_type_id' => $packageType,
    ]);

    $this->boxProductQueries->updateProductId($productBoxRecord->product_id, $product->id);

    $this->assertDatabaseHas('box_products', [
        'id' => $productBoxRecord->id,
        'product_id' => $product->id,
        'package_type_id' => $productBoxRecord->package_type_id,
        'units' => $productBoxRecord->units,
    ]);
});

test('findBoxByIdAndProductId method call and return proper response', function (): void {
    $productId = Product::factory()->create()->id;
    $productBoxRecord = BoxProduct::factory()->create([
        'product_id' => $productId,
    ]);
    $response = $this->boxProductQueries->findBoxByIdAndProductId($productBoxRecord->id, $productId);

    expect($response->toArray())
        ->toHaveKey('id', $productBoxRecord->id);
});

test('getById method call and return proper response', function (): void {
    $productBoxRecord = BoxProduct::factory()->create();
    $response = $this->boxProductQueries->getById($productBoxRecord->id);

    expect($response->toArray())
        ->toHaveKey('id', $productBoxRecord->id)
        ->toHaveKey('package_type_id', $productBoxRecord->package_type_id)
        ->toHaveKey('units', $productBoxRecord->units);
});

test('getBoxProducts method returns the product Box data with relations for export', function (): void {
    $this->companyA = Company::factory()->create([
        'code' => '123456',
        'email' => 'companya@example.com',
        'creator_can_approve_draft_product' => true,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->companyA->id,
        'compound_product_name' => 'XYZ',
        'code' => 'X1234',
        'status' => Statuses::ACTIVE->value,
    ]);

    $packageType = PackageType::factory()->create()->id;

    $productBoxRecord = BoxProduct::factory()->create([
        'product_id' => $product->id,
        'package_type_id' => $packageType,
        'units' => 12,
        'retail_price' => 12,
    ]);

    $response = $this->boxProductQueries->getBoxProducts([
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'status' => ProductStatuses::ACTIVE->value,
        'batch' => ProductBatches::ALL->value,
        'date_range' => null,
        'product_type_id' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'color_ids' => null,
        'size_ids' => null,
        'department_ids' => null,
        'article_numbers' => null,
        'tag_ids' => null,
        'style_ids' => null,
        'product_collection_ids' => null,
    ], $this->companyA->id);

    expect($response->first()->toArray())
        ->toHaveKey('units', $productBoxRecord->units)
        ->toHaveKey('product_id', $productBoxRecord->product_id)
        ->toHaveKey('package_type_id', $productBoxRecord->package_type_id)
        ->toHaveKey('retail_price', $productBoxRecord->retail_price);
});
