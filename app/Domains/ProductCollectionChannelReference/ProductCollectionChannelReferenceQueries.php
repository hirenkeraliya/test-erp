<?php

declare(strict_types=1);

namespace App\Domains\ProductCollectionChannelReference;

use App\Models\ProductCollectionChannelReference;

class ProductCollectionChannelReferenceQueries
{
    public function addNew(array $record): void
    {
        ProductCollectionChannelReference::create($record);
    }

    public function getByProductCollectionIdAndSaleChannelId(
        int $productCollectionId,
        int $saleChannelId
    ): ?ProductCollectionChannelReference {
        return ProductCollectionChannelReference::select(
            'id',
            'product_collection_id',
            'external_product_collection_id'
        )
            ->where('product_collection_id', $productCollectionId)
            ->where('sale_channel_id', $saleChannelId)
            ->first();
    }

    public function getProductCollectionIdIdForEcommerce(int $productCollectionId): ?ProductCollectionChannelReference
    {
        return ProductCollectionChannelReference::query()
            ->select('id', 'product_collection_id', 'external_product_collection_id')
            ->where('product_collection_id', $productCollectionId)
            ->first();
    }

    public function deleteById(int $id, int $saleChannelId): void
    {
        $productCollectionChannelReference = ProductCollectionChannelReference::select(
            'id',
            'product_collection_id'
        )
        ->where('sale_channel_id', $saleChannelId)
        ->where('external_product_collection_id', $id)
        ->first();

        if ($productCollectionChannelReference) {
            $productCollectionChannelReference->delete();
        }
    }
}
