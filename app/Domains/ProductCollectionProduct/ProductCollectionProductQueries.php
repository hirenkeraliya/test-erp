<?php

declare(strict_types=1);

namespace App\Domains\ProductCollectionProduct;

use App\Domains\ProductCollection\ProductCollectionQueries;
use App\Models\ProductCollectionProduct;
use Closure;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductCollectionProductQueries
{
    public function addNew(array $productCollectionProductData): void
    {
        $productCollectionProductExists = $this->checkAlreadyExists(
            $productCollectionProductData['product_id'],
            $productCollectionProductData['product_collection_id']
        );
        if (! $productCollectionProductExists) {
            ProductCollectionProduct::create($productCollectionProductData);
        }
    }

    public function checkAlreadyExists(int $productId, int $productCollectionId): bool
    {
        return ProductCollectionProduct::where('product_id', $productId)
            ->where('product_collection_id', $productCollectionId)
            ->exists();
    }

    public function removeByProductId(int $productId, int $companyId): void
    {
        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollectionProducts = ProductCollectionProduct::select('id')
            ->whereHas('productCollection', $productCollectionQueries->filterByCompany($companyId))
            ->where('product_id', $productId)
            ->get();

        foreach ($productCollectionProducts as $productCollectionProduct) {
            $productCollectionProduct->delete();
        }
    }

    public function syncByProductCollectionId(int $productCollectionId): Collection
    {
        return ProductCollectionProduct::select('id')
            ->where('product_collection_id', $productCollectionId)
            ->where('is_synced', false)
            ->get();
    }

    public function removeByProductCollectionId(int $productCollectionId, int $companyId): void
    {
        $productCollectionQueries = resolve(ProductCollectionQueries::class);
        $productCollection = $productCollectionQueries->getProductCollectionById($productCollectionId, $companyId);
        $productCollection->productCollectionProducts()->delete();
    }

    public function getBasicColumns(): string
    {
        return 'id,product_collection_id,is_synced';
    }

    public function getProductCollectionAndProductIdColumns(): string
    {
        return 'product_collection_id,product_id';
    }

    public function filterByProductCollectionIds(array $productCollectionIds): Closure
    {
        return fn ($query) => $query->select('id', 'product_collection_id')->whereIntegerInRaw(
            'product_collection_id',
            $productCollectionIds
        );
    }

    public function getProductCollectionProducts(array $filteredData): LengthAwarePaginator
    {
        return ProductCollectionProduct::query()
            ->select('product_id')
            ->where('product_collection_id', $filteredData['product_collection_id'])
            ->when($filteredData['after_updated_at'], function ($query) use ($filteredData): void {
                $query->where('updated_at', '>=', $filteredData['after_updated_at']);
            })
            ->when($filteredData['sort_by'], function ($query) use ($filteredData): void {
                $query->orderBy($filteredData['sort_by'], $filteredData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filteredData['per_page']);
    }
}
