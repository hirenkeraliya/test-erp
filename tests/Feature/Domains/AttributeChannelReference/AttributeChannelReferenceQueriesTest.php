<?php

declare(strict_types=1);

use App\Domains\AttributeChannelReference\AttributeChannelReferenceQueries;
use App\Models\Attribute;
use App\Models\AttributeChannelReference;
use App\Models\SaleChannel;

beforeEach(function (): void {
    $this->attributeChannelReferenceQueries = new AttributeChannelReferenceQueries();
});

test('a attribute channel reference can be added', function (): void {
    $attribute = Attribute::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $attributeChannelReferenceRecord = AttributeChannelReference::factory()->make([
        'attribute_id' => $attribute,
        'sale_channel_id' => $saleChannelId,
        'external_attribute_id' => $attribute,
    ]);

    $this->attributeChannelReferenceQueries->addNew($attributeChannelReferenceRecord->toArray());

    $this->assertDatabaseHas(AttributeChannelReference::class, $attributeChannelReferenceRecord->toArray());
});

test('it calls the getByAttributeIdAndSaleChannelId to get the external Attribute', function (): void {
    $attributeId = Attribute::factory()->create()->getKey();
    $saleChannelId = SaleChannel::factory()->create()->getKey();

    $attributeChannelReference = AttributeChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'attribute_id' => $attributeId,
        'external_attribute_id' => 1,
    ]);

    $response = $this->attributeChannelReferenceQueries->getByAttributeIdAndSaleChannelId($attributeId, $saleChannelId);

    expect($response)
        ->toHaveKey('id', $attributeChannelReference->getKey())
        ->toHaveKey('attribute_id', $attributeId)
        ->toHaveKey('external_attribute_id', 1);
});

test('it calls the getByAttributeIdAndSaleChannelIds to get the external Attributes', function (): void {
    $attributeId = Attribute::factory()->create()->getKey();
    $saleChannelId = SaleChannel::factory()->create()->getKey();

    $attributeChannelReference = AttributeChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'attribute_id' => $attributeId,
        'external_attribute_id' => 1,
    ]);

    $response = $this->attributeChannelReferenceQueries->getByAttributeIdAndSaleChannelIds(
        [$attributeId],
        $saleChannelId
    );

    expect($response->first()->toArray())
        ->toHaveKey('id', $attributeChannelReference->getKey())
        ->toHaveKey('attribute_id', $attributeId)
        ->toHaveKey('external_attribute_id', 1);
});
