<?php

declare(strict_types=1);

use App\Domains\ProductChannelReference\ProductChannelReferenceQueries;
use App\Models\Product;
use App\Models\ProductChannelReference;
use App\Models\SaleChannel;

beforeEach(function (): void {
    $this->productChannelReferenceQueries = new ProductChannelReferenceQueries();
});

test('a product channel reference can be added', function (): void {
    $product = Product::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $productChannelReferenceRecord = ProductChannelReference::factory()->make([
        'product_id' => $product,
        'sale_channel_id' => $saleChannelId,
        'external_product_id' => $product,
    ]);

    $this->productChannelReferenceQueries->addNew($productChannelReferenceRecord->toArray());

    $this->assertDatabaseHas('product_channel_references', [
        'product_id' => $productChannelReferenceRecord->product_id,
        'sale_channel_id' => $productChannelReferenceRecord->sale_channel_id,
        'external_product_id' => $productChannelReferenceRecord->external_product_id,
    ]);
});

test('getProductChannelReferenceByProductId method call and return proper response', function (): void {
    $productChannelReferenceRecord = ProductChannelReference::factory()->create();
    $response = $this->productChannelReferenceQueries->getProductChannelReferenceByProductId(
        $productChannelReferenceRecord->product_id
    );

    expect($response->toArray())
        ->toHaveKey('id', $productChannelReferenceRecord->id)
        ->toHaveKey('external_product_id', $productChannelReferenceRecord->external_product_id);
});

test('it calls the deleteExternalVariantId for delete the row', function (): void {
    $productChannelReferenceRecord = ProductChannelReference::factory()->create();
    $response = $this->productChannelReferenceQueries->deleteExternalVariantId($productChannelReferenceRecord);

    $this->assertDatabaseMissing(ProductChannelReference::class, $productChannelReferenceRecord->toArray());
});

it('retrieves products by sale channel ID', function (): void {
    $saleChannelId = SaleChannel::factory()->create()->id;
    $saleChannelIdB = SaleChannel::factory()->create()->id;
    ProductChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'product_id' => 101,
    ]);
    ProductChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'product_id' => 102,
    ]);
    ProductChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelIdB,
        'product_id' => 103,
    ]);

    $queries = new ProductChannelReferenceQueries();

    $result = $queries->getProductsByChannelId($saleChannelId);

    expect($result)->toHaveCount(2);
    expect($result->pluck('product_id')->toArray())->toMatchArray([101, 102]);
});

test('it calls the removeReferencesBasedOnSaleChannelAndProductIds for delete the row', function (): void {
    $product = Product::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $productChannelReferenceRecord = ProductChannelReference::factory()->make([
        'product_id' => $product,
        'sale_channel_id' => $saleChannelId,
        'external_product_id' => $product,
    ]);

    $this->productChannelReferenceQueries->removeReferencesBasedOnSaleChannelAndProductIds([$product], $saleChannelId);

    $this->assertDatabaseMissing(ProductChannelReference::class, $productChannelReferenceRecord->toArray());
});

test('it retrieves product ID by external variant ID and sale channel ID', function (): void {
    $saleChannelId = SaleChannel::factory()->create()->id;
    $productId = Product::factory()->create()->id;
    ProductChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'external_variant_id' => 1001,
        'product_id' => $productId,
    ]);

    $result = $this->productChannelReferenceQueries->getByProductId(1001, $saleChannelId);

    expect($result)->toBe($productId);
});
