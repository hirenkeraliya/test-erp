<?php

declare(strict_types=1);

use App\Domains\DreamPriceProduct\DreamPriceProductQueries;
use App\Domains\Product\Enums\Statuses;
use App\Models\Company;
use App\Models\DreamPrice;
use App\Models\DreamPriceProduct;
use App\Models\MasterProduct;
use App\Models\Product;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->product = Product::factory()->create();
    $this->dreamPrice = DreamPrice::factory()->create();

    $this->dreamPriceProductQueries = new DreamPriceProductQueries();
});

test('Dream price products can be deleted', function (): void {
    DreamPriceProduct::factory()->create([
        'dream_price_id' => $this->dreamPrice->id,
        'product_id' => $this->product->id,
        'price' => 100.00,
    ]);

    $this->dreamPriceProductQueries->delete($this->dreamPrice);

    $this->assertDatabaseMissing('dream_price_products', [
        'dream_price_id' => $this->dreamPrice->id,
        'product_id' => $this->product->id,
        'price' => 100.00,
    ]);
});

test('It returns dream price products when product variant is false', function (): void {
    Config::set('app.product_variant', false);

    $dreamPriceProduct = DreamPriceProduct::factory()->create([
        'dream_price_id' => $this->dreamPrice->id,
        'product_id' => $this->product->id,
        'price' => 100.00,
    ]);

    $response = $this->dreamPriceProductQueries->getByIdWithProduct($this->dreamPrice->id);

    expect($response->first()->toArray())
        ->toHaveKey('product_id', $dreamPriceProduct->product_id)
        ->toHaveKey('dream_price_id', $dreamPriceProduct->dream_price_id);
});

test('It returns dream price products when product variant is true', function (): void {
    Config::set('app.product_variant', true);

    $companyId = Company::factory()->create()->id;

    $masterProduct = MasterProduct::factory()->create([
        'company_id' => $companyId,
        'has_batch' => false,
        'is_non_inventory' => false,
    ]);

    $product = Product::factory()->create([
        'company_id' => $companyId,
        'compound_product_name' => 'ABCD131333',
        'code' => '131313',
        'upc' => 'wrwrwr',
        'article_number' => '12346644',
        'status' => Statuses::ACTIVE->value,
        'is_non_inventory' => false,
        'is_non_selling_item' => false,
        'is_available_in_pos' => true,
        'is_available_in_ecommerce' => false,
        'master_product_id' => $masterProduct->id,
    ]);

    $dreamPriceProduct = DreamPriceProduct::factory()->create([
        'dream_price_id' => $this->dreamPrice->id,
        'product_id' => $product->id,
        'price' => 100.00,
    ]);

    $response = $this->dreamPriceProductQueries->getByIdWithProduct($this->dreamPrice->id);

    expect($response->first()->toArray())
        ->toHaveKey('product_id', $dreamPriceProduct->product_id)
        ->toHaveKey('dream_price_id', $dreamPriceProduct->dream_price_id);
});

test('It returns dream price products ids', function (): void {
    $filterData = [
        'search_text' => 'ABC',
        'sort_by' => 'id',
        'sort_direction' => 'asc',
        'per_page' => 10,
        'after_updated_at' => null,
        'dream_price_id' => $this->dreamPrice->id,
    ];

    $dreamPriceProduct = DreamPriceProduct::factory()->create([
        'dream_price_id' => $this->dreamPrice->id,
        'product_id' => $this->product->id,
        'price' => 100.00,
    ]);

    $response = $this->dreamPriceProductQueries->getDreamPriceProduct($filterData);

    expect($response->first()->toArray())
        ->toHaveKey('product_id', $dreamPriceProduct->product_id);
});

test('getByDreamPriceId returns correct dream price products', function (): void {
    $dreamPriceProducts = DreamPriceProduct::factory()->count(3)->create([
        'dream_price_id' => $this->dreamPrice->id,
    ]);

    $result = $this->dreamPriceProductQueries->getByDreamPriceId($this->dreamPrice->id);

    $expectedIds = $dreamPriceProducts->pluck('id')->sort()->values()->toArray();
    $resultIds = $result->pluck('id')->sort()->values()->toArray();
    expect($resultIds)->toBe($expectedIds);

    $firstResult = $result->first()->toArray();
    expect($firstResult)->toHaveKeys(['id', 'dream_price_id', 'product_id', 'price']);
});
