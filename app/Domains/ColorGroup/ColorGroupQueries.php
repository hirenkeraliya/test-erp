<?php

declare(strict_types=1);

namespace App\Domains\ColorGroup;

use App\CommonFunctions;
use App\Domains\ColorGroup\DataObjects\ColorGroupData;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\ColorGroup;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ColorGroupQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getColorGroups($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(ColorGroupData $colorGroupData, int $companyId): void
    {
        $data = $colorGroupData->all();
        $data['company_id'] = $companyId;

        ColorGroup::create($data);
    }

    public function getById(int $colorGroupId, int $companyId): ColorGroup
    {
        return ColorGroup::select('id', 'name', 'code', 'color_code')
            ->where('company_id', $companyId)
            ->findOrFail($colorGroupId);
    }

    public function update(ColorGroupData $colorGroupData, int $colorGroupId, int $companyId): void
    {
        $color = $this->getById($colorGroupId, $companyId);
        $color->update($colorGroupData->all());
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,code';
    }

    public function getColorGroupsExport(array $filterData, int $companyId): SupportCollection
    {
        return $this->getColorGroups($filterData, $companyId)->get();
    }

    public function getColorGroupByCompanyId(int $companyId): SupportCollection
    {
        return ColorGroup::select('id', 'name')
            ->where('company_id', $companyId)
            ->get();
    }

    public function getColorGroupsByCompanyId(int $companyId, array $filterData): LengthAwarePaginator
    {
        return ColorGroup::select('id', 'name', 'code', 'color_code', 'updated_at', 'created_at')
            ->where('company_id', $companyId)
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            })
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->whereAny(['name', 'code'], 'LIKE', '%' . $filterData['search_text'] . '%');
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getCachedColorGroupSalesForChart(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string $date,
        bool $refresh = false
    ): SupportCollection {
        $cacheKey = 'cache-color-groups-sales-' . $companyId . '-' . $locationId . '-' . $brandId . '-' . $date . $brandId;

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): SupportCollection => ColorGroup::query()
                ->select(
                    'color_groups.id',
                    'color_groups.name',
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
                        ->join('color_groups', 'colors.group_id', '=', 'color_groups.id')
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
                            'color_groups.id as color_group_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('color_group_id'),
                    'color_sale_total',
                    'color_sale_total.color_group_id',
                    '=',
                    'color_groups.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->join('colors', 'products.color_id', '=', 'colors.id')
                        ->join('color_groups', 'colors.group_id', '=', 'color_groups.id')
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
                            'color_groups.id as color_group_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('color_group_id'),
                    'color_return_total',
                    'color_return_total.color_group_id',
                    '=',
                    'color_groups.id'
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

    public function getCachedSeasonalTopFiveColorGroupSalesForChart(
        array $filterData,
        int $companyId,
        bool $refresh = false,
        array $ids = [],
    ): SupportCollection {
        $cacheKey = 'cache-seasonal-color-groups-sales-' . $companyId . '-' . $filterData['location_id'] . '-' . $filterData['brand_id'] . '-' . $filterData['start_date'] . $filterData['end_date'];

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): SupportCollection => ColorGroup::query()
                ->select(
                    'color_groups.id',
                    'color_groups.name',
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
                        ->join('color_groups', 'colors.group_id', '=', 'color_groups.id')
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
                            'color_groups.id as color_group_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('color_group_id'),
                    'color_sale_total',
                    'color_sale_total.color_group_id',
                    '=',
                    'color_groups.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->join('colors', 'products.color_id', '=', 'colors.id')
                        ->join('color_groups', 'colors.group_id', '=', 'color_groups.id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $filterData['location_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when((int) $filterData['end_date'] > 0, function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['end_date']);
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
                            'color_groups.id as color_group_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('color_group_id'),
                    'color_return_total',
                    'color_return_total.color_group_id',
                    '=',
                    'color_groups.id'
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

    public function existsByName(string $name, int $companyId): bool
    {
        return ColorGroup::whereCaseSensitive('name', $name)->where('company_id', $companyId)->exists();
    }

    public function codeTakenByAnotherColorGroup(string $code, string $name, int $companyId): bool
    {
        return ColorGroup::whereNotCaseSensitive('name', $name)
            ->whereCaseSensitive('code', $code)
            ->where('company_id', $companyId)
            ->exists();
    }

    public function updateByName(array $colorGroupData, string $name, int $companyId): void
    {
        $colorGroup = ColorGroup::select('id')
            ->where('name', $name)
            ->where('company_id', $companyId)
            ->first();

        if ($colorGroup instanceof ColorGroup) {
            $colorGroup->update($colorGroupData);
        }
    }

    public function existsByCode(string $code, int $companyId): bool
    {
        return ColorGroup::whereCaseSensitive('code', $code)->where('company_id', $companyId)->exists();
    }

    public function getIdByName(string $name, int $companyId): ?ColorGroup
    {
        return ColorGroup::select('id', 'name')
            ->whereCaseSensitive('name', $name)
            ->where('company_id', $companyId)
            ->first();
    }

    public function getColorGroupSalesSummary(array $filterData, int $companyId): SupportCollection
    {
        return ColorGroup::query()
            ->select(
                'color_groups.id',
                'color_groups.name',
                DB::raw(
                    '(COALESCE(color_group_sale_total.total_paid_amount, 0) - COALESCE(color_group_return_total.return_amount, 0)) as total_sales'
                ),
                DB::raw(
                    '(COALESCE(color_group_sale_total.units_sold, 0) - COALESCE(color_group_return_total.return_units, 0)) as total_units_sold'
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
                    ->join('color_groups', 'colors.group_id', '=', 'color_groups.id')
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
                        'color_groups.id as color_group_id',
                        DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                        DB::raw('SUM(sale_items.quantity) as units_sold')
                    )
                    ->groupBy('color_group_id'),
                'color_group_sale_total',
                'color_group_sale_total.color_group_id',
                '=',
                'color_groups.id'
            )
            ->leftJoinSub(
                DB::table('sale_returns')
                    ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                    ->join('colors', 'products.color_id', '=', 'colors.id')
                    ->join('color_groups', 'colors.group_id', '=', 'color_groups.id')
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
                    ->select(
                        'color_groups.id as color_group_id',
                        DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                        DB::raw('SUM(sale_return_items.quantity) as return_units')
                    )
                    ->groupBy('color_group_id'),
                'color_group_return_total',
                'color_group_return_total.color_group_id',
                '=',
                'color_groups.id'
            )
            ->whereNotNull('color_group_sale_total.total_paid_amount')
            ->orWhereNotNull('color_group_sale_total.units_sold')
            ->orWhereNotNull('color_group_return_total.return_amount')
            ->orWhereNotNull('color_group_return_total.return_units')
            ->orderByDesc('total_sales')
            ->get();
    }

    private function getColorGroups(array $filterData, int $companyId): Builder
    {
        return ColorGroup::query()
            ->select('id', 'name', 'code', 'color_code')
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['name', 'code', 'color_code'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }
}
