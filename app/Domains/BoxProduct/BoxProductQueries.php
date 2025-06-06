<?php

declare(strict_types=1);

namespace App\Domains\BoxProduct;

use App\CommonFunctions;
use App\Domains\BoxProductLoyaltyPoint\BoxProductLoyaltyPointQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\PackageType\PackageTypeQueries;
use App\Domains\Product\Enums\ProductBatches;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use App\Domains\Tag\TagQueries;
use App\Models\BoxProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class BoxProductQueries
{
    public function addNew(array $productBoxRecord): BoxProduct
    {
        return BoxProduct::create($productBoxRecord);
    }

    public function deleteProductBox(Product $product): void
    {
        $boxProductLoyaltyPointQueries = resolve(BoxProductLoyaltyPointQueries::class);

        foreach ($product->boxes as $box) {
            $boxProductLoyaltyPointQueries->deleteBoxProductLoyaltyPoints($box);
        }

        $product->boxes()->delete();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,product_id,package_type_id,units,retail_price,staff_price,minimum_price,purchase_cost,wholesale_price';
    }

    public function updateProductId(int $oldProductId, int $newProductId): void
    {
        $boxProducts = BoxProduct::query()
            ->select('id', 'product_id')
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($boxProducts as $boxProduct) {
            $boxProduct->product_id = $newProductId;
            $boxProduct->save();
        }
    }

    public function findBoxByIdAndProductId(int $id, int $productId): ?BoxProduct
    {
        return BoxProduct::query()
            ->select('id')
            ->where('id', $id)
            ->where('product_id', $productId)
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->first();
    }

    public function getById(int $id): BoxProduct
    {
        return BoxProduct::select('id', 'package_type_id', 'units')->findOrFail($id);
    }

    public function getBoxProducts(array $filterData, int $companyId): Collection
    {
        return $this->getBoxProductLists($filterData, $companyId)->get();
    }

    public function exportBoxProductRecords(array $filterData, int $companyId, int $skip, int $limit): Collection
    {
        return $this->getBoxProductLists($filterData, $companyId)
            ->skip($skip)->limit($limit)->get();
    }

    public function getBoxProductsExportCount(array $filterData, int $companyId): int
    {
        return $this->getBoxProductLists($filterData, $companyId)->count();
    }

    private function getBoxProductLists(array $filterData, int $companyId): Builder
    {
        $brandQueries = resolve(BrandQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);
        $packageTypeQueries = resolve(PackageTypeQueries::class);
        $productQueries = resolve(ProductQueries::class);

        return BoxProduct::query()
            ->select('id', 'product_id', 'package_type_id', 'units', 'retail_price', 'staff_price')
            ->with([
                'product:' . $productQueries->getColumnsForBatchExpiryReports(),
                'packageType:' . $packageTypeQueries->getBasicColumnNames(),
            ])
            ->whereHas('product', function ($query) use (
                $filterData,
                $brandQueries,
                $categoryQueries,
                $companyId,
                $tagQueries,
                $productCollectionProductQueries
            ): void {
                $query->when($filterData['search_text'], function ($query) use (
                    $filterData,
                    $brandQueries,
                    $categoryQueries
                ): void {
                    $query->where(function ($query) use ($filterData, $brandQueries, $categoryQueries): void {
                        $query
                            ->whereAny(
                                [
                                    'compound_product_name',
                                    'code',
                                    'upc',
                                    'article_number',
                                    'retail_price',
                                    'purchase_cost',
                                    'ean',
                                    'custom_sku',
                                ],
                                'LIKE',
                                '%' . $filterData['search_text'] . '%'
                            )
                            ->orWhereHas('brand', $brandQueries->searchByName($filterData['search_text']))
                            ->orWhereHas('categories', $categoryQueries->searchByName($filterData['search_text']));
                    });
                })
                ->whereNot('status', Statuses::DRAFT->value)
                ->where('company_id', $companyId)
                ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                    $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
                }, function ($query): void {
                    $query->orderBy('id', 'desc');
                })
                ->when($filterData['date_range'], function ($query) use ($filterData): void {
                    $query->where('created_at', '>=', CommonFunctions::addStartTime($filterData['date_range'][0]))
                        ->where('created_at', '<=', CommonFunctions::addEndTime($filterData['date_range'][1]));
                })
                ->when($filterData['product_type_id'], function ($query) use ($filterData): void {
                    $query->where('type_id', (int) $filterData['product_type_id']);
                })
                ->when($filterData['category_ids'], function ($query) use ($filterData, $categoryQueries): void {
                    $query->whereHas('categories', $categoryQueries->filterByIds($filterData['category_ids']));
                })
                ->when($filterData['tag_ids'], function ($query) use ($filterData, $tagQueries): void {
                    $query->whereHas('tags', $tagQueries->filterByIds($filterData['tag_ids']));
                })
                ->when($filterData['brand_ids'], function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('brand_id', (array) $filterData['brand_ids']);
                })
                ->when($filterData['color_ids'], function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('color_id', (array) $filterData['color_ids']);
                })
                ->when($filterData['size_ids'], function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('size_id', (array) $filterData['size_ids']);
                })
                ->when($filterData['department_ids'], function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('department_id', (array) $filterData['department_ids']);
                })
                ->when(ProductStatuses::ACTIVE->value === $filterData['status'], function ($query): void {
                    $query->onlyActive();
                })
                ->when(ProductBatches::HAS_BATCH->value === $filterData['batch'], function ($query): void {
                    $query->where('has_batch', true);
                })
                ->when(ProductBatches::NO_BATCH->value === $filterData['batch'], function ($query): void {
                    $query->where('has_batch', false);
                })
                ->when($filterData['article_numbers'], function ($query) use ($filterData): void {
                    $query->whereIn('article_number', (array) $filterData['article_numbers']);
                })
                ->when($filterData['style_ids'], function ($query) use ($filterData): void {
                    $query->whereIntegerInRaw('style_id', (array) $filterData['style_ids']);
                })
                ->when($filterData['product_collection_ids'], function ($query) use (
                    $filterData,
                    $productCollectionProductQueries
                ): void {
                    $query->whereHas(
                        'productCollectionProducts',
                        $productCollectionProductQueries->filterByProductCollectionIds(
                            $filterData['product_collection_ids']
                        )
                    );
                })
                ->when(ProductStatuses::ARCHIVED->value === $filterData['status'], function ($query): void {
                    $query->onlyArchived();
                });
            });
    }
}
