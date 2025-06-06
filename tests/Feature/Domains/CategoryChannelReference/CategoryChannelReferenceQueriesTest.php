<?php

declare(strict_types=1);

use App\Domains\CategoryChannelReference\CategoryChannelReferenceQueries;
use App\Models\Category;
use App\Models\CategoryChannelReference;
use App\Models\SaleChannel;

beforeEach(function (): void {
    $this->categoryChannelReferenceQueries = new CategoryChannelReferenceQueries();
});

test('a category channel reference can be added', function (): void {
    $category = Category::factory()->create()->id;
    $saleChannelId = SaleChannel::factory()->create()->id;

    $categoryChannelReferenceRecord = CategoryChannelReference::factory()->make([
        'category_id' => $category,
        'sale_channel_id' => $saleChannelId,
        'external_category_id' => $category,
    ]);

    $this->categoryChannelReferenceQueries->addNew($categoryChannelReferenceRecord->toArray());

    $this->assertDatabaseHas(CategoryChannelReference::class, [
        'category_id' => $categoryChannelReferenceRecord->category_id,
        'sale_channel_id' => $categoryChannelReferenceRecord->sale_channel_id,
        'external_category_id' => $categoryChannelReferenceRecord->external_category_id,
    ]);
});

test('it calls the getExternalCategoryIdFromCategoryId to get the external category', function (): void {
    $categoryId = Category::factory()->create()->getKey();
    $saleChannelId = SaleChannel::factory()->create()->getKey();

    $categoryChannelReference = CategoryChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'category_id' => $categoryId,
        'external_category_id' => $categoryId,
    ]);

    $response = $this->categoryChannelReferenceQueries->getExternalCategoryIdFromCategoryId($categoryId);

    expect($response)
        ->toHaveKey('id', $categoryChannelReference->getKey())
        ->toHaveKey('external_category_id', $categoryId);
});

test('it calls the getBySaleChannelIdCategoryIds to get the external categories', function (): void {
    $categoryId = Category::factory()->create()->getKey();
    $saleChannelId = SaleChannel::factory()->create()->getKey();

    $categoryChannelReference = CategoryChannelReference::factory()->create([
        'sale_channel_id' => $saleChannelId,
        'category_id' => $categoryId,
        'external_category_id' => $categoryId,
    ]);

    $response = $this->categoryChannelReferenceQueries->getBySaleChannelIdCategoryIds([$categoryId], $saleChannelId);

    expect($response->first()->toArray())
        ->toHaveKey('id', $categoryChannelReference->getKey())
        ->toHaveKey('external_category_id', $categoryId);
});
