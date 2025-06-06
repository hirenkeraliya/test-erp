<?php

declare(strict_types=1);

namespace App\Domains\Color;

use App\CommonFunctions;
use App\Domains\Color\DataObjects\ColorData;
use App\Domains\ColorGroup\ColorGroupQueries;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\Color;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ColorQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getColors($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(ColorData $colorData, int $companyId): Color
    {
        $data = $colorData->all();
        $data['company_id'] = $companyId;

        return Color::create($data);
    }

    public function getFirstForEcommerceSync(int $companyId, int $saleChannelId): ?Color
    {
        return Color::select('id')->whereDoesntHave('colorChannelReferences', function ($query) use (
            $saleChannelId
        ): void {
            $query->where('sale_channel_id', $saleChannelId);
        })
            ->where('company_id', $companyId)
            ->orderBy('id', 'asc')
            ->first();
    }

    public function getLastForEcommerceSync(int $companyId, int $saleChannelId): ?Color
    {
        return Color::select('id')
            ->whereDoesntHave('colorChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getColorEcommerceChannelByStartAndEndId(
        int $companyId,
        int $startId,
        int $endId,
        int $saleChannelId
    ): Collection {
        return Color::select('id', 'name', 'code')
        ->whereDoesntHave('colorChannelReferences', function ($query) use ($saleChannelId): void {
            $query->where('sale_channel_id', $saleChannelId);
        })
            ->where('company_id', $companyId)
            ->where('id', '>=', $startId)
            ->where('id', '<=', $endId)
            ->get();
    }

    public function getById(int $colorId, int $companyId): Color
    {
        return Color::select('id', 'name', 'code', 'group_id', 'color_code')
            ->where('company_id', $companyId)
            ->findOrFail($colorId);
    }

    public function getByOnlyId(int $colorId): Color
    {
        return Color::select('id', 'name', 'code', 'group_id', 'color_code', 'company_id')
            ->findOrFail($colorId);
    }

    public function update(ColorData $colorData, int $colorId, int $companyId): void
    {
        $color = $this->getById($colorId, $companyId);
        $color->update($colorData->all());
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getWithBasicColumns(int $companyId): Collection
    {
        return Color::select('id', 'name')->where('company_id', $companyId)->get();
    }

    public function existsByName(string $name, int $companyId): bool
    {
        return Color::whereCaseSensitive('name', $name)->where('company_id', $companyId)->exists();
    }

    public function getIdByName(string $name, int $companyId): int
    {
        return Color::firstOrCreate([
            'name' => $name,
            'company_id' => $companyId,
        ])->id;
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,code';
    }

    public function getBasicColumnNamesForEcommerce(): string
    {
        return 'id,name,code,color_code,created_at,updated_at';
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

    public function getFilteredColorsByCompanyId(string $searchText, int $companyId): SupportCollection
    {
        return Color::select('id', 'name')
            ->where('company_id', $companyId)
            ->where('name', 'like', '%' . $searchText . '%')
            ->orderByRaw('CASE WHEN name LIKE ? THEN 1 ELSE 2 END, name', [$searchText . '%'])
            ->limit(5)
            ->get();
    }

    public function getColorsExport(array $filterData, int $companyId): SupportCollection
    {
        return $this->getColors($filterData, $companyId)->get();
    }

    public function getCachedColorsSalesForChart(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string $date,
        bool $refresh = false
    ): SupportCollection {
        $cacheKey = null !== $locationId ? 'cache-colors-sales-' . $locationId . $brandId . $date : 'cache-colors-sales-' . $date . $brandId;

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): SupportCollection => Color::query()
                ->select(
                    'colors.id',
                    'colors.name',
                    'color_sale_total.sales_count',
                    DB::raw(
                        '(COALESCE(color_sale_total.total_paid_amount, 0) - COALESCE(color_return_total.return_amount, 0)) as total_sales'
                    ),
                    DB::raw(
                        '(COALESCE(color_sale_total.units_sold, 0) - COALESCE(color_return_total.return_units, 0)) as total_units_sold'
                    ),
                )
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'sale_items.product_id', '=', 'products.id')
                        ->join('colors', 'products.color_id', '=', 'colors.id')
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
                            'colors.id as color_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('color_id'),
                    'color_sale_total',
                    'color_sale_total.color_id',
                    '=',
                    'colors.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->join('colors', 'products.color_id', '=', 'colors.id')
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
                            'colors.id as color_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('color_id'),
                    'color_return_total',
                    'color_return_total.color_id',
                    '=',
                    'colors.id'
                )
                ->whereNotNull('color_sale_total.total_paid_amount')
                ->orWhereNotNull('color_sale_total.units_sold')
                ->orWhereNotNull('color_sale_total.sales_count')
                ->orWhereNotNull('color_return_total.return_amount')
                ->orWhereNotNull('color_return_total.return_units')
                ->orderByDesc('total_sales')
                ->get()
        );
    }

    public function getCachedSeasonalTopFiveColorsSalesForChart(
        array $filterData,
        int $companyId,
        bool $refreshData = false,
        array $ids = []
    ): SupportCollection {
        $cacheKey = null !== $filterData['location_id'] ? 'cache-seasonal-colors-sales-' . $filterData['location_id'] . $filterData['brand_id'] . $filterData['start_date'] . $filterData['end_date'] : 'cache-seasonal-colors-sales-' . $filterData['start_date'] . $filterData['end_date'] . $filterData['brand_id'];

        if ($refreshData) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): SupportCollection => Color::query()
                ->select(
                    'colors.id',
                    'colors.name',
                    'color_sale_total.sales_count',
                    DB::raw(
                        '(COALESCE(color_sale_total.total_paid_amount, 0) - COALESCE(color_return_total.return_amount, 0)) as total_sales'
                    ),
                    DB::raw(
                        '(COALESCE(color_sale_total.units_sold, 0) - COALESCE(color_return_total.return_units, 0)) as total_units_sold'
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
                        ->join('colors', 'products.color_id', '=', 'colors.id')
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
                            'colors.id as color_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('color_id'),
                    'color_sale_total',
                    'color_sale_total.color_id',
                    '=',
                    'colors.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->join('colors', 'products.color_id', '=', 'colors.id')
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
                            'colors.id as color_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('color_id'),
                    'color_return_total',
                    'color_return_total.color_id',
                    '=',
                    'colors.id'
                )
                ->whereNotNull('color_sale_total.total_paid_amount')
                ->orWhereNotNull('color_sale_total.units_sold')
                ->orWhereNotNull('color_sale_total.sales_count')
                ->orWhereNotNull('color_return_total.return_amount')
                ->orWhereNotNull('color_return_total.return_units')
                ->orderByDesc('total_sales')
                ->take(5)
                ->get()
        );
    }

    public function getCachedWeekDistributionColorForChart(
        array $filterData,
        int $companyId,
        bool $refreshData = false,
    ): SupportCollection {
        $cacheKey = null !== $filterData['location_id'] ? 'cache-seasonal-week-based-colors-sales-' . $filterData['location_id'] . $filterData['brand_id'] . $filterData['start_date'] . $filterData['end_date'] : 'cache-seasonal-week-based-colors-sales-' . $filterData['start_date'] . $filterData['end_date'] . $filterData['brand_id'];

        if ($refreshData) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): SupportCollection => Color::query()
                ->select(
                    'colors.id',
                    DB::raw("CONCAT('week - ', color_sale_total.week_number) as week_number"),
                    DB::raw(
                        '(COALESCE(SUM(color_sale_total.units_sold), 0) - COALESCE(SUM(color_return_total.return_units), 0)) as total_units_sold'
                    ),
                )
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'sale_items.product_id', '=', 'products.id')
                        ->join('colors', 'products.color_id', '=', 'colors.id')
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
                            'colors.id as color_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                            DB::raw('WEEK(counter_updates.opened_by_pos_at) as week_number'),
                        )
                        ->groupBy('color_id', 'week_number'),
                    'color_sale_total',
                    'color_sale_total.color_id',
                    '=',
                    'colors.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->join('colors', 'products.color_id', '=', 'colors.id')
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
                            'colors.id as color_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                            DB::raw('WEEK(counter_updates.opened_by_pos_at) as week_number'),
                        )
                        ->groupBy('color_id', 'week_number'),
                    'color_return_total',
                    'color_return_total.color_id',
                    '=',
                    'colors.id'
                )
                ->whereNotNull('color_sale_total.total_paid_amount')
                ->orWhereNotNull('color_sale_total.units_sold')
                ->orWhereNotNull('color_sale_total.sales_count')
                ->orWhereNotNull('color_return_total.return_amount')
                ->orWhereNotNull('color_return_total.return_units')
                ->groupBy('week_number')
                ->orderBy('week_number')
                ->get()
        );
    }

    public function existsByCode(string $code, int $companyId): bool
    {
        return Color::whereCaseSensitive('code', $code)->where('company_id', $companyId)->exists();
    }

    public function getByCompanyId(int $companyId): Collection
    {
        return Color::select('id', 'name')
            ->where('company_id', $companyId)
            ->get();
    }

    public function getColorSalesSummary(array $filterData, int $companyId): SupportCollection
    {
        return Color::query()
            ->select(
                'colors.id',
                'colors.name',
                DB::raw(
                    '(COALESCE(color_sale_total.total_paid_amount, 0) - COALESCE(color_return_total.return_amount, 0)) as total_sales'
                ),
                DB::raw(
                    '(COALESCE(color_sale_total.units_sold, 0) - COALESCE(color_return_total.return_units, 0)) as total_units_sold'
                )
            )
            ->leftJoinSub(
                DB::table('sales')
                    ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->join('colors', 'products.color_id', '=', 'colors.id')
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::CATEGORIES->value,
                        function ($query) use ($filterData): void {
                            $query->join('category_product', 'products.id', '=', 'category_product.product_id')
                                ->where('category_product.category_id', $filterData['id']);
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
                            $query->where('colors.group_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::SIZES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.size_id', $filterData['id']);
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
                        'colors.id as color_id',
                        DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                        DB::raw('SUM(sale_items.quantity) as units_sold')
                    )
                    ->groupBy('color_id'),
                'color_sale_total',
                'color_sale_total.color_id',
                '=',
                'colors.id'
            )
            ->leftJoinSub(
                DB::table('sale_returns')
                    ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                    ->join('colors', 'products.color_id', '=', 'colors.id')
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::CATEGORIES->value,
                        function ($query) use ($filterData): void {
                            $query->join('category_product', 'products.id', '=', 'category_product.product_id')
                                ->where('category_product.category_id', $filterData['id']);
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
                            $query->where('colors.group_id', $filterData['id']);
                        }
                    )
                    ->when(
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::SIZES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.size_id', $filterData['id']);
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
                        'colors.id as color_id',
                        DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                        DB::raw('SUM(sale_return_items.quantity) as return_units')
                    )
                    ->groupBy('color_id'),
                'color_return_total',
                'color_return_total.color_id',
                '=',
                'colors.id'
            )
            ->whereNotNull('color_sale_total.total_paid_amount')
            ->orWhereNotNull('color_sale_total.units_sold')
            ->orWhereNotNull('color_return_total.return_amount')
            ->orWhereNotNull('color_return_total.return_units')
            ->orderByDesc('total_sales')
            ->get();
    }

    public function getCachedTopSellingColor(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string $fromDate,
        string $toDate,
        bool $refresh = false
    ): SupportCollection {
        $cacheKey = 'cache-Top-Selling-Color-' . $companyId . '-' . $locationId . '-' . $brandId . '-' . $fromDate . '-' . $toDate;

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => Color::query()
                ->select(
                    'colors.id',
                    'colors.name',
                    DB::raw(
                        '(COALESCE(color_sale_total.total_paid_amount, 0) - COALESCE(color_return_total.return_amount, 0)) as total_sales'
                    ),
                    DB::raw(
                        '(COALESCE(color_sale_total.units_sold, 0) - COALESCE(color_return_total.return_units, 0)) as total_units_sold'
                    )
                )
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'sale_items.product_id', '=', 'products.id')
                        ->where('products.retail_price', '>', 0.0)
                        ->where('locations.company_id', $companyId)
                        ->when((int) $locationId > 0 && (int) $brandId > 0, function ($query) use (
                            $locationId,
                            $brandId
                        ): void {
                            $query->where('counters.location_id', $locationId)
                                ->where('products.brand_id', $brandId);
                        })
                        ->when((int) $locationId > 0 && (int) $brandId <= 0, function ($query) use (
                            $locationId
                        ): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $locationId <= 0 && (int) $brandId > 0, function ($query) use (
                            $brandId
                        ): void {
                            $query->whereRaw(
                                'counters.location_id IN (select location_id from brand_location where brand_id = ' . $brandId . ')'
                            )->where('products.brand_id', $brandId);
                        })
                        ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($fromDate))
                        ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($toDate))
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->select(
                            'products.color_id as color_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count')
                        )
                        ->groupBy('color_id'),
                    'color_sale_total',
                    'color_sale_total.color_id',
                    '=',
                    'colors.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->where('products.retail_price', '>', 0.0)
                        ->where('locations.company_id', $companyId)
                        ->when((int) $locationId > 0 && (int) $brandId > 0, function ($query) use (
                            $locationId,
                            $brandId
                        ): void {
                            $query->where('counters.location_id', $locationId)
                                ->where('products.brand_id', $brandId);
                        })
                        ->when((int) $locationId > 0 && (int) $brandId <= 0, function ($query) use (
                            $locationId
                        ): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $locationId <= 0 && (int) $brandId > 0, function ($query) use (
                            $brandId
                        ): void {
                            $query->whereRaw(
                                'counters.location_id IN (select location_id from brand_location where brand_id = ' . $brandId . ')'
                            )->where('products.brand_id', $brandId);
                        })
                        ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($fromDate))
                        ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($toDate))
                        ->select(
                            'products.color_id as color_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units')
                        )
                        ->groupBy('color_id'),
                    'color_return_total',
                    'color_return_total.color_id',
                    '=',
                    'colors.id'
                )
                ->whereNotNull('color_sale_total.total_paid_amount')
                ->orWhereNotNull('color_sale_total.units_sold')
                ->orWhereNotNull('color_sale_total.sales_count')
                ->orWhereNotNull('color_return_total.return_amount')
                ->orWhereNotNull('color_return_total.return_units')
                ->orderByDesc('total_units_sold')
                ->limit(10)
                ->get()
        );
    }

    private function getColors(array $filterData, int $companyId): Builder
    {
        $colorGroupQueries = resolve(ColorGroupQueries::class);

        return Color::query()
            ->select('id', 'name', 'code', 'group_id', 'color_code')
            ->with(['colorGroup:' . $colorGroupQueries->getBasicColumnNames()])
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['name', 'code', 'color_code'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['group_ids'], function ($query) use ($filterData): void {
                $query->whereIntegerInRaw('group_id', (array) $filterData['group_ids']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getColorNamesByIds(int $companyId, array $colorIds): ?Color
    {
        return Color::selectRaw("GROUP_CONCAT(CONCAT(name) SEPARATOR ', ') AS names")
            ->whereIntegerInRaw('id', $colorIds)
            ->where('company_id', $companyId)
            ->first();
    }

    public function codeTakenByAnotherColor(string $code, string $name, int $companyId): bool
    {
        return Color::whereNotCaseSensitive('name', $name)
            ->whereCaseSensitive('code', $code)
            ->where('company_id', $companyId)
            ->exists();
    }

    public function updateByName(array $colorData, string $name, int $companyId): void
    {
        $color = Color::select('id')
            ->where('name', $name)
            ->where('company_id', $companyId)
            ->first();

        if ($color instanceof Color) {
            $color->update($colorData);
        }
    }

    public function getColorNameForFilter(array $colorIds): string
    {
        $colorData = [];
        $color = Color::select('name')
            ->whereIntegerInRaw('id', values: $colorIds)
            ->get();

        if ($color->isNotEmpty()) {
            $colorData = $color->pluck('name')->toArray();
        }

        return implode(', ', $colorData);
    }

    public function firstOrCreate(string $name, int $companyId): Color
    {
        return Color::firstOrCreate([
            'name' => $name,
            'company_id' => $companyId,
        ]);
    }
}
