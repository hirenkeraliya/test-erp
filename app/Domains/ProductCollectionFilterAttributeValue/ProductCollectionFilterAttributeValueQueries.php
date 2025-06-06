<?php

declare(strict_types=1);

namespace App\Domains\ProductCollectionFilterAttributeValue;

use App\Models\ProductCollectionFilterAttributeValue;

class ProductCollectionFilterAttributeValueQueries
{
    public function addNew(int $productCollectionFilterId, int $attributeId, string $value): void
    {
        ProductCollectionFilterAttributeValue::create([
            'product_collection_filter_id' => $productCollectionFilterId,
            'attribute_id' => $attributeId,
            'value' => $value,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,product_collection_filter_id,attribute_id,value';
    }
}
