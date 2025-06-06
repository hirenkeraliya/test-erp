<?php

declare(strict_types=1);

use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductVariantValue;

beforeEach(function (): void {
    $this->productVariantValueQueries = new ProductVariantValueQueries();
});

test('a product variant value can be added', function (): void {
    $productId = Product::factory()->create()->id;
    $attributeId = Attribute::factory()->create()->id;

    $productVariantValue = ProductVariantValue::factory()->create([
        'product_id' => $productId,
        'attribute_id' => $attributeId,
        'value' => 'Red',
    ]);

    $this->productVariantValueQueries->addNew(
        $productVariantValue->product_id,
        $productVariantValue->attribute_id,
        $productVariantValue->value,
    );

    $this->assertDatabaseHas('product_variant_values', [
        'product_id' => $productVariantValue->product_id,
        'attribute_id' => $productVariantValue->attribute_id,
        'value' => $productVariantValue->value,
    ]);
});

test('getProductsWithMatchingVariants method call and return proper response', function (): void {
    $productId = Product::factory()->create()->id;
    $productId2 = Product::factory()->create()->id;

    $productVariantValue = ProductVariantValue::factory()->create([
        'product_id' => $productId2,
        'value' => 'Red',
    ]);

    $response = $this->productVariantValueQueries->getProductsWithMatchingVariants(
        $productId,
        [$productVariantValue->value]
    );

    expect($response->first()->toArray())
        ->toHaveKey('product_id', $productId2);
});
