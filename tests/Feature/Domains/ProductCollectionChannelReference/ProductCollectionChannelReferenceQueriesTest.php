<?php

declare(strict_types=1);

use App\Domains\ProductCollectionChannelReference\ProductCollectionChannelReferenceQueries;
use App\Models\ProductCollection;
use App\Models\ProductCollectionChannelReference;
use App\Models\SaleChannel;

beforeEach(function (): void {
    $this->productCollectionChannelReferenceQueries = new ProductCollectionChannelReferenceQueries();
});

test('a product collection channel reference can be added', function (): void {
    $productCollection = ProductCollection::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $productCollectionChannelReferenceRecord = ProductCollectionChannelReference::factory()->make([
        'product_collection_id' => $productCollection,
        'sale_channel_id' => $saleChannelId,
        'external_product_collection_id' => $productCollection,
    ]);

    $this->productCollectionChannelReferenceQueries->addNew($productCollectionChannelReferenceRecord->toArray());

    $this->assertDatabaseHas(
        ProductCollectionChannelReference::class,
        $productCollectionChannelReferenceRecord->toArray()
    );
});

test('it calls the getByProductCollectionIdAndSaleChannelId to get the external Product Collection', function (): void {
    $productCollectionId = ProductCollection::factory()->create()->getKey();
    $saleChannelId = SaleChannel::factory()->create()->getKey();

    $productCollectionChannelReference = ProductCollectionChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'product_collection_id' => $productCollectionId,
        'external_product_collection_id' => 1,
    ]);

    $response = $this->productCollectionChannelReferenceQueries->getByProductCollectionIdAndSaleChannelId(
        $productCollectionId,
        $saleChannelId
    );

    expect($response)
        ->toHaveKey('id', $productCollectionChannelReference->getKey())
        ->toHaveKey('product_collection_id', $productCollectionId)
        ->toHaveKey('external_product_collection_id', 1);
});

test('a product collection channel reference can be deleted', function (): void {
    $saleChannelId = SaleChannel::factory()->create()->getKey();

    $productCollectionReference = ProductCollectionChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'external_product_collection_id' => 12,
    ]);

    $this->assertDatabaseHas('product_collection_channel_references', [
        'id' => $productCollectionReference->id,
    ]);

    $productCollectionChannelReferenceQueries = new ProductCollectionChannelReferenceQueries();
    $productCollectionChannelReferenceQueries->deleteById(12, $saleChannelId);

    $this->assertDatabaseMissing('product_collection_channel_references', [
        'id' => $productCollectionReference->id,
    ]);
});
