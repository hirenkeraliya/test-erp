<?php

declare(strict_types=1);

namespace App\Domains\ProductLoyaltyPoint;

use App\CommonFunctions;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Membership\MembershipQueries;
use App\Domains\Product\Enums\ProductBatches;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\Enums\Statuses;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductCollectionProduct\ProductCollectionProductQueries;
use App\Domains\Tag\TagQueries;
use App\Models\ProductLoyaltyPoint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductLoyaltyPointQueries
{
    public function addNew(int $productId, int $membershipId, int $points): void
    {
        ProductLoyaltyPoint::create([
            'product_id' => $productId,
            'membership_id' => $membershipId,
            'points' => $points,
        ]);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,product_id,membership_id,points';
    }

    public function updateProductId(int $companyId, int $oldProductId, int $newProductId): void
    {
        $productLoyaltyPoints = ProductLoyaltyPoint::query()
            ->select('id', 'product_id', 'membership_id')
            ->whereHas('membership', function ($query) use ($companyId): void {
                $query->where('company_id', $companyId);
            })
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($productLoyaltyPoints as $productLoyaltyPoint) {
            $productLoyaltyPoint->product_id = $newProductId;
            $productLoyaltyPoint->save();
        }
    }

    public function existByProductLoyaltyPoint(int $membershipId, int $productId): bool
    {
        return ProductLoyaltyPoint::query()
                    ->where('membership_id', $membershipId)
                    ->where('product_id', $productId)
                    ->exists();
    }

    public function exportLoyaltyPointProductRecords(
        array $filterData,
        int $companyId,
        int $skip,
        int $limit
    ): Collection {
        return $this->getLoyaltyPointProductLists($filterData, $companyId)
            ->skip($skip)->limit($limit)->get();
    }

    public function getLoyaltyPointProductsExportCount(array $filterData, int $companyId): int
    {
        return $this->getLoyaltyPointProductLists($filterData, $companyId)->count();
    }

    public function getLoyaltyPointProducts(array $filterData, int $companyId): Collection
    {
        return $this->getLoyaltyPointProductLists($filterData, $companyId)->get();
    }

    private function getLoyaltyPointProductLists(array $filterData, int $companyId): Builder
    {
        $brandQueries = resolve(BrandQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $tagQueries = resolve(TagQueries::class);
        $productCollectionProductQueries = resolve(ProductCollectionProductQueries::class);
        $membershipQueries = resolve(MembershipQueries::class);
        $productQueries = resolve(ProductQueries::class);

        return ProductLoyaltyPoint::query()
            ->select('id', 'product_id', 'membership_id', 'points')
            ->with([
                'product:' . $productQueries->getColumnsForBatchExpiryReports(),
                'membership:' . $membershipQueries->getColumnNamesForMemberApi(),
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
