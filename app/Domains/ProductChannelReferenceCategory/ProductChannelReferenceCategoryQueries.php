<?php

namespace App\Domains\ProductChannelReferenceCategory;

use App\Models\ProductChannelReferenceCategory;

class ProductChannelReferenceCategoryQueries
{
    public function addExternalCategoryId(int $categoryId, int $externalProductId): void
    {
        ProductChannelReferenceCategory::create([
            'external_category_id' => $categoryId,
            'product_channel_references_id' => $externalProductId,
        ]);
    }
}
