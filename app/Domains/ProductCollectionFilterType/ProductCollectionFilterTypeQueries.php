<?php

declare(strict_types=1);

namespace App\Domains\ProductCollectionFilterType;

use App\Models\ProductCollectionFilterType;

class ProductCollectionFilterTypeQueries
{
    public function addNew(int $typeId, int $productCollectionFilterId): void
    {
        ProductCollectionFilterType::create([
            'type_id' => $typeId,
            'product_collection_filter_id' => $productCollectionFilterId,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,type_id,product_collection_filter_id';
    }
}
