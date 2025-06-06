<?php

declare(strict_types=1);

namespace App\Domains\TopTwentyAggregateData;

use App\Domains\Attribute\AttributeQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\Style\StyleQueries;
use App\Models\TopTwentyAggregateData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TopTwentyAggregateDataQueries
{
    public function addNew(array $topTwentyRecords): void
    {
        TopTwentyAggregateData::create([
            'product_id' => $topTwentyRecords['product_id'],
            'counter_update_id' => $topTwentyRecords['counter_update_id'],
            'date' => $topTwentyRecords['date'],
            'quantity' => $topTwentyRecords['quantity'],
            'gross_sales' => $topTwentyRecords['gross_sales'],
            'discount' => $topTwentyRecords['discount'],
            'net_sales' => $topTwentyRecords['net_sales'],
            'tax' => $topTwentyRecords['tax'],
            'total_amount' => $topTwentyRecords['total_amount'],
        ]);
    }

    public function getByStoreForTopProductExport(array $filterData): Collection
    {
        return $this->topTwentyAggregateQuery($filterData)
            ->orderBy('quantity', 'desc')
            ->get();
    }

    public function getByStoreForTopColorExport(array $filterData): Collection
    {
        return $this->topTwentyAggregateQuery($filterData)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getByStoreForTopAttributeExport(array $filterData): Collection
    {
        return $this->topTwentyAggregateQuery($filterData)
            ->orderBy('id', 'desc')
            ->get();
    }

    private function topTwentyAggregateQuery(array $filterData): Builder
    {
        $productQueries = resolve(ProductQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $styleQueries = resolve(StyleQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $categoryQueries = resolve(CategoryQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        return TopTwentyAggregateData::query()
            ->select(
                'id',
                'product_id',
                'counter_update_id',
                'date',
                'quantity',
                'gross_sales',
                'discount',
                'net_sales',
                'tax',
                'total_amount'
            )
            ->with([
                'counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
                'counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
            ])
            ->withWhereHas('product', function ($query) use (
                $productQueries,
                $colorQueries,
                $sizeQueries,
                $styleQueries,
                $brandQueries,
                $categoryQueries,
                $attributeQueries,
                $masterProductQueries,
                $filterData,
            ): void {
                $query->select(explode(',', $productQueries->getBasicColumnsName()))
                    ->onlyActive();

                if (config('app.product_variant')) {
                    $query->with([
                        'productVariantValue' => function ($query) use ($filterData): void {
                            $query->where('attribute_id', (int) $filterData['attribute_type']);
                        },
                        'productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                        'masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                        'masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                        'masterProduct.categories:' . $categoryQueries->getBasicColumnNames(),
                    ])->whereHas('masterProduct', function ($query): void {
                        $query->where('is_non_selling_item', false);
                    });
                } else {
                    $query->with([
                        'color:' . $colorQueries->getBasicColumnNames(),
                        'size:' . $sizeQueries->getBasicColumnNames(),
                        'style:' . $styleQueries->getBasicColumnNames(),
                        'brand:' . $brandQueries->getBasicColumnNames(),
                        'categories:' . $categoryQueries->getBasicColumnNames(),
                    ])->where('is_non_selling_item', false);
                }
            })
            ->whereHas('counterUpdate', function ($query) use ($filterData, $counterUpdateQueries): void {
                $query
                    ->when(null !== $filterData['location_ids'], function ($query) use (
                        $filterData,
                        $counterUpdateQueries
                    ): void {
                        $query->where($counterUpdateQueries->filterByStoreIds($filterData['location_ids']));
                    })
                    ->when(null !== $filterData['counter_ids'], function ($query) use (
                        $filterData,
                        $counterUpdateQueries
                    ): void {
                        $query->where($counterUpdateQueries->filterByCounterIds($filterData['counter_ids']));
                    })
                    ->when(null !== $filterData['cashier_ids'], function ($query) use (
                        $filterData,
                        $counterUpdateQueries
                    ): void {
                        $query->where($counterUpdateQueries->filterByCashierIds($filterData['cashier_ids']));
                    });
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->where('date', '>=', $filterData['date_range'][0])
                        ->where('date', '<=', $filterData['date_range'][1]);
                });
            });
    }

    public function getBasicColumnNames(): string
    {
        return 'id,product_id,counter_update_id,date,quantity,gross_sales,discount,net_sales,tax,total_amount';
    }
}
