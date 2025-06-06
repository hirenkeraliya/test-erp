<?php

declare(strict_types=1);

use App\Domains\ProductChannelReferenceCategory\ProductChannelReferenceCategoryQueries;
use App\Models\ProductChannelReference; // Import the model

it('adds an external category ID to the database', function (): void {
    $queries = new ProductChannelReferenceCategoryQueries();

    $productChannelReference = ProductChannelReference::factory()->create();

    $categoryId = 123;
    $externalProductId = $productChannelReference->id;

    $queries->addExternalCategoryId($categoryId, $externalProductId);

    $this->assertDatabaseHas('product_channel_reference_categories', [
        'external_category_id' => $categoryId,
        'product_channel_references_id' => $externalProductId,
    ]);
});
