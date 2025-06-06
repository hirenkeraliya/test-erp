<?php

declare(strict_types=1);

namespace App\Domains\SellThroughAggregate;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughFilterTypes;
use App\Domains\SellThroughAggregate\Enums\SellThroughIncludeTypes;
use App\Domains\StockSummary\Enums\StockSummaryByModuleReportBy;
use App\Domains\StockSummary\Enums\StockSummaryByModuleReportType;
use App\Models\SellThroughAggregate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SellThroughAggregateQueries
{
    public function updateOrCreate(array $data): void
    {
        SellThroughAggregate::updateOrCreate(
            [
                'date' => $data['date'],
                'product_id' => $data['product_id'],
                'location_id' => $data['location_id'],
            ],
            [
                'goods_receive_note_in' => $data['goods_receive_note_in'],
                'goods_receive_note_out' => $data['goods_receive_note_out'],
                'stock_adjustment_in' => $data['stock_adjustment_in'],
                'stock_adjustment_out' => $data['stock_adjustment_out'],
                'stock_transfer_in' => $data['stock_transfer_in'],
                'stock_transfer_out' => $data['stock_transfer_out'],
                'delivery_order_in' => $data['delivery_order_in'],
                'delivery_order_out' => $data['delivery_order_out'],
                'foc_sold' => $data['foc_sold'],
                'sold' => $data['sold'],
                'sold_online' => $data['sold_online'],
                'foc_sold_online' => $data['foc_sold_online'],
                'total_online_sold_amount' => $data['total_online_sold_amount'],
                'sale_amount' => $data['total_amount'],
                'return' => $data['return'],
                'sale_return_amounts' => $data['total_return_amount'],
                'balance' => $data['balance'],
            ]
        );
    }

    public function sellThroughAggregateForUpcPaginate(array $filterData, int $companyId): LengthAwarePaginator
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForUpc($filterData, $companyId)
                ->paginate($filterData['per_page'])
        );
    }

    public function stockMovementAggregateForUpc(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQueryStockMovementForUpc($filterData, $companyId)
                ->get()
        );
    }

    public function sellThroughAggregateForUpcGet(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForUpc($filterData, $companyId)
                ->get()
        );
    }

    public function commonQuerySellThroughForUpc(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'product_id', 'location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query
                    ->whereAny(['products.name', 'products.upc'], 'LIKE', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('product_id');

        $relations = ['product:' . $productQueries->getColumnNameAndIdWithMasterId(), 'product.media'];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        }

        return SellThroughAggregate::select(
            'sell_through_aggregates.id as sell_through_aggregate_id',
            'sell_through_aggregates.product_id',
            'products.name',
            'products.retail_price as price',
            'products.upc',
            'products.original_created_at',
            'products.created_at',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw('COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sale_return_amounts), 0) as net_sale_amount'),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw($this->receivedLogic($filterData) . ' AS received'),
            DB::raw('
                    CASE
                        WHEN (' . $this->receivedLogic($filterData) . " = 0) THEN 0
                        ELSE (
                            ({$this->getSoldLogic($filterData)}
                            + {$this->getOnlineSoldLogic($filterData)})
                            * 100 / {$this->receivedLogic($filterData)}
                        )
                    END as sell_through
                ")
        )
            ->when(! config('app.product_variant'), function ($query): void {
                $query->addSelect('colors.name as color_name', 'sizes.name as size_name');
            })
            ->with($relations)
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            }, function ($query): void {
                $query->leftJoin('colors', 'colors.id', '=', 'products.color_id')
                    ->leftJoin('sizes', 'sizes.id', '=', 'products.size_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query
                    ->whereAny(['products.name', 'products.upc'], 'LIKE', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy('products.id');
    }

    public function commonQueryStockMovementForUpc(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'product_id', 'location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query
                    ->whereAny(['products.name', 'products.upc'], 'LIKE', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))->groupBy(
                'product_id'
            )
            ->groupBy(['location_id']);

        $relations = ['product:' . $productQueries->getColumnNameAndIdWithMasterId(), 'product.media'];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        }

        return SellThroughAggregate::select(
            'sell_through_aggregates.id as sell_through_aggregate_id',
            'sell_through_aggregates.product_id',
            'products.name',
            'products.retail_price as price',
            'products.upc',
            'locations.name as location_name',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_in), 0) as goods_receive_note_in_balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_out), 0) as goods_receive_note_out_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_in), 0) as stock_adjustment_in_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_out), 0) as stock_adjustment_out_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_in), 0) as stock_transfer_in_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_out), 0) as stock_transfer_out_balance'),
            DB::raw('COALESCE(SUM(delivery_order_in), 0) as delivery_order_in_balance'),
            DB::raw('COALESCE(SUM(delivery_order_out), 0) as delivery_order_out_balance'),
        )
            ->when(! config('app.product_variant'), function ($query): void {
                $query->addSelect('colors.name as color_name', 'sizes.name as size_name');
            })
            ->with($relations)
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->leftJoin('locations', 'locations.id', '=', 'sell_through_aggregates.location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->leftJoin('colors', 'colors.id', '=', 'products.color_id')
            ->leftJoin('sizes', 'sizes.id', '=', 'products.size_id')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query
                    ->whereAny(['products.name', 'products.upc'], 'LIKE', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy(['products.id', 'sell_through_aggregates.location_id']);
    }

    public function balanceDetailsByUpc(array $filterData, int $productId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'product_id',
            DB::raw('COALESCE(SUM(balance), 0) AS balance'),
        )
            ->where('product_id', $productId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('product_id', 'location_id')
            ->get();
    }

    public function soldDetailsByUpc(array $filterData, int $productId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'product_id',
            'location_id',
            DB::raw('SUM(sold) as sold'),
            DB::raw('SUM(foc_sold) as foc_sold'),
            DB::raw('SUM(sell_through_aggregates.return) as return_quantity')
        )
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->join('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->where('product_id', $productId)
            ->when(isset($filterData['date']), function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            }, function ($query) use ($filterData): void {
                $query->whereBetween('date', [$filterData['date_range'][0], $filterData['date_range'][1]]);
            })
            ->groupBy('product_id', 'location_id')->get();
    }

    public function receivedDetailsByUpc(array $filterData, int $productId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        $productQueries = new ProductQueries();

        return SellThroughAggregate::select(
            'location_id',
            'product_id',
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_in_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_IN->value,
                'goods_receive_note_in'
            ) . '
                    ), 0
                ) AS goods_receive_note_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_out_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_OUT->value,
                'goods_receive_note_out'
            ) . '
                    ), 0
                ) AS goods_receive_note_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_in_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_IN->value,
                'stock_adjustment_in'
            ) . '
                    ), 0
                ) AS stock_adjustment_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_out_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_OUT->value,
                'stock_adjustment_out'
            ) . '
                    ), 0
                ) AS stock_adjustment_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_in_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_IN->value,
                'stock_transfer_in'
            ) . '
                    ), 0
                ) AS stock_transfer_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_out_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_OUT->value,
                'stock_transfer_out'
            ) . '
                    ), 0
                ) AS stock_transfer_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_in_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_IN->value,
                'delivery_order_in'
            ) . '
                    ), 0
                ) AS delivery_order_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_out_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_OUT->value,
                'delivery_order_out'
            ) . '
                    ), 0
                ) AS delivery_order_out_balance')
        )
            ->where('product_id', $productId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('product_id', 'location_id')
            ->get();
    }

    public function updateProductId(int $oldProductId, int $newProductId): void
    {
        $sellThroughAggregates = SellThroughAggregate::query()
            ->select('id', 'product_id')
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($sellThroughAggregates as $sellThroughAggregate) {
            $sellThroughAggregate->product_id = $newProductId;
            $sellThroughAggregate->save();
        }
    }

    public function getByOldOrNewProductId(int $oldProductId, int $newProductId): Collection
    {
        return SellThroughAggregate::query()
            ->select('id', 'product_id', 'location_id', 'date')
            ->orWhere('product_id', $oldProductId)
            ->orWhere('product_id', $newProductId)
            ->get();
    }

    private function getByProductIdLocationIdAndDate(
        int $productId,
        int $locationId,
        string $date
    ): ?SellThroughAggregate {
        return SellThroughAggregate::query()
            ->select(
                'id',
                'product_id',
                'location_id',
                'date',
                'goods_receive_note_in',
                'goods_receive_note_out',
                'stock_adjustment_in',
                'stock_adjustment_out',
                'stock_transfer_in',
                'stock_transfer_out',
                'delivery_order_in',
                'delivery_order_out',
                'foc_sold',
                'sold',
                'return',
            )
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->where('date', $date)
            ->first();
    }

    public function updateTheNumberColumnsAndDeleteOldProduct(
        SellThroughAggregate $oldSellThroughAggregate,
        SellThroughAggregate $newSellThroughAggregate
    ): void {
        /** @var SellThroughAggregate $oldFullSellThroughAggregate */
        $oldFullSellThroughAggregate = $this->getByProductIdLocationIdAndDate(
            $oldSellThroughAggregate->product_id,
            $oldSellThroughAggregate->location_id,
            $oldSellThroughAggregate->date
        );

        /** @var SellThroughAggregate $newFullSellThroughAggregate */
        $newFullSellThroughAggregate = $this->getByProductIdLocationIdAndDate(
            $newSellThroughAggregate->product_id,
            $newSellThroughAggregate->location_id,
            $newSellThroughAggregate->date
        );

        $newFullSellThroughAggregate->goods_receive_note_in += $oldFullSellThroughAggregate->goods_receive_note_in;
        $newFullSellThroughAggregate->goods_receive_note_out += $oldFullSellThroughAggregate->goods_receive_note_out;
        $newFullSellThroughAggregate->stock_adjustment_in += $oldFullSellThroughAggregate->stock_adjustment_in;
        $newFullSellThroughAggregate->stock_adjustment_out += $oldFullSellThroughAggregate->stock_adjustment_out;
        $newFullSellThroughAggregate->stock_transfer_in += $oldFullSellThroughAggregate->stock_transfer_in;
        $newFullSellThroughAggregate->stock_transfer_out += $oldFullSellThroughAggregate->stock_transfer_out;
        $newFullSellThroughAggregate->delivery_order_in += $oldFullSellThroughAggregate->delivery_order_in;
        $newFullSellThroughAggregate->delivery_order_out += $oldFullSellThroughAggregate->delivery_order_out;
        $newFullSellThroughAggregate->foc_sold += $oldFullSellThroughAggregate->foc_sold;
        $newFullSellThroughAggregate->sold += $oldFullSellThroughAggregate->sold;
        $newFullSellThroughAggregate->return += $oldFullSellThroughAggregate->return;
        $newFullSellThroughAggregate->save();

        $oldFullSellThroughAggregate->delete();
    }

    public function updateOldProductToNewProduct(
        SellThroughAggregate $oldSellThroughAggregate,
        int $newProductId
    ): void {
        /** @var SellThroughAggregate $oldFullSellThroughAggregate */
        $oldFullSellThroughAggregate = $this->getByProductIdLocationIdAndDate(
            $oldSellThroughAggregate->product_id,
            $oldSellThroughAggregate->location_id,
            $oldSellThroughAggregate->date
        );

        $oldFullSellThroughAggregate->product_id = $newProductId;
        $oldFullSellThroughAggregate->save();
    }

    private function getSoldLogic(array $filterData): string
    {
        return '
            CASE
                WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ALL->value . '
                    THEN (COALESCE(SUM(sold), 0) + COALESCE(SUM(foc_sold), 0) - COALESCE(SUM(sell_through_aggregates.return), 0))
                WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ONLY_SOLD->value . '
                    THEN COALESCE(SUM(sold), 0) - COALESCE(SUM(sell_through_aggregates.return), 0)
                WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ONLY_FREE_ITEMS_SOLD->value . '
                    THEN COALESCE(SUM(foc_sold), 0) - COALESCE(SUM(sell_through_aggregates.return), 0)
                ELSE 0
            END';
    }

    private function getOnlineSoldLogic(array $filterData): string
    {
        return '
            CASE
                WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ALL->value . '
                    THEN COALESCE(SUM(sold_online), 0) + COALESCE(SUM(foc_sold_online), 0)
                WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ONLY_SOLD->value . '
                    THEN COALESCE(SUM(sold_online), 0)
                WHEN ' . $filterData['filter_by'] . ' = ' . SellThroughFilterTypes::ONLY_FREE_ITEMS_SOLD->value . '
                    THEN COALESCE(SUM(foc_sold_online), 0)
                ELSE 0
            END';
    }

    private function receivedLogic(array $filterData): string
    {
        return '
             COALESCE(
                SUM(
                    ' . $this->generateOptimizedCase(
            $filterData,
            'includes_by_goods_receive_note_in_location_ids',
            SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_IN->value,
            'goods_receive_note_in'
        ) . ' +
                    ' . $this->generateOptimizedCase(
            $filterData,
            'includes_by_goods_receive_note_out_location_ids',
            SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_OUT->value,
            'goods_receive_note_out'
        ) . ' +
                    ' . $this->generateOptimizedCase(
            $filterData,
            'includes_by_stock_adjustment_in_location_ids',
            SellThroughIncludeTypes::STOCK_ADJUSTMENT_IN->value,
            'stock_adjustment_in'
        ) . ' +
                    ' . $this->generateOptimizedCase(
            $filterData,
            'includes_by_stock_adjustment_out_location_ids',
            SellThroughIncludeTypes::STOCK_ADJUSTMENT_OUT->value,
            'stock_adjustment_out'
        ) . ' +
                    ' . $this->generateOptimizedCase(
            $filterData,
            'includes_by_stock_transfer_in_location_ids',
            SellThroughIncludeTypes::STOCK_TRANSFER_IN->value,
            'stock_transfer_in'
        ) . ' +
                    ' . $this->generateOptimizedCase(
            $filterData,
            'includes_by_stock_transfer_out_location_ids',
            SellThroughIncludeTypes::STOCK_TRANSFER_OUT->value,
            'stock_transfer_out'
        ) . ' +
                    ' . $this->generateOptimizedCase(
            $filterData,
            'includes_by_delivery_order_in_location_ids',
            SellThroughIncludeTypes::DELIVERY_ORDER_IN->value,
            'delivery_order_in'
        ) . ' +
                    ' . $this->generateOptimizedCase(
            $filterData,
            'includes_by_delivery_order_out_location_ids',
            SellThroughIncludeTypes::DELIVERY_ORDER_OUT->value,
            'delivery_order_out'
        ) . '
                ), 0
            )
        ';
    }

    public function sellThroughAggregateForColorPaginate(array $filterData, int $companyId): LengthAwarePaginator
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForColor($filterData, $companyId)
                ->paginate($filterData['per_page'])
        );
    }

    public function sellThroughAggregateForColorGet(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForColor($filterData, $companyId)
                ->get()
        );
    }

    public function stockMovementSummaryForColor(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQueryStockMovementForColor($filterData, $companyId)
                ->get()
        );
    }

    private function commonQuerySellThroughForColor(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'product_id', 'sell_through_aggregates.location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('colors', 'colors.id', '=', 'products.color_id')
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('colors.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('product_id', 'colors.id');

        return SellThroughAggregate::select(
            'products.id as product_id',
            'products.color_id as color_id',
            'products.original_created_at',
            'products.created_at',
            'colors.name as name',
            DB::raw($this->getSoldLogic($filterData) . '
                 AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw($this->receivedLogic($filterData) . ' AS received'),
            DB::raw('
                CASE
                    WHEN (' . $this->receivedLogic($filterData) . " = 0) THEN 0
                    ELSE (
                        ({$this->getSoldLogic($filterData)}
                        + {$this->getOnlineSoldLogic($filterData)})
                        * 100 / {$this->receivedLogic($filterData)}
                    )
                END as sell_through
            ")
        )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('colors', 'colors.id', '=', 'products.color_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('colors.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy('products.id', 'colors.id');
    }

    private function commonQueryStockMovementForColor(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'product_id', 'sell_through_aggregates.location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('colors', 'colors.id', '=', 'products.color_id')
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('colors.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy(['product_id', 'colors.id', 'sell_through_aggregates.location_id']);

        return SellThroughAggregate::select(
            'products.id as product_id',
            'products.color_id as color_id',
            'colors.name as name',
            'locations.name as location_name',
            DB::raw($this->getSoldLogic($filterData) . '
                 AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_in), 0) as goods_receive_note_in_balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_out), 0) as goods_receive_note_out_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_in), 0) as stock_adjustment_in_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_out), 0) as stock_adjustment_out_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_in), 0) as stock_transfer_in_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_out), 0) as stock_transfer_out_balance'),
            DB::raw('COALESCE(SUM(delivery_order_in), 0) as delivery_order_in_balance'),
            DB::raw('COALESCE(SUM(delivery_order_out), 0) as delivery_order_out_balance'),
        )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('colors', 'colors.id', '=', 'products.color_id')
            ->leftJoin('locations', 'locations.id', '=', 'sell_through_aggregates.location_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('colors.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy(['products.id', 'colors.id', 'sell_through_aggregates.location_id']);
    }

    public function soldDetailsByColor(array $filterData, int $colorId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'product_id',
            'location_id',
            DB::raw('SUM(sold) as sold'),
            DB::raw('SUM(foc_sold) as foc_sold'),
            DB::raw('SUM(sell_through_aggregates.return) as return_quantity')
        )
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->join('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->join('colors', 'colors.id', '=', 'products.color_id')
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->where('colors.id', $colorId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->when(isset($filterData['date']), function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            }, function ($query) use ($filterData): void {
                $query->whereBetween('date', [$filterData['date_range'][0], $filterData['date_range'][1]]);
            })
            ->groupBy('colors.id', 'location_id')->get();
    }

    public function receivedDetailsByColor(array $filterData, int $colorId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'product_id',
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_in_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_IN->value,
                'goods_receive_note_in'
            ) . '
                    ), 0
                ) AS goods_receive_note_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_out_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_OUT->value,
                'goods_receive_note_out'
            ) . '
                    ), 0
                ) AS goods_receive_note_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_in_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_IN->value,
                'stock_adjustment_in'
            ) . '
                    ), 0
                ) AS stock_adjustment_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_out_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_OUT->value,
                'stock_adjustment_out'
            ) . '
                    ), 0
                ) AS stock_adjustment_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_in_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_IN->value,
                'stock_transfer_in'
            ) . '
                    ), 0
                ) AS stock_transfer_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_out_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_OUT->value,
                'stock_transfer_out'
            ) . '
                    ), 0
                ) AS stock_transfer_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_in_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_IN->value,
                'delivery_order_in'
            ) . '
                    ), 0
                ) AS delivery_order_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_out_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_OUT->value,
                'delivery_order_out'
            ) . '
                    ), 0
                ) AS delivery_order_out_balance')
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->join('colors', 'colors.id', '=', 'products.color_id')
            ->where('colors.id', $colorId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('colors.id', 'location_id')
            ->get();
    }

    public function balanceDetailsByColor(array $filterData, int $colorId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'product_id',
            DB::raw('COALESCE(SUM(balance), 0) AS balance'),
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->join('colors', 'colors.id', '=', 'products.color_id')
            ->where('colors.id', $colorId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('colors.id', 'location_id')
            ->get();
    }

    public function stockMovementSummaryForAttribute(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQueryStockMovementForAttribute($filterData, $companyId)
                ->get()
        );
    }

    private function commonQueryStockMovementForAttribute(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(
                DB::raw('SUM(balance) as balance'),
                'products.id as product_id',
                'sell_through_aggregates.location_id'
            )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id')
            ->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id')
            ->leftJoin('attributes', 'attributes.id', '=', 'product_variant_values.attribute_id')
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('product_variant_values.value', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->where('attributes.id', $filterData['attribute_type'])
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy(['product_variant_values.value', 'sell_through_aggregates.location_id']);

        return SellThroughAggregate::select(
            'products.id as product_id',
            'product_variant_values.attribute_id as attribute_id',
            'product_variant_values.value as name',
            'locations.name as location_name',
            DB::raw($this->getSoldLogic($filterData) . '
                 AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_in), 0) as goods_receive_note_in_balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_out), 0) as goods_receive_note_out_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_in), 0) as stock_adjustment_in_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_out), 0) as stock_adjustment_out_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_in), 0) as stock_transfer_in_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_out), 0) as stock_transfer_out_balance'),
            DB::raw('COALESCE(SUM(delivery_order_in), 0) as delivery_order_in_balance'),
            DB::raw('COALESCE(SUM(delivery_order_out), 0) as delivery_order_out_balance'),
        )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id')
            ->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id')
            ->leftJoin('attributes', 'attributes.id', '=', 'product_variant_values.attribute_id')
            ->leftJoin('locations', 'locations.id', '=', 'sell_through_aggregates.location_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('product_variant_values.value', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->where('attributes.id', $filterData['attribute_type'])
            ->groupBy(['product_variant_values.value', 'sell_through_aggregates.location_id']);
    }

    public function sellThroughAggregateForSizePaginate(array $filterData, int $companyId): LengthAwarePaginator
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForSize($filterData, $companyId)
                ->paginate($filterData['per_page'])
        );
    }

    public function stockMovementSummaryForSize(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQueryStockMovementSummaryForSize($filterData, $companyId)
                ->get()
        );
    }

    public function sellThroughAggregateForSizeGet(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForSize($filterData, $companyId)
                ->get()
        );
    }

    private function commonQuerySellThroughForSize(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'product_id', 'location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('sizes', 'sizes.id', '=', 'products.size_id')
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('sizes.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('sizes.id');

        return SellThroughAggregate::select(
            'products.id as product_id',
            'products.size_id as size_id',
            'sizes.name as name',
            'products.original_created_at',
            'products.created_at',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw($this->receivedLogic($filterData) . ' AS received'),
            DB::raw('
                            CASE
                                WHEN (' . $this->receivedLogic($filterData) . " = 0) THEN 0
                                ELSE (
                                    ({$this->getSoldLogic($filterData)}
                                    + {$this->getOnlineSoldLogic($filterData)})
                                    * 100 / {$this->receivedLogic($filterData)}
                                )
                            END as sell_through
                        ")
        )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('sizes', 'sizes.id', '=', 'products.size_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('sizes.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy('sizes.id');
    }

    private function commonQueryStockMovementSummaryForSize(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'product_id', 'location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('sizes', 'sizes.id', '=', 'products.size_id')
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('sizes.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy(['sizes.id', 'location_id']);

        return SellThroughAggregate::select(
            'products.id as product_id',
            'products.size_id as size_id',
            'sizes.name as name',
            'locations.name as location_name',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_in), 0) as goods_receive_note_in_balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_out), 0) as goods_receive_note_out_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_in), 0) as stock_adjustment_in_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_out), 0) as stock_adjustment_out_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_in), 0) as stock_transfer_in_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_out), 0) as stock_transfer_out_balance'),
            DB::raw('COALESCE(SUM(delivery_order_in), 0) as delivery_order_in_balance'),
            DB::raw('COALESCE(SUM(delivery_order_out), 0) as delivery_order_out_balance'),
        )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('locations', 'locations.id', '=', 'sell_through_aggregates.location_id')
            ->leftJoin('sizes', 'sizes.id', '=', 'products.size_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('sizes.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy(['sizes.id', 'sell_through_aggregates.location_id']);
    }

    public function soldDetailsBySize(array $filterData, int $sizeId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'product_id',
            'location_id',
            DB::raw('SUM(sold) as sold'),
            DB::raw('SUM(foc_sold) as foc_sold'),
            DB::raw('SUM(sell_through_aggregates.return) as return_quantity')
        )
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->join('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->join('sizes', 'sizes.id', '=', 'products.size_id')
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->where('sizes.id', $sizeId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->when(isset($filterData['date']), function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            }, function ($query) use ($filterData): void {
                $query->whereBetween('date', [$filterData['date_range'][0], $filterData['date_range'][1]]);
            })
            ->groupBy('sizes.id', 'location_id')->get();
    }

    public function receivedDetailsBySize(array $filterData, int $sizeId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'product_id',
            DB::raw('COALESCE(
                        SUM(
                            ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_in_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_IN->value,
                'goods_receive_note_in'
            ) . '
                        ), 0
                    ) AS goods_receive_note_in_balance'),
            DB::raw('COALESCE(
                        SUM(
                            ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_out_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_OUT->value,
                'goods_receive_note_out'
            ) . '
                        ), 0
                    ) AS goods_receive_note_out_balance'),
            DB::raw('COALESCE(
                        SUM(
                            ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_in_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_IN->value,
                'stock_adjustment_in'
            ) . '
                        ), 0
                    ) AS stock_adjustment_in_balance'),
            DB::raw('COALESCE(
                        SUM(
                            ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_out_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_OUT->value,
                'stock_adjustment_out'
            ) . '
                        ), 0
                    ) AS stock_adjustment_out_balance'),
            DB::raw('COALESCE(
                        SUM(
                            ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_in_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_IN->value,
                'stock_transfer_in'
            ) . '
                        ), 0
                    ) AS stock_transfer_in_balance'),
            DB::raw('COALESCE(
                        SUM(
                            ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_out_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_OUT->value,
                'stock_transfer_out'
            ) . '
                        ), 0
                    ) AS stock_transfer_out_balance'),
            DB::raw('COALESCE(
                        SUM(
                            ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_in_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_IN->value,
                'delivery_order_in'
            ) . '
                        ), 0
                    ) AS delivery_order_in_balance'),
            DB::raw('COALESCE(
                        SUM(
                            ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_out_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_OUT->value,
                'delivery_order_out'
            ) . '
                        ), 0
                    ) AS delivery_order_out_balance')
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->join('sizes', 'sizes.id', '=', 'products.size_id')
            ->where('sizes.id', $sizeId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('sizes.id', 'location_id')
            ->get();
    }

    public function balanceDetailsBySize(array $filterData, int $sizeId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'product_id',
            DB::raw('COALESCE(SUM(balance), 0) AS balance'),
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->join('sizes', 'sizes.id', '=', 'products.size_id')
            ->where('sizes.id', $sizeId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('sizes.id', 'location_id')
            ->get();
    }

    public function sellThroughAggregateForStylePaginate(array $filterData, int $companyId): LengthAwarePaginator
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForStyle($filterData, $companyId)
                ->paginate($filterData['per_page'])
        );
    }

    public function stockMovementSummaryForStyle(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQueryStockMovementForStyle($filterData, $companyId)
                ->get()
        );
    }

    public function sellThroughAggregateForStyleGet(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForStyle($filterData, $companyId)
                ->get()
        );
    }

    private function commonQuerySellThroughForStyle(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'product_id', 'location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('styles', 'styles.id', '=', 'products.style_id')
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('styles.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('styles.id');

        return SellThroughAggregate::select(
            'products.id as product_id',
            'products.style_id as style_id',
            'styles.name as name',
            'products.original_created_at',
            'products.created_at',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw($this->receivedLogic($filterData) . ' AS received'),
            DB::raw('
                    CASE
                        WHEN (' . $this->receivedLogic($filterData) . " = 0) THEN 0
                        ELSE (
                            ({$this->getSoldLogic($filterData)}
                            + {$this->getOnlineSoldLogic($filterData)})
                            * 100 / {$this->receivedLogic($filterData)}
                        )
                    END as sell_through
                ")
        )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('styles', 'styles.id', '=', 'products.style_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('styles.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy('styles.id');
    }

    private function commonQueryStockMovementForStyle(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'product_id', 'location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('styles', 'styles.id', '=', 'products.style_id')
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('styles.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy(['styles.id', 'location_id']);

        return SellThroughAggregate::select(
            'products.id as product_id',
            'products.style_id as style_id',
            'styles.name as name',
            'locations.name as location_name',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_in), 0) as goods_receive_note_in_balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_out), 0) as goods_receive_note_out_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_in), 0) as stock_adjustment_in_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_out), 0) as stock_adjustment_out_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_in), 0) as stock_transfer_in_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_out), 0) as stock_transfer_out_balance'),
            DB::raw('COALESCE(SUM(delivery_order_in), 0) as delivery_order_in_balance'),
            DB::raw('COALESCE(SUM(delivery_order_out), 0) as delivery_order_out_balance'),
        )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('styles', 'styles.id', '=', 'products.style_id')
            ->leftJoin('locations', 'locations.id', '=', 'sell_through_aggregates.location_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('styles.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy(['styles.id', 'sell_through_aggregates.location_id']);
    }

    public function soldDetailsByStyle(array $filterData, int $styleId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'product_id',
            'location_id',
            DB::raw('SUM(sold) as sold'),
            DB::raw('SUM(foc_sold) as foc_sold'),
            DB::raw('SUM(sell_through_aggregates.return) as return_quantity')
        )
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->join('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->join('styles', 'styles.id', '=', 'products.style_id')
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->where('styles.id', $styleId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->when(isset($filterData['date']), function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            }, function ($query) use ($filterData): void {
                $query->whereBetween('date', [$filterData['date_range'][0], $filterData['date_range'][1]]);
            })
            ->groupBy('styles.id', 'location_id')->get();
    }

    public function receivedDetailsByStyle(array $filterData, int $styleId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'product_id',
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_in_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_IN->value,
                'goods_receive_note_in'
            ) . '
                    ), 0
                ) AS goods_receive_note_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_out_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_OUT->value,
                'goods_receive_note_out'
            ) . '
                    ), 0
                ) AS goods_receive_note_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_in_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_IN->value,
                'stock_adjustment_in'
            ) . '
                    ), 0
                ) AS stock_adjustment_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_out_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_OUT->value,
                'stock_adjustment_out'
            ) . '
                    ), 0
                ) AS stock_adjustment_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_in_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_IN->value,
                'stock_transfer_in'
            ) . '
                    ), 0
                ) AS stock_transfer_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_out_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_OUT->value,
                'stock_transfer_out'
            ) . '
                    ), 0
                ) AS stock_transfer_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_in_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_IN->value,
                'delivery_order_in'
            ) . '
                    ), 0
                ) AS delivery_order_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_out_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_OUT->value,
                'delivery_order_out'
            ) . '
                    ), 0
                ) AS delivery_order_out_balance')
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->join('styles', 'styles.id', '=', 'products.style_id')
            ->where('styles.id', $styleId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('styles.id', 'location_id')
            ->get();
    }

    public function balanceDetailsByStyle(array $filterData, int $styleId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'product_id',
            DB::raw('COALESCE(SUM(balance), 0) AS balance'),
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->join('styles', 'styles.id', '=', 'products.style_id')
            ->where('styles.id', $styleId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('styles.id', 'location_id')
            ->get();
    }

    public function sellThroughAggregateForAttributePaginate(array $filterData, int $companyId): LengthAwarePaginator
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForAttribute($filterData, $companyId)
                ->paginate($filterData['per_page'])
        );
    }

    public function sellThroughAggregateForAttributeGet(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForAttribute($filterData, $companyId)
                ->get()
        );
    }

    private function commonQuerySellThroughForAttribute(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'products.id as product_id', 'location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id')
            ->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id')
            ->leftJoin('attributes', 'attributes.id', '=', 'product_variant_values.attribute_id')
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('product_variant_values.value', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->where('attributes.id', $filterData['attribute_type'])
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('product_variant_values.value');

        return SellThroughAggregate::select(
            'products.id as product_id',
            'product_variant_values.attribute_id as attribute_id',
            'product_variant_values.value as name',
            'products.original_created_at',
            'products.created_at',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw($this->receivedLogic($filterData) . ' AS received'),
            DB::raw('
                            CASE
                                WHEN (' . $this->receivedLogic($filterData) . " = 0) THEN 0
                                ELSE (
                                    ({$this->getSoldLogic($filterData)}
                                    + {$this->getOnlineSoldLogic($filterData)})
                                    * 100 / {$this->receivedLogic($filterData)}
                                )
                            END as sell_through
                        ")
        )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id')
            ->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id')
            ->leftJoin('attributes', 'attributes.id', '=', 'product_variant_values.attribute_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('product_variant_values.value', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->where('attributes.id', $filterData['attribute_type'])
            ->groupBy('product_variant_values.value');
    }

    public function soldDetailsByAttribute(array $filterData, string $attribute, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'products.id as product_id',
            'location_id',
            DB::raw('SUM(sold) as sold'),
            DB::raw('SUM(foc_sold) as foc_sold'),
            DB::raw('SUM(sell_through_aggregates.return) as return_quantity')
        )
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->join('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id')
            ->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id')
            ->leftJoin('attributes', 'attributes.id', '=', 'product_variant_values.attribute_id')
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->where('product_variant_values.value', $attribute)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->when(isset($filterData['date']), function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            }, function ($query) use ($filterData): void {
                $query->whereBetween('date', [$filterData['date_range'][0], $filterData['date_range'][1]]);
            })
            ->groupBy('product_variant_values.value', 'location_id')->get();
    }

    public function receivedDetailsByAttribute(array $filterData, string $attribute, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'products.id as product_id',
            DB::raw('COALESCE(
                        SUM(
                            ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_in_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_IN->value,
                'goods_receive_note_in'
            ) . '
                        ), 0
                    ) AS goods_receive_note_in_balance'),
            DB::raw('COALESCE(
                        SUM(
                            ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_out_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_OUT->value,
                'goods_receive_note_out'
            ) . '
                        ), 0
                    ) AS goods_receive_note_out_balance'),
            DB::raw('COALESCE(
                        SUM(
                            ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_in_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_IN->value,
                'stock_adjustment_in'
            ) . '
                        ), 0
                    ) AS stock_adjustment_in_balance'),
            DB::raw('COALESCE(
                        SUM(
                            ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_out_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_OUT->value,
                'stock_adjustment_out'
            ) . '
                        ), 0
                    ) AS stock_adjustment_out_balance'),
            DB::raw('COALESCE(
                        SUM(
                            ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_in_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_IN->value,
                'stock_transfer_in'
            ) . '
                        ), 0
                    ) AS stock_transfer_in_balance'),
            DB::raw('COALESCE(
                        SUM(
                            ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_out_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_OUT->value,
                'stock_transfer_out'
            ) . '
                        ), 0
                    ) AS stock_transfer_out_balance'),
            DB::raw('COALESCE(
                        SUM(
                            ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_in_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_IN->value,
                'delivery_order_in'
            ) . '
                        ), 0
                    ) AS delivery_order_in_balance'),
            DB::raw('COALESCE(
                        SUM(
                            ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_out_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_OUT->value,
                'delivery_order_out'
            ) . '
                        ), 0
                    ) AS delivery_order_out_balance')
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id')
            ->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id')
            ->leftJoin('attributes', 'attributes.id', '=', 'product_variant_values.attribute_id')
            ->where('product_variant_values.value', $attribute)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('product_variant_values.value', 'location_id')
            ->get();
    }

    public function balanceDetailsByAttribute(array $filterData, string $attribute, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'products.id as product_id',
            DB::raw('COALESCE(SUM(balance), 0) AS balance'),
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id')
            ->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id')
            ->leftJoin('attributes', 'attributes.id', '=', 'product_variant_values.attribute_id')
            ->where('product_variant_values.value', $attribute)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('product_variant_values.value', 'location_id')
            ->get();
    }

    public function sellThroughAggregateForBrandPaginate(array $filterData, int $companyId): LengthAwarePaginator
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForBrand($filterData, $companyId)
                ->paginate($filterData['per_page'])
        );
    }

    public function stockMovementSummaryForBrand(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQueryStockMovementForBrand($filterData, $companyId)
                ->get()
        );
    }

    public function sellThroughAggregateForBrandGet(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForBrand($filterData, $companyId)
                ->get()
        );
    }

    private function commonQuerySellThroughForBrand(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'product_id', 'location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('brands.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('brands.id');

        return SellThroughAggregate::select(
            'products.id as product_id',
            'products.brand_id as brand_id',
            'brands.name as name',
            'products.original_created_at',
            'products.created_at',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw($this->receivedLogic($filterData) . ' AS received'),
            DB::raw('
                    CASE
                        WHEN (' . $this->receivedLogic($filterData) . " = 0) THEN 0
                        ELSE (
                            ({$this->getSoldLogic($filterData)}
                            + {$this->getOnlineSoldLogic($filterData)})
                            * 100 / {$this->receivedLogic($filterData)}
                        )
                    END as sell_through
                ")
        )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('brands.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy('brands.id');
    }

    private function commonQueryStockMovementForBrand(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'product_id', 'location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('brands.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy(['brands.id', 'location_id']);

        return SellThroughAggregate::select(
            'products.id as product_id',
            'products.brand_id as brand_id',
            'brands.name as name',
            'locations.name as location_name',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_in), 0) as goods_receive_note_in_balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_out), 0) as goods_receive_note_out_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_in), 0) as stock_adjustment_in_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_out), 0) as stock_adjustment_out_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_in), 0) as stock_transfer_in_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_out), 0) as stock_transfer_out_balance'),
            DB::raw('COALESCE(SUM(delivery_order_in), 0) as delivery_order_in_balance'),
            DB::raw('COALESCE(SUM(delivery_order_out), 0) as delivery_order_out_balance'),
        )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->leftJoin('locations', 'locations.id', '=', 'sell_through_aggregates.location_id')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('brands.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy(['brands.id', 'sell_through_aggregates.location_id']);
    }

    public function soldDetailsByBrand(array $filterData, int $brandId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'product_id',
            'location_id',
            DB::raw('SUM(sold) as sold'),
            DB::raw('SUM(foc_sold) as foc_sold'),
            DB::raw('SUM(sell_through_aggregates.return) as return_quantity')
        )
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->join('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->where('brands.id', $brandId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->when(isset($filterData['date']), function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            }, function ($query) use ($filterData): void {
                $query->whereBetween('date', [$filterData['date_range'][0], $filterData['date_range'][1]]);
            })
            ->groupBy('brands.id', 'location_id')->get();
    }

    public function receivedDetailsByBrand(array $filterData, int $brandId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'product_id',
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_in_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_IN->value,
                'goods_receive_note_in'
            ) . '
                    ), 0
                ) AS goods_receive_note_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_out_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_OUT->value,
                'goods_receive_note_out'
            ) . '
                    ), 0
                ) AS goods_receive_note_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_in_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_IN->value,
                'stock_adjustment_in'
            ) . '
                    ), 0
                ) AS stock_adjustment_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_out_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_OUT->value,
                'stock_adjustment_out'
            ) . '
                    ), 0
                ) AS stock_adjustment_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_in_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_IN->value,
                'stock_transfer_in'
            ) . '
                    ), 0
                ) AS stock_transfer_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_out_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_OUT->value,
                'stock_transfer_out'
            ) . '
                    ), 0
                ) AS stock_transfer_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_in_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_IN->value,
                'delivery_order_in'
            ) . '
                    ), 0
                ) AS delivery_order_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_out_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_OUT->value,
                'delivery_order_out'
            ) . '
                    ), 0
                ) AS delivery_order_out_balance')
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->where('brands.id', $brandId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('brands.id', 'location_id')
            ->get();
    }

    public function balanceDetailsByBrand(array $filterData, int $brandId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'product_id',
            DB::raw('COALESCE(SUM(balance), 0) AS balance'),
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->where('brands.id', $brandId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('brands.id', 'location_id')
            ->get();
    }

    public function sellThroughAggregateForDepartmentPaginate(array $filterData, int $companyId): LengthAwarePaginator
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForDepartment($filterData, $companyId)
                ->paginate($filterData['per_page'])
        );
    }

    public function stockMovementForDepartment(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQueryStockMovementForDepartment($filterData, $companyId)
                ->get()
        );
    }

    public function sellThroughAggregateForDepartmentGet(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForDepartment($filterData, $companyId)
                ->get()
        );
    }

    private function commonQuerySellThroughForDepartment(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'product_id', 'location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('departments', 'departments.id', '=', 'products.department_id')
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('departments.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('departments.id');

        return SellThroughAggregate::select(
            'products.id as product_id',
            'products.department_id as department_id',
            'departments.name as name',
            'products.original_created_at',
            'products.created_at',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw($this->receivedLogic($filterData) . ' AS received'),
            DB::raw('
                    CASE
                        WHEN (' . $this->receivedLogic($filterData) . " = 0) THEN 0
                        ELSE (
                            ({$this->getSoldLogic($filterData)}
                            + {$this->getOnlineSoldLogic($filterData)})
                            * 100 / {$this->receivedLogic($filterData)}
                        )
                    END as sell_through
                ")
        )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('departments', 'departments.id', '=', 'products.department_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('departments.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy('departments.id');
    }

    private function commonQueryStockMovementForDepartment(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'product_id', 'location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('departments', 'departments.id', '=', 'products.department_id')
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('departments.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy(['departments.id', 'location_id']);

        return SellThroughAggregate::select(
            'products.id as product_id',
            'products.department_id as department_id',
            'departments.name as name',
            'locations.name as location_name',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_in), 0) as goods_receive_note_in_balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_out), 0) as goods_receive_note_out_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_in), 0) as stock_adjustment_in_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_out), 0) as stock_adjustment_out_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_in), 0) as stock_transfer_in_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_out), 0) as stock_transfer_out_balance'),
            DB::raw('COALESCE(SUM(delivery_order_in), 0) as delivery_order_in_balance'),
            DB::raw('COALESCE(SUM(delivery_order_out), 0) as delivery_order_out_balance'),
        )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('departments', 'departments.id', '=', 'products.department_id')
            ->leftJoin('locations', 'locations.id', '=', 'sell_through_aggregates.location_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('departments.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy(['departments.id', 'sell_through_aggregates.location_id']);
    }

    public function soldDetailsByDepartment(array $filterData, int $departmentId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'product_id',
            'location_id',
            DB::raw('SUM(sold) as sold'),
            DB::raw('SUM(foc_sold) as foc_sold'),
            DB::raw('SUM(sell_through_aggregates.return) as return_quantity')
        )
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->join('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->join('departments', 'departments.id', '=', 'products.department_id')
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->where('departments.id', $departmentId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->when(isset($filterData['date']), function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            }, function ($query) use ($filterData): void {
                $query->whereBetween('date', [$filterData['date_range'][0], $filterData['date_range'][1]]);
            })
            ->groupBy('departments.id', 'location_id')->get();
    }

    public function receivedDetailsByDepartment(array $filterData, int $departmentId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'product_id',
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_in_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_IN->value,
                'goods_receive_note_in'
            ) . '
                    ), 0
                ) AS goods_receive_note_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_out_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_OUT->value,
                'goods_receive_note_out'
            ) . '
                    ), 0
                ) AS goods_receive_note_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_in_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_IN->value,
                'stock_adjustment_in'
            ) . '
                    ), 0
                ) AS stock_adjustment_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_out_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_OUT->value,
                'stock_adjustment_out'
            ) . '
                    ), 0
                ) AS stock_adjustment_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_in_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_IN->value,
                'stock_transfer_in'
            ) . '
                    ), 0
                ) AS stock_transfer_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_out_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_OUT->value,
                'stock_transfer_out'
            ) . '
                    ), 0
                ) AS stock_transfer_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_in_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_IN->value,
                'delivery_order_in'
            ) . '
                    ), 0
                ) AS delivery_order_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_out_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_OUT->value,
                'delivery_order_out'
            ) . '
                    ), 0
                ) AS delivery_order_out_balance')
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->join('departments', 'departments.id', '=', 'products.department_id')
            ->where('departments.id', $departmentId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('departments.id', 'location_id')
            ->get();
    }

    public function balanceDetailsByDepartment(array $filterData, int $departmentId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'product_id',
            DB::raw('COALESCE(SUM(balance), 0) AS balance'),
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->join('departments', 'departments.id', '=', 'products.department_id')
            ->where('departments.id', $departmentId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('departments.id', 'location_id')
            ->get();
    }

    public function sellThroughAggregateForLocationPaginate(array $filterData, int $companyId): LengthAwarePaginator
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForLocation($filterData, $companyId)
                ->paginate($filterData['per_page'])
        );
    }

    public function stockMovementForLocation(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQueryStockMovementForLocation($filterData, $companyId)
                ->get()
        );
    }

    public function sellThroughAggregateForLocationGet(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForLocation($filterData, $companyId)
                ->get()
        );
    }

    public function soldDetailsByLocation(array $filterData, int $locationId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'product_id',
            'location_id',
            DB::raw('SUM(sold) as sold'),
            DB::raw('SUM(foc_sold) as foc_sold'),
            DB::raw('SUM(sell_through_aggregates.return) as return_quantity')
        )
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->join('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->where('location_id', $locationId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->when(isset($filterData['date']), function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            }, function ($query) use ($filterData): void {
                $query->whereBetween('date', [$filterData['date_range'][0], $filterData['date_range'][1]]);
            })
            ->groupBy('location_id')->get();
    }

    public function receivedDetailsByLocation(array $filterData, int $locationId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'product_id',
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_in_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_IN->value,
                'goods_receive_note_in'
            ) . '
                    ), 0
                ) AS goods_receive_note_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_out_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_OUT->value,
                'goods_receive_note_out'
            ) . '
                    ), 0
                ) AS goods_receive_note_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_in_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_IN->value,
                'stock_adjustment_in'
            ) . '
                    ), 0
                ) AS stock_adjustment_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_out_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_OUT->value,
                'stock_adjustment_out'
            ) . '
                    ), 0
                ) AS stock_adjustment_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_in_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_IN->value,
                'stock_transfer_in'
            ) . '
                    ), 0
                ) AS stock_transfer_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_out_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_OUT->value,
                'stock_transfer_out'
            ) . '
                    ), 0
                ) AS stock_transfer_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_in_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_IN->value,
                'delivery_order_in'
            ) . '
                    ), 0
                ) AS delivery_order_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_out_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_OUT->value,
                'delivery_order_out'
            ) . '
                    ), 0
                ) AS delivery_order_out_balance')
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->where('location_id', $locationId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('location_id')
            ->get();
    }

    public function balanceDetailsByLocation(array $filterData, int $locationId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'product_id',
            DB::raw('COALESCE(SUM(balance), 0) AS balance'),
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->where('location_id', $locationId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('location_id')
            ->get();
    }

    private function commonQuerySellThroughForLocation(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'product_id', 'location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('locations', 'locations.id', '=', 'sell_through_aggregates.location_id')
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('locations.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('location_id');

        return SellThroughAggregate::select(
            'products.id as product_id',
            'sell_through_aggregates.location_id as location_id',
            'locations.name as name',
            'locations.code as code',
            'products.original_created_at',
            'products.created_at',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw($this->receivedLogic($filterData) . ' AS received'),
            DB::raw('
                        CASE
                            WHEN (' . $this->receivedLogic($filterData) . " = 0) THEN 0
                            ELSE (
                                ({$this->getSoldLogic($filterData)}
                                + {$this->getOnlineSoldLogic($filterData)})
                                * 100 / {$this->receivedLogic($filterData)}
                            )
                        END as sell_through
                    ")
        )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('locations', 'locations.id', '=', 'sell_through_aggregates.location_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('locations.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy('sell_through_aggregates.location_id');
    }

    private function commonQueryStockMovementForLocation(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'product_id', 'location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('locations', 'locations.id', '=', 'sell_through_aggregates.location_id')
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('locations.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('location_id');

        return SellThroughAggregate::select(
            'products.id as product_id',
            'sell_through_aggregates.location_id as location_id',
            'locations.name as name',
            'locations.code as code',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_in), 0) as goods_receive_note_in_balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_out), 0) as goods_receive_note_out_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_in), 0) as stock_adjustment_in_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_out), 0) as stock_adjustment_out_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_in), 0) as stock_transfer_in_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_out), 0) as stock_transfer_out_balance'),
            DB::raw('COALESCE(SUM(delivery_order_in), 0) as delivery_order_in_balance'),
            DB::raw('COALESCE(SUM(delivery_order_out), 0) as delivery_order_out_balance'),
        )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('locations', 'locations.id', '=', 'sell_through_aggregates.location_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('locations.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy('sell_through_aggregates.location_id');
    }

    public function sellThroughAggregateForCategoryPaginate(array $filterData, int $companyId): LengthAwarePaginator
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForCategory($filterData, $companyId)
                ->paginate($filterData['per_page'])
        );
    }

    public function stockMovementForCategory(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQueryStockMovementForCategory($filterData, $companyId)
                ->get()
        );
    }

    public function sellThroughAggregateForCategoryGet(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForCategory($filterData, $companyId)
                ->get()
        );
    }

    public function soldDetailsByCategory(array $filterData, int $categoryId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'sell_through_aggregates.product_id',
            'location_id',
            DB::raw('SUM(sold) as sold'),
            DB::raw('SUM(foc_sold) as foc_sold'),
            DB::raw('SUM(sell_through_aggregates.return) as return_quantity')
        )
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->join('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('category_product', 'category_product.product_id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'category_product.category_id')
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->where('categories.id', $categoryId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->when(isset($filterData['date']), function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            }, function ($query) use ($filterData): void {
                $query->whereBetween('date', [$filterData['date_range'][0], $filterData['date_range'][1]]);
            })
            ->groupBy('categories.id')->get();
    }

    public function receivedDetailsByCategory(array $filterData, int $categoryId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'sell_through_aggregates.product_id',
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_in_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_IN->value,
                'goods_receive_note_in'
            ) . '
                    ), 0
                ) AS goods_receive_note_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_out_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_OUT->value,
                'goods_receive_note_out'
            ) . '
                    ), 0
                ) AS goods_receive_note_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_in_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_IN->value,
                'stock_adjustment_in'
            ) . '
                    ), 0
                ) AS stock_adjustment_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_out_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_OUT->value,
                'stock_adjustment_out'
            ) . '
                    ), 0
                ) AS stock_adjustment_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_in_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_IN->value,
                'stock_transfer_in'
            ) . '
                    ), 0
                ) AS stock_transfer_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_out_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_OUT->value,
                'stock_transfer_out'
            ) . '
                    ), 0
                ) AS stock_transfer_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_in_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_IN->value,
                'delivery_order_in'
            ) . '
                    ), 0
                ) AS delivery_order_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_out_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_OUT->value,
                'delivery_order_out'
            ) . '
                    ), 0
                ) AS delivery_order_out_balance')
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('category_product', 'category_product.product_id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'category_product.category_id')
            ->where('categories.id', $categoryId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('categories.id')
            ->get();
    }

    public function balanceDetailsByCategory(array $filterData, int $categoryId, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'sell_through_aggregates.product_id',
            DB::raw('COALESCE(SUM(balance), 0) AS balance'),
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('category_product', 'category_product.product_id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'category_product.category_id')
            ->where('categories.id', $categoryId)
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('categories.id')
            ->get();
    }

    private function commonQuerySellThroughForCategory(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'sell_through_aggregates.product_id', 'location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('category_product', 'category_product.product_id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'category_product.category_id')
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('categories.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('categories.id');

        return SellThroughAggregate::select(
            'products.id as product_id',
            'categories.id as category_id',
            'categories.name as name',
            'categories.code as code',
            'products.original_created_at',
            'products.created_at',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw($this->receivedLogic($filterData) . ' AS received'),
            DB::raw('
                        CASE
                            WHEN (' . $this->receivedLogic($filterData) . " = 0) THEN 0
                            ELSE (
                                ({$this->getSoldLogic($filterData)}
                                + {$this->getOnlineSoldLogic($filterData)})
                                * 100 / {$this->receivedLogic($filterData)}
                            )
                        END as sell_through
                    ")
        )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('category_product', 'category_product.product_id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'category_product.category_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('categories.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy('categories.id');
    }

    private function commonQueryStockMovementForCategory(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'sell_through_aggregates.product_id', 'location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('category_product', 'category_product.product_id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'category_product.category_id')
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('categories.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy(['categories.id', 'location_id']);

        return SellThroughAggregate::select(
            'products.id as product_id',
            'categories.id as category_id',
            'categories.name as name',
            'categories.code as code',
            'locations.name as location_name',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_in), 0) as goods_receive_note_in_balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_out), 0) as goods_receive_note_out_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_in), 0) as stock_adjustment_in_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_out), 0) as stock_adjustment_out_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_in), 0) as stock_transfer_in_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_out), 0) as stock_transfer_out_balance'),
            DB::raw('COALESCE(SUM(delivery_order_in), 0) as delivery_order_in_balance'),
            DB::raw('COALESCE(SUM(delivery_order_out), 0) as delivery_order_out_balance'),
        )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('category_product', 'category_product.product_id', '=', 'sell_through_aggregates.product_id')
            ->leftJoin('locations', 'locations.id', '=', 'sell_through_aggregates.location_id')
            ->leftJoin('categories', 'categories.id', '=', 'category_product.category_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where('categories.name', 'like', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy(['categories.id', 'sell_through_aggregates.location_id']);
    }

    public function sellThroughAggregateForArticleNumberPaginate(
        array $filterData,
        int $companyId
    ): LengthAwarePaginator {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForArticleNumber($filterData, $companyId)
                ->paginate($filterData['per_page'])
        );
    }

    public function stockMovementForArticleNumber(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQueryStockMovementForArticleNumber($filterData, $companyId)
                ->get()
        );
    }

    public function sellThroughAggregateForArticleNumberGet(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForArticleNumber($filterData, $companyId)
                ->get()
        );
    }

    public function soldDetailsByArticleNumber(array $filterData, string $articleNumber, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'product_id',
            'location_id',
            DB::raw('SUM(sold) as sold'),
            DB::raw('SUM(foc_sold) as foc_sold'),
            DB::raw('SUM(sell_through_aggregates.return) as return_quantity')
        )
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->join('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(config('app.product_variant'), function ($query) use ($articleNumber): void {
                $query->where('master_products.article_number', $articleNumber);
            }, function ($query) use ($articleNumber): void {
                $query->where('products.article_number', $articleNumber);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->when(isset($filterData['date']), function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            }, function ($query) use ($filterData): void {
                $query->whereBetween('date', [$filterData['date_range'][0], $filterData['date_range'][1]]);
            })
            ->groupBy('location_id')
            ->get();
    }

    public function receivedDetailsByArticleNumber(array $filterData, string $articleNumber, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'sell_through_aggregates.product_id',
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_in_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_IN->value,
                'goods_receive_note_in'
            ) . '
                    ), 0
                ) AS goods_receive_note_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_goods_receive_note_out_location_ids',
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_OUT->value,
                'goods_receive_note_out'
            ) . '
                    ), 0
                ) AS goods_receive_note_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_in_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_IN->value,
                'stock_adjustment_in'
            ) . '
                    ), 0
                ) AS stock_adjustment_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_adjustment_out_location_ids',
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_OUT->value,
                'stock_adjustment_out'
            ) . '
                    ), 0
                ) AS stock_adjustment_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_in_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_IN->value,
                'stock_transfer_in'
            ) . '
                    ), 0
                ) AS stock_transfer_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_stock_transfer_out_location_ids',
                SellThroughIncludeTypes::STOCK_TRANSFER_OUT->value,
                'stock_transfer_out'
            ) . '
                    ), 0
                ) AS stock_transfer_out_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_in_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_IN->value,
                'delivery_order_in'
            ) . '
                    ), 0
                ) AS delivery_order_in_balance'),
            DB::raw('COALESCE(
                    SUM(
                        ' . $this->generateOptimizedCase(
                $filterData,
                'includes_by_delivery_order_out_location_ids',
                SellThroughIncludeTypes::DELIVERY_ORDER_OUT->value,
                'delivery_order_out'
            ) . '
                    ), 0
                ) AS delivery_order_out_balance')
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->when(config('app.product_variant'), function ($query) use ($articleNumber): void {
                $query->where('master_products.article_number', $articleNumber);
            }, function ($query) use ($articleNumber): void {
                $query->where('products.article_number', $articleNumber);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('location_id')
            ->get();
    }

    public function balanceDetailsByArticleNumber(array $filterData, string $articleNumber, int $companyId): Collection
    {
        $productQueries = new ProductQueries();
        $locationQueries = new LocationQueries();

        return SellThroughAggregate::select(
            'location_id',
            'sell_through_aggregates.product_id',
            DB::raw('COALESCE(SUM(balance), 0) AS balance'),
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->whereBetween('date', $filterData['date_range']);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->when(config('app.product_variant'), function ($query) use ($articleNumber): void {
                $query->where('master_products.article_number', $articleNumber);
            }, function ($query) use ($articleNumber): void {
                $query->where('products.article_number', $articleNumber);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->with('location:' . $locationQueries->getRegionColumnNames())
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('location_id')
            ->get();
    }

    private function commonQuerySellThroughForArticleNumber(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(
                DB::raw('SUM(balance) as balance'),
                'sell_through_aggregates.product_id',
                'sell_through_aggregates.location_id'
            )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query
                    ->whereAny(['products.name', 'products.upc'], 'LIKE', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy(config('app.product_variant') ? 'master_products.article_number' : 'products.article_number');

        return SellThroughAggregate::select(
            'products.id as product_id',
            'products.name',
            'products.original_created_at',
            'products.created_at',
            'products.retail_price as price',
            config(
                'app.product_variant'
            ) ? 'master_products.article_number as article_number' : 'products.article_number as article_number',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw($this->receivedLogic($filterData) . ' AS received'),
            DB::raw('
                    CASE
                        WHEN (' . $this->receivedLogic($filterData) . " = 0) THEN 0
                        ELSE (
                            ({$this->getSoldLogic($filterData)}
                            + {$this->getOnlineSoldLogic($filterData)})
                            * 100 / {$this->receivedLogic($filterData)}
                        )
                    END as sell_through
                ")
        )
            ->with(['product:' . $productQueries->getColumnNameAndId(), 'product.media'])
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query
                    ->whereAny(['products.name', 'products.upc'], 'LIKE', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy(config('app.product_variant') ? 'master_products.article_number' : 'products.article_number');
    }

    private function commonQueryStockMovementForArticleNumber(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(
                DB::raw('SUM(balance) as balance'),
                'sell_through_aggregates.product_id',
                'sell_through_aggregates.location_id'
            )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query
                    ->whereAny(['products.name', 'products.upc'], 'LIKE', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy(
                [config(
                    'app.product_variant'
                ) ? 'master_products.article_number' : 'products.article_number', 'sell_through_aggregates.location_id']
            );

        return SellThroughAggregate::select(
            'products.id as product_id',
            'products.name',
            'products.retail_price as price',
            config(
                'app.product_variant'
            ) ? 'master_products.article_number as article_number' : 'products.article_number as article_number',
            'locations.name as location_name',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_in), 0) as goods_receive_note_in_balance'),
            DB::raw('COALESCE(SUM(goods_receive_note_out), 0) as goods_receive_note_out_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_in), 0) as stock_adjustment_in_balance'),
            DB::raw('COALESCE(SUM(stock_adjustment_out), 0) as stock_adjustment_out_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_in), 0) as stock_transfer_in_balance'),
            DB::raw('COALESCE(SUM(stock_transfer_out), 0) as stock_transfer_out_balance'),
            DB::raw('COALESCE(SUM(delivery_order_in), 0) as delivery_order_in_balance'),
            DB::raw('COALESCE(SUM(delivery_order_out), 0) as delivery_order_out_balance'),
        )
            ->with(['product:' . $productQueries->getColumnNameAndId(), 'product.media'])
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('locations', 'locations.id', '=', 'sell_through_aggregates.location_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query
                    ->whereAny(['products.name', 'products.upc'], 'LIKE', '%' . $filterData['search_text'] . '%');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy(
                [config(
                    'app.product_variant'
                ) ? 'master_products.article_number' : 'products.article_number', 'sell_through_aggregates.location_id']
            );
    }

    private function generateOptimizedCase(
        array $filterData,
        string $locationColumnKey,
        int $includeTypeValue,
        string $columnName
    ): string {
        $locationCondition = empty($filterData[$locationColumnKey])
            ? ''
            : 'AND sell_through_aggregates.location_id IN (' . implode(
                ',',
                array_map('intval', $filterData[$locationColumnKey])
            ) . ')';

        $includeByValues = empty($filterData['include_by'])
            ? '0'
            : implode(',', array_map('intval', $filterData['include_by']));

        return 'CASE WHEN ' . $includeTypeValue . ' IN (' . $includeByValues . sprintf(
            ') %s THEN %s ELSE 0 END',
            $locationCondition,
            $columnName
        );
    }

    public function sellThroughAggregateForSummaryGet(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQuerySellThroughForSummary($filterData, $companyId)
                ->get()
        );
    }

    public function stockMovementForSummaryGet(array $filterData, int $companyId): Collection
    {
        [$cacheKey, $cacheExpireTime] = CommonFunctions::generateFilteredCacheKeyWithExpiration(
            $filterData,
            __FUNCTION__,
            $companyId
        );

        return Cache::remember(
            $cacheKey,
            $cacheExpireTime,
            fn () => $this->commonQueryStockMovementForSummary($filterData, $companyId)
                ->get()
        );
    }

    public function commonQuerySellThroughForSummary(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $selectedColumns = [];

        if (config('app.product_variant')) {
            $selectedColumns = array_merge($selectedColumns, [
                'product_variant_values.value as variant_name',
                'product_variant_values.attribute_id as attribute_id',
            ]);
        } else {
            $selectedColumns = array_merge($selectedColumns, ['colors.name as color_name']);
        }

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(
                DB::raw('SUM(balance) as balance'),
                'sell_through_aggregates.product_id',
                'sell_through_aggregates.location_id'
            )
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id')
                    ->leftJoin('attributes', 'attributes.id', '=', 'product_variant_values.attribute_id')
                    ->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->when(config('app.product_variant'), function ($query) use ($filterData): void {
                $query->where('attributes.id', $filterData['attribute_type']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy(config('app.product_variant') ? 'product_variant_values.value' : 'products.color_id');

        return SellThroughAggregate::select(
            array_merge([
                'sell_through_aggregates.product_id as product_id',
                'sell_through_aggregates.location_id as location_id',
                'locations.name as location_name',
                'products.name',
                'products.original_created_at',
                'products.created_at',
                config(
                    'app.product_variant'
                ) ? 'master_products.article_number as article_number' : 'products.article_number as article_number',
                DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
                DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
                DB::raw(
                    'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
                ),
                DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
                DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
                DB::raw($this->receivedLogic($filterData) . ' AS received'),
                DB::raw('
                    CASE
                        WHEN (' . $this->receivedLogic($filterData) . " = 0) THEN 0
                        ELSE (
                            ({$this->getSoldLogic($filterData)}
                            + {$this->getOnlineSoldLogic($filterData)})
                            * 100 / {$this->receivedLogic($filterData)}
                        )
                    END as sell_through
                "),
            ], $selectedColumns)
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id')
                ->leftJoin('product_variant_values', 'products.id', '=', 'product_variant_values.product_id')
                ->leftJoin('attributes', 'attributes.id', '=', 'product_variant_values.attribute_id');
            }, function ($query): void {
                $query->leftJoin('colors', 'colors.id', '=', 'products.color_id');
            })
            ->leftJoin('locations', 'locations.id', '=', 'sell_through_aggregates.location_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->when(config('app.product_variant'), function ($query) use ($filterData): void {
                $query->where('attributes.id', $filterData['attribute_type']);
            })
            ->groupBy(config('app.product_variant') ? 'product_variant_values.value' : 'products.color_id');
    }

    public function commonQueryStockMovementForSummary(array $filterData, int $companyId): Builder
    {
        $productQueries = new ProductQueries();

        $balanceSubQuery = DB::table('sell_through_aggregates')
            ->select(DB::raw('SUM(balance) as balance'), 'product_id', 'location_id')
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date_range'][1]);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('location_id', $filterData['location_ids']);
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->groupBy('products.color_id');

        return SellThroughAggregate::select(
            'sell_through_aggregates.product_id as product_id',
            'sell_through_aggregates.location_id as location_id',
            'locations.name as location_name',
            'products.name',
            config('app.product_variant') ? 'master_products.article_number' : 'products.article_number',
            'colors.name as color_name',
            DB::raw($this->getSoldLogic($filterData) . '
                AS sold
            '),
            DB::raw($this->getOnlineSoldLogic($filterData) . '
                 AS online_sold
            '),
            DB::raw(
                'COALESCE(SUM(sale_amount), 0) - COALESCE(SUM(sell_through_aggregates.sale_return_amounts), 0) as net_sale_amount'
            ),
            DB::raw('SUM(total_online_sold_amount) as online_sale_amount'),
            DB::raw('COALESCE(balance_sub.balance, 0) as balance'),
            DB::raw($this->receivedLogic($filterData) . ' AS received'),
            DB::raw('
                        CASE
                            WHEN (' . $this->receivedLogic($filterData) . " = 0) THEN 0
                            ELSE (
                                ({$this->getSoldLogic($filterData)}
                                + {$this->getOnlineSoldLogic($filterData)})
                                * 100 / {$this->receivedLogic($filterData)}
                            )
                        END as sell_through
                    ")
        )
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->whereDate('date', '<=', $filterData['date']);
            })
            ->when(null !== $filterData['date_range'], function ($query) use ($filterData): void {
                $query->where('date', '>=', $filterData['date_range'][0])
                    ->where('date', '<=', $filterData['date_range'][1]);
            })
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin('locations', 'locations.id', '=', 'sell_through_aggregates.location_id')
            ->leftJoinSub($balanceSubQuery, 'balance_sub', function ($join): void {
                $join->on('sell_through_aggregates.product_id', '=', 'balance_sub.product_id')
                    ->on('sell_through_aggregates.location_id', '=', 'balance_sub.location_id');
            })
            ->join('colors', 'colors.id', '=', 'products.color_id')
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy('colors.id');
    }

    public function sellThroughAggregateByProductArticleNumberForDashboard(
        array $filterData,
        int $companyId
    ): Collection {
        $locationIds = $filterData['location_ids'] ?? null;
        $locationIds = is_array($locationIds) ? implode(',', $locationIds) : $locationIds;

        $cacheKey = 'cache-top-ranking-products-' . $companyId . '-' . $locationIds . '-' . $filterData['date'];

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => $this->commonQuerySellThroughAggregateByProductArticleNumberForDashboard(
                $filterData,
                $companyId
            )->limit(10)
                ->having('sell_through', '<=', 100)
                ->get()
        );
    }

    private function commonQuerySellThroughAggregateByProductArticleNumberForDashboard(
        array $filterData,
        int $companyId
    ): Builder {
        $productQueries = new ProductQueries();

        return SellThroughAggregate::select(
            'product_id as id',
            'product_id',
            'location_id',
            'products.name',
            config(
                'app.product_variant'
            ) ? 'master_products.article_number as article_number' : 'products.article_number as article_number',
            DB::raw('
                        CASE
                            WHEN (' . $this->receivedLogic($filterData) . " = 0) THEN 0
                            ELSE (
                                ({$this->getSoldLogic($filterData)}
                                + {$this->getOnlineSoldLogic($filterData)})
                                * 100 / {$this->receivedLogic($filterData)}
                            )
                        END as sell_through
                    ")
        )
            ->with(['product:' . $productQueries->getColumnNameAndId(), 'product.media'])
            ->leftJoin('products', 'products.id', '=', 'sell_through_aggregates.id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->where($productQueries->productsFilterForSellThroughAggregate($filterData, $companyId))
            ->when(null !== $filterData['date'], function ($query) use ($filterData): void {
                $query->where('date', '<=', $filterData['date']);
            })
            ->when($filterData['location_ids'], function ($query) use ($filterData): void {
                $query->whereIn('sell_through_aggregates.location_id', $filterData['location_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sell_through_aggregates.id', 'desc');
            })
            ->groupBy(config('app.product_variant') ? 'master_products.article_number' : 'products.article_number');
    }

    public function getStockSummaryByModuleForExport(array $filterData, int $reportType, int $reportBy): Collection
    {
        $productQueries = new ProductQueries();
        $attributeQueries = resolve(AttributeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);

        $query = SellThroughAggregate::query();
        $articleNumber = config('app.product_variant') ? 'master_products.article_number' : 'products.article_number';

        $selectColumn = [
            'sell_through_aggregates.id',
            'products.id as product_id',
            'locations.code as location_code',
            $articleNumber,
            'products.name as product_name',
            'brands.name as brand_name',
            'departments.name as department_name',
        ];

        $reportMappings = [
            StockSummaryByModuleReportBy::SALES->value => 'SUM(sold - `return`) as sales',
            StockSummaryByModuleReportBy::GRN_IN->value => 'SUM(goods_receive_note_in) as grn_in',
            StockSummaryByModuleReportBy::GRN_OUT->value => 'SUM(goods_receive_note_out) as grn_out',
            StockSummaryByModuleReportBy::TRANSFER_OUT->value => 'SUM(stock_transfer_out) as stock_transfer_out',
            StockSummaryByModuleReportBy::DELIVERY_OUT->value => 'SUM(delivery_order_out) as delivery_order_out',
            StockSummaryByModuleReportBy::TRANSFER_IN->value => 'SUM(stock_transfer_in) as stock_transfer_in',
            StockSummaryByModuleReportBy::DELIVERY_IN->value => 'SUM(delivery_order_in) as delivery_order_in',
            StockSummaryByModuleReportBy::STOCK_ADJUSTMENT_IN->value => 'SUM(stock_adjustment_in) as stock_adjustment_in',
            StockSummaryByModuleReportBy::STOCK_ADJUSTMENT_OUT->value => 'SUM(stock_adjustment_out) as stock_adjustment_out',
        ];

        if (isset($reportMappings[$reportBy])) {
            $selectColumn[] = DB::raw($reportMappings[$reportBy]);
        }

        if ($reportType === StockSummaryByModuleReportType::BY_MASTER_PRODUCT->value) {
            $selectColumn[] = $articleNumber;
        } elseif (! config('app.product_variant')) {
            $selectColumn[] = 'colors.name as color_name';
            $selectColumn[] = 'sizes.name as size_name';
        }

        $groupByMappings = [
            StockSummaryByModuleReportType::BY_MASTER_PRODUCT->value => [$articleNumber, 'locations.code'],
            StockSummaryByModuleReportType::BY_UPC->value => ['products.upc', 'locations.code'],
            StockSummaryByModuleReportType::BY_BRAND->value => [
                config('app.product_variant') ? 'master_products.brand_id' : 'products.brand_id',
                'locations.code',
            ],
            StockSummaryByModuleReportType::BY_DEPARTMENT->value => [
                config('app.product_variant') ? 'master_products.department_id' : 'products.department_id',
                'locations.code',
            ],
        ];

        $groupByColumns = $groupByMappings[$reportType] ?? ['products.id', 'locations.code'];

        $relations = [];
        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product:' . $productQueries->getColumnNameAndIdWithMasterId(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        }

        $query->select($selectColumn)
            ->join('products', 'products.id', '=', 'sell_through_aggregates.product_id')
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'products.master_product_id', '=', 'master_products.id');
            })
            ->leftJoin(
                'brands',
                'brands.id',
                '=',
                config('app.product_variant') ? 'master_products.brand_id' : 'products.brand_id'
            )
            ->leftJoin(
                'departments',
                'departments.id',
                '=',
                config('app.product_variant') ? 'master_products.department_id' : 'products.department_id'
            )
            ->unless(config('app.product_variant'), function ($query): void {
                $query->leftJoin('colors', 'colors.id', '=', 'products.color_id')
                    ->leftJoin('sizes', 'sizes.id', '=', 'products.size_id');
            })
            ->leftJoin('locations', 'locations.id', '=', 'sell_through_aggregates.location_id')
            ->with($relations)
            ->when(
                $filterData['date_range'] ?? null,
                fn ($query) => $query->whereBetween('date', $filterData['date_range'])
            )
            ->when(
                $filterData['location_ids'] ?? null,
                fn ($query) => $query->whereIn('location_id', $filterData['location_ids'])
            )
            ->when(
                $filterData['article_number'] ?? null,
                fn ($query) => $query->whereIn($articleNumber, $filterData['article_number'])
            )
            ->when(
                $filterData['department_ids'] ?? null,
                fn ($query) => $query->whereIn(
                    config('app.product_variant') ? 'master_products.department_id' : 'products.department_id',
                    $filterData['department_ids']
                )
            )
            ->when(
                $filterData['brand_ids'] ?? null,
                fn ($query) => $query->whereIn(
                    config('app.product_variant') ? 'master_products.brand_id' : 'products.brand_id',
                    $filterData['brand_ids']
                )
            )
            ->groupBy($groupByColumns);

        return $query->get();
    }
}
