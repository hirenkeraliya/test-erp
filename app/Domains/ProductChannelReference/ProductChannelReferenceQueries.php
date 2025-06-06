<?php

declare(strict_types=1);

namespace App\Domains\ProductChannelReference;

use App\Domains\SaleChannel\Enums\SaleChannelTypes;
use App\Models\ProductChannelReference;
use Illuminate\Support\Collection;

class ProductChannelReferenceQueries
{
    public function addNew(array $productExternalIdRecords): ProductChannelReference
    {
        return ProductChannelReference::updateOrCreate([
            'sale_channel_id' => $productExternalIdRecords['sale_channel_id'],
            'product_id' => $productExternalIdRecords['product_id'],
            'external_product_id' => $productExternalIdRecords['external_product_id'],
            'external_variant_id' => $productExternalIdRecords['external_variant_id'],
        ]);
    }

    public function getProductChannelReferenceByProductId(int $productId): ?ProductChannelReference
    {
        return ProductChannelReference::select('id', 'product_id', 'external_product_id', 'external_variant_id')
            ->where('product_id', $productId)
            ->first();
    }

    public function getByProductIdAndSaleChannelId(int $productId, int $saleChannelId): ?ProductChannelReference
    {
        return ProductChannelReference::select('id', 'product_id', 'external_product_id', 'external_variant_id')
            ->where('product_id', $productId)
            ->where('sale_channel_id', $saleChannelId)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getByProductIdAndSaleChannelIds(array $productIds, int $saleChannelId): Collection
    {
        return ProductChannelReference::select('id', 'external_product_id', 'external_variant_id')
            ->whereIntegerInRaw('product_id', $productIds)
            ->where('sale_channel_id', $saleChannelId)
            ->get();
    }

    public function getByProductIdAndSaleChannelIdForEcommerce(
        int $productId,
        int $saleChannelId
    ): ?ProductChannelReference {
        return ProductChannelReference::select('id', 'product_id', 'external_product_id', 'external_variant_id')
            ->where('product_id', $productId)
            ->where('sale_channel_id', $saleChannelId)
            ->whereNotNull('external_variant_id')
            ->first();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,sale_channel_id,product_id,external_product_id,external_variant_id';
    }

    public function getProductForWebspert(string $articleNumber, ?int $colorId = 0): Collection
    {
        return ProductChannelReference::query()->select(
            'id',
            'product_id',
            'external_product_id',
            'external_variant_id'
        )
            ->whereHas('product', function ($query) use ($articleNumber, $colorId): void {
                $query
                    ->select('id')
                    ->where('article_number', $articleNumber)
                    ->when(0 !== $colorId, function ($query) use ($colorId): void {
                        $query->where('color_id', $colorId);
                    });
            })
            ->whereHas('saleChannel', function ($query): void {
                $query
                    ->select('id')
                    ->where('type_id', SaleChannelTypes::WEBSPERT_ECOMMERCE);
            })
            ->get();
    }

    public function getByArticleNumberAndSaleChannelId(
        string $articleNumber,
        int $saleChannelId
    ): ?ProductChannelReference {
        return ProductChannelReference::query()->select(
            'id',
            'product_id',
            'external_product_id',
            'external_variant_id'
        )
            ->where('sale_channel_id', $saleChannelId)
            ->whereHas('product', function ($query) use ($articleNumber): void {
                $query
                    ->select('id', 'article_number')
                    ->where('article_number', $articleNumber);
            })
            ->first();
    }

    public function getProductIdForWebspert(int $productId): ?ProductChannelReference
    {
        return ProductChannelReference::query()
            ->select('id', 'product_id', 'external_product_id', 'external_variant_id')
            ->whereHas('saleChannel', function ($query): void {
                $query
                    ->select('id')
                    ->where('type_id', SaleChannelTypes::WEBSPERT_ECOMMERCE);
            })
            ->where('product_id', $productId)
            ->first();
    }

    public function deleteExternalVariantId(ProductChannelReference $productChannelReference): void
    {
        $productChannelReference->delete();
    }

    public function deleteExternalProductId(ProductChannelReference $productChannelReference): void
    {
        $productChannelReference->delete();
    }

    public function getRecordsByProductId(int $productId): Collection
    {
        return ProductChannelReference::query()
            ->select('id', 'sale_channel_id', 'product_id', 'external_variant_id')
            ->where('product_id', $productId)
            ->whereNotNull('external_variant_id')
            ->get();
    }

    public function deleteOldProductForMerge(int $oldProductId): void
    {
        ProductChannelReference::query()
            ->where('product_id', $oldProductId)
            ->whereNotNull('external_variant_id')
            ->delete();
    }

    public function getProductsByChannelId(int $saleChannelId): Collection
    {
        return ProductChannelReference::query()
            ->select('id', 'product_id')
            ->where('sale_channel_id', $saleChannelId)
            ->get();
    }

    public function removeReferencesBasedOnSaleChannelAndProductIds(array $productIds, int $saleChannelId): void
    {
        ProductChannelReference::query()
            ->when(0 !== $saleChannelId, function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->whereIntegerInRaw('product_id', $productIds)
            ->chunk(5000, function (Collection $productChannelReferences): void {
                foreach ($productChannelReferences as $productChannelReference) {
                    $productChannelReference->delete();
                }
            });
    }

    public function getByProductId(int $productId, int $saleChannelId): ?int
    {
        return ProductChannelReference::query()
            ->select('id', 'product_id')
            ->where('external_variant_id', $productId)
            ->where('sale_channel_id', $saleChannelId)
            ->first()?->product_id;
    }
}
