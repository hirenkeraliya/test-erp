<?php

declare(strict_types=1);

namespace App\Domains\Size;

use App\CommonFunctions;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Size\DataObjects\SizeData;
use App\Domains\SizeGroup\SizeGroupQueries;
use App\Models\Size;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SizeQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getSizeQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(SizeData $sizeData, int $companyId): Size
    {
        $data = $sizeData->all();
        $data['company_id'] = $companyId;

        return Size::create($data);
    }

    public function getFirstForEcommerceSync(int $companyId, int $saleChannelId): ?Size
    {
        return Size::select('id')
            ->whereDoesntHave('sizeChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->where('company_id', $companyId)
            ->orderBy('id', 'asc')
            ->first();
    }

    public function getLastForEcommerceSync(int $companyId, int $saleChannelId): ?Size
    {
        return Size::select('id')
            ->whereDoesntHave('sizeChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getSizeEcommerceChannelByStartAndEndId(
        int $companyId,
        int $startId,
        int $endId,
        int $saleChannelId
    ): Collection {
        return Size::select('id', 'name', 'code')
            ->whereDoesntHave('sizeChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->where('company_id', $companyId)
            ->where('id', '>=', $startId)
            ->where('id', '<=', $endId)
            ->get();
    }

    public function getById(int $sizeId, int $companyId): Size
    {
        return Size::select('id', 'name', 'code', 'group_id')
            ->where('company_id', $companyId)
            ->findOrFail($sizeId);
    }

    public function getByOnlyId(int $sizeId): Size
    {
        return Size::select('id', 'name', 'code', 'group_id', 'sort_order', 'company_id')
            ->findOrFail($sizeId);
    }

    public function update(SizeData $sizeData, int $sizeId, int $companyId): void
    {
        $size = $this->getById($sizeId, $companyId);
        $size->update($sizeData->all());
    }

    public function updateSortOrder(int $sizeId, int $companyId, int $sortOrder): void
    {
        $size = $this->getById($sizeId, $companyId);
        $size->update([
            'sort_order' => $sortOrder,
        ]);
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getWithBasicColumns(int $companyId): Collection
    {
        return Size::select('id', 'name')->where('company_id', $companyId)->get();
    }

    public function existsByName(string $name, int $companyId): bool
    {
        return Size::whereCaseSensitive('name', $name)->where('company_id', $companyId)->exists();
    }

    public function existsByCode(string $code, int $companyId): bool
    {
        return Size::whereCaseSensitive('code', $code)->where('company_id', $companyId)->exists();
    }

    public function getIdByName(string $name, int $companyId): int
    {
        $getLastSortingSequenceNumber = Size::where('company_id', $companyId)->latest()->first()?->sort_order;

        return Size::firstOrCreate([
            'name' => $name,
            'company_id' => $companyId,
        ], [
            'sort_order' => $getLastSortingSequenceNumber + 1,
        ])->id;
    }

    public function getIdBySortName(string $name, int $companyId): ?int
    {
        return Size::select('id')
            ->whereCaseSensitive('name', $name)
            ->where('company_id', $companyId)
            ->first()
            ?->id;
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,code,sort_order';
    }

    public function getBasicColumnNamesForEcommerce(): string
    {
        return 'id,name,code,created_at,updated_at';
    }

    public function getBasicColumnNamesForRegularSalesApi(): string
    {
        return 'id,name';
    }

    public function searchByColumns(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')
            ->whereAny(['name', 'code'], 'LIKE', '%' . $searchText . '%');
    }

    public function searchByName(string $searchText): Closure
    {
        return fn ($query) => $query->where('name', 'like', '%' . $searchText . '%');
    }

    public function getFilteredSizesByCompanyId(string $searchText, int $companyId): SupportCollection
    {
        return Size::select('id', 'name')
            ->where('company_id', $companyId)
            ->where('name', 'like', '%' . $searchText . '%')
            ->limit(5)
            ->get();
    }

    public function getAllSizes(int $companyId): SupportCollection
    {
        return Size::select('id', 'name', 'sort_order')->where('company_id', $companyId)->get();
    }

    public function getSizesExport(array $filterData, int $companyId): SupportCollection
    {
        return $this->getSizeQuery($filterData, $companyId)->get();
    }

    public function getCachedSizeSalesForChart(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string $date,
        bool $refresh = false
    ): SupportCollection {
        $cacheKey = 'cache-sizes-sales-' . $companyId . '-' . $locationId . '-' . $brandId . '-' . $date . $brandId;

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): SupportCollection => Size::query()
                ->select(
                    'sizes.id',
                    'sizes.name',
                    'size_sale_total.sales_count',
                    DB::raw(
                        '(COALESCE(size_sale_total.total_paid_amount, 0) - COALESCE(size_return_total.return_amount, 0)) as total_sales'
                    ),
                    DB::raw(
                        '(COALESCE(size_sale_total.units_sold, 0) - COALESCE(size_return_total.return_units, 0)) as total_units_sold'
                    ),
                )
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'sale_items.product_id', '=', 'products.id')
                        ->join('sizes', 'products.size_id', '=', 'sizes.id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $locationId > 0, function ($query) use ($locationId): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $brandId > 0, function ($query) use ($brandId): void {
                            $query->where('products.brand_id', $brandId);
                        })
                        ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($date))
                        ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($date))
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->select(
                            'sizes.id as size_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('size_id'),
                    'size_sale_total',
                    'size_sale_total.size_id',
                    '=',
                    'sizes.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->join('sizes', 'products.size_id', '=', 'sizes.id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $locationId > 0, function ($query) use ($locationId): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $brandId > 0, function ($query) use ($brandId): void {
                            $query->where('products.brand_id', $brandId);
                        })
                        ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($date))
                        ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($date))
                        ->select(
                            'sizes.id as size_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('size_id'),
                    'size_return_total',
                    'size_return_total.size_id',
                    '=',
                    'sizes.id'
                )
                ->whereNotNull('size_sale_total.total_paid_amount')
                ->orWhereNotNull('size_sale_total.units_sold')
                ->orWhereNotNull('size_sale_total.sales_count')
                ->orWhereNotNull('size_return_total.return_amount')
                ->orWhereNotNull('size_return_total.return_units')
                ->orderByDesc('total_sales')
                ->get()
        );
    }

    public function getCachedSeasonalTopFiveSizeSalesForChart(
        array $filterData,
        int $companyId,
        bool $refresh = false,
        array $ids = [],
    ): SupportCollection {
        $cacheKey = 'cache-seasonal-sizes-sales-' . $companyId . '-' . $filterData['location_id'] . '-' . $filterData['brand_id'] . '-' . $filterData['start_date'] . $filterData['end_date'];

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): SupportCollection => Size::query()
                ->select(
                    'sizes.id',
                    'sizes.name',
                    'size_sale_total.sales_count',
                    DB::raw(
                        '(COALESCE(size_sale_total.total_paid_amount, 0) - COALESCE(size_return_total.return_amount, 0)) as total_sales'
                    ),
                    DB::raw(
                        '(COALESCE(size_sale_total.units_sold, 0) - COALESCE(size_return_total.return_units, 0)) as total_units_sold'
                    ),
                )
                ->when([] !== $ids, function ($query) use ($ids): void {
                    $query->whereIntegerInRaw('id', $ids);
                })
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'sale_items.product_id', '=', 'products.id')
                        ->join('sizes', 'products.size_id', '=', 'sizes.id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $filterData['location_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when((int) $filterData['brand_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['brand_id']);
                        })
                        ->where(
                            'counter_updates.opened_by_pos_at',
                            '>=',
                            CommonFunctions::addStartTime($filterData['start_date'])
                        )
                        ->where(
                            'counter_updates.opened_by_pos_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['end_date'])
                        )
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->select(
                            'sizes.id as size_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('size_id'),
                    'size_sale_total',
                    'size_sale_total.size_id',
                    '=',
                    'sizes.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->join('sizes', 'products.size_id', '=', 'sizes.id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $filterData['location_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when((int) $filterData['brand_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['brand_id']);
                        })
                        ->where(
                            'counter_updates.opened_by_pos_at',
                            '>=',
                            CommonFunctions::addStartTime($filterData['start_date'])
                        )
                        ->where(
                            'counter_updates.opened_by_pos_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['end_date'])
                        )
                        ->select(
                            'sizes.id as size_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('size_id'),
                    'size_return_total',
                    'size_return_total.size_id',
                    '=',
                    'sizes.id'
                )
                ->whereNotNull('size_sale_total.total_paid_amount')
                ->orWhereNotNull('size_sale_total.units_sold')
                ->orWhereNotNull('size_sale_total.sales_count')
                ->orWhereNotNull('size_return_total.return_amount')
                ->orWhereNotNull('size_return_total.return_units')
                ->orderByDesc('total_sales')
                ->take(5)
                ->get()
        );
    }

    public function getCachedSeasonalSizeWithStockSalesForChart(
        array $filterData,
        int $companyId,
        bool $refresh = false
    ): SupportCollection {
        $cacheKey = 'cache-seasonal-sizes-with-stock-sales-' . $companyId . '-' . $filterData['location_id'] . '-' . $filterData['brand_id'] . '-' . $filterData['start_date'] . $filterData['end_date'];

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): SupportCollection => Size::query()
                ->select(
                    'sizes.id',
                    'sizes.name',
                    'inventory_updates.stock',
                    DB::raw(
                        '(COALESCE(size_sale_total.units_sold, 0) - COALESCE(size_return_total.return_units, 0)) as total_units_sold'
                    ),
                )
                ->leftJoinSub(
                    DB::table('inventory_updates')
                        ->join('products', 'inventory_updates.product_id', '=', 'products.id')
                        ->join('sizes', 'products.size_id', '=', 'sizes.id')
                        ->join('locations', 'inventory_updates.location_id', '=', 'locations.id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $filterData['location_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('locations.id', $filterData['location_id']);
                        })
                        ->when((int) $filterData['brand_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['brand_id']);
                        })
                        ->where(
                            'inventory_updates.happened_at',
                            '>=',
                            CommonFunctions::addStartTime($filterData['start_date'])
                        )
                        ->where(
                            'inventory_updates.happened_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['end_date'])
                        )
                        ->where('affected_by_type', ModelMapping::GOODS_RECEIVED_NOTE_PRODUCT->name)
                        ->select('sizes.id as size_id', DB::raw('SUM(inventory_updates.quantity) as stock'))
                        ->groupBy('size_id'),
                    'inventory_updates',
                    'inventory_updates.size_id',
                    '=',
                    'sizes.id'
                )
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'sale_items.product_id', '=', 'products.id')
                        ->join('sizes', 'products.size_id', '=', 'sizes.id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $filterData['location_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when((int) $filterData['brand_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['brand_id']);
                        })
                        ->where(
                            'counter_updates.opened_by_pos_at',
                            '>=',
                            CommonFunctions::addStartTime($filterData['start_date'])
                        )
                        ->where(
                            'counter_updates.opened_by_pos_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['end_date'])
                        )
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->select(
                            'sizes.id as size_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('size_id'),
                    'size_sale_total',
                    'size_sale_total.size_id',
                    '=',
                    'sizes.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->join('sizes', 'products.size_id', '=', 'sizes.id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $filterData['location_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when((int) $filterData['brand_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['brand_id']);
                        })
                        ->where(
                            'counter_updates.opened_by_pos_at',
                            '>=',
                            CommonFunctions::addStartTime($filterData['start_date'])
                        )
                        ->where(
                            'counter_updates.opened_by_pos_at',
                            '<=',
                            CommonFunctions::addEndTime($filterData['end_date'])
                        )
                        ->select(
                            'sizes.id as size_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('size_id'),
                    'size_return_total',
                    'size_return_total.size_id',
                    '=',
                    'sizes.id'
                )
                ->whereNotNull('size_sale_total.total_paid_amount')
                ->orWhereNotNull('size_sale_total.units_sold')
                ->orWhereNotNull('size_sale_total.sales_count')
                ->orWhereNotNull('size_return_total.return_amount')
                ->orWhereNotNull('size_return_total.return_units')
                ->orderByDesc('total_units_sold')
                ->take(5)
                ->get()
        );
    }

    public function getSizeSalesSummary(array $filterData, int $companyId): SupportCollection
    {
        return Size::query()
            ->select(
                'sizes.id',
                'sizes.name',
                DB::raw(
                    '(COALESCE(size_sale_total.total_paid_amount, 0) - COALESCE(size_return_total.return_amount, 0)) as total_sales'
                ),
                DB::raw(
                    '(COALESCE(size_sale_total.units_sold, 0) - COALESCE(size_return_total.return_units, 0)) as total_units_sold'
                )
            )
            ->leftJoinSub(
                DB::table('sales')
                    ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->join('sizes', 'products.size_id', '=', 'sizes.id')
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::CATEGORIES->value,
                        function ($query) use ($filterData): void {
                            $query->join('category_product', 'products.id', '=', 'category_product.product_id')
                                ->where('category_product.category_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::COLORS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.color_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::BRANDS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::DEPARTMENTS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.department_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::COLOR_GROUPS->value,
                        function ($query) use ($filterData): void {
                            $query->join('colors', 'products.color_id', '=', 'colors.id')
                                ->where('colors.group_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::STYLES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.style_id', $filterData['id']);
                        }
                    )
                    ->where('locations.company_id', $companyId)
                    ->where(
                        'counter_updates.opened_by_pos_at',
                        '>=',
                        CommonFunctions::addStartTime($filterData['date'])
                    )
                    ->where(
                        'counter_updates.opened_by_pos_at',
                        '<=',
                        CommonFunctions::addEndTime($filterData['date'])
                    )
                    ->when(array_key_exists('locationId', $filterData), function ($query) use ($filterData): void {
                        $query->where('locations.id', $filterData['locationId']);
                    })
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->select(
                        'sizes.id as size_id',
                        DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                        DB::raw('SUM(sale_items.quantity) as units_sold')
                    )
                    ->groupBy('size_id'),
                'size_sale_total',
                'size_sale_total.size_id',
                '=',
                'sizes.id'
            )
            ->leftJoinSub(
                DB::table('sale_returns')
                    ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                    ->join('sizes', 'products.size_id', '=', 'sizes.id')
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::CATEGORIES->value,
                        function ($query) use ($filterData): void {
                            $query->join('category_product', 'products.id', '=', 'category_product.product_id')
                                ->where('category_product.category_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::COLORS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.color_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::BRANDS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::DEPARTMENTS->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.department_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::COLOR_GROUPS->value,
                        function ($query) use ($filterData): void {
                            $query->join('colors', 'products.color_id', '=', 'colors.id')
                                ->where('colors.group_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::STYLES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.style_id', $filterData['id']);
                        }
                    )
                    ->where('locations.company_id', $companyId)
                    ->when(array_key_exists('locationId', $filterData), function ($query) use ($filterData): void {
                        $query->where('locations.id', $filterData['locationId']);
                    })
                    ->where(
                        'counter_updates.opened_by_pos_at',
                        '>=',
                        CommonFunctions::addStartTime($filterData['date'])
                    )
                    ->where(
                        'counter_updates.opened_by_pos_at',
                        '<=',
                        CommonFunctions::addEndTime($filterData['date'])
                    )
                    ->select(
                        'sizes.id as size_id',
                        DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                        DB::raw('SUM(sale_return_items.quantity) as return_units')
                    )
                    ->groupBy('size_id'),
                'size_return_total',
                'size_return_total.size_id',
                '=',
                'sizes.id'
            )
            ->whereNotNull('size_sale_total.total_paid_amount')
            ->orWhereNotNull('size_sale_total.units_sold')
            ->orWhereNotNull('size_return_total.return_amount')
            ->orWhereNotNull('size_return_total.return_units')
            ->orderByDesc('total_sales')
            ->get();
    }

    public function codeTakenByAnotherSize(string $code, string $name, int $companyId): bool
    {
        return Size::whereNotCaseSensitive('name', $name)
            ->whereCaseSensitive('code', $code)
            ->where('company_id', $companyId)
            ->exists();
    }

    public function updateByName(array $sizeData, string $name, int $companyId): void
    {
        $size = Size::select('id')
            ->where('name', $name)
            ->where('company_id', $companyId)
            ->first();

        if ($size instanceof Size) {
            $size->update($sizeData);
        }
    }

    private function getSizeQuery(array $filterData, int $companyId): Builder
    {
        $sizeGroupQueries = resolve(SizeGroupQueries::class);
        $sizeQueries = resolve(self::class);

        return Size::query()
            ->select('id', 'name', 'code', 'group_id', 'sort_order')
            ->with([
                'sizeGroup:' . $sizeGroupQueries->getBasicColumnNames(),
                'sortingSize:' . $sizeQueries->getBasicColumnNames(),
            ])
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['name', 'code'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['group_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('group_id', (array) $filterData['group_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('sort_order', 'asc');
            });
    }

    public function getSizeNameForFilter(array $sizeIds): string
    {
        $sizeData = [];
        $size = Size::select('name')
            ->whereIntegerInRaw('id', values: $sizeIds)
            ->get();

        if ($size->isNotEmpty()) {
            $sizeData = $size->pluck('name')->toArray();
        }

        return implode(', ', $sizeData);
    }

    public function firstOrCreate(string $name, int $companyId): Size
    {
        return Size::firstOrCreate([
            'name' => $name,
            'company_id' => $companyId,
        ]);
    }
}
