<?php

declare(strict_types=1);

namespace App\Domains\Style;

use App\CommonFunctions;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Domains\Style\DataObjects\StyleData;
use App\Models\Style;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StyleQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getStylesQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(StyleData $styleData, int $companyId): Style
    {
        $data = $styleData->all();
        $data['company_id'] = $companyId;

        return Style::create($data);
    }

    public function getById(int $styleId, int $companyId): Style
    {
        return Style::select('id', 'name', 'code', 'company_id')
            ->where('company_id', $companyId)
            ->findOrFail($styleId);
    }

    public function getStyleNamesByIds(int $companyId, array $styleIds): ?Style
    {
        return Style::selectRaw("GROUP_CONCAT(CONCAT(name) SEPARATOR ', ') AS names")
            ->whereIntegerInRaw('id', $styleIds)
            ->where('company_id', $companyId)
            ->first();
    }

    public function update(StyleData $styleData, int $styleId, int $companyId): void
    {
        $style = $this->getById($styleId, $companyId);
        $style->update($styleData->all());
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getWithBasicColumns(int $companyId): Collection
    {
        return Style::select('id', 'name')->where('company_id', $companyId)->get();
    }

    public function getByIds(array $styleIds): Collection
    {
        return Style::select('name')
            ->whereIntegerInRaw('id', $styleIds)
            ->get();
    }

    public function getByOnlyId(int $styleId): Style
    {
        return Style::select('id', 'name', 'code', 'company_id')
            ->findOrFail($styleId);
    }

    public function existsByName(string $name, int $companyId): bool
    {
        return Style::where('name', $name)->where('company_id', $companyId)->exists();
    }

    public function getIdByName(string $name, int $companyId): int
    {
        return Style::firstOrCreate([
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
        return 'id,name,code,created_at,updated_at';
    }

    public function getBasicColumnNamesProductCollection(): string
    {
        return 'id,name';
    }

    public function searchByColumns(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')
            ->whereAny(['name', 'code'], 'LIKE', '%' . $searchText . '%');
    }

    public function getStylesExport(array $filterData, int $companyId): Collection
    {
        return $this->getStylesQuery($filterData, $companyId)->get();
    }

    public function getStylesByCompanyId(int $companyId, array $filterData): LengthAwarePaginator
    {
        return Style::select('id', 'name', 'code', 'updated_at', 'created_at')
            ->where('company_id', $companyId)
            ->when($filterData['after_updated_at'], function ($query) use ($filterData): void {
                $query->where('updated_at', '>=', $filterData['after_updated_at']);
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function getMonthWiseSales(array $date, int $companyId, ?int $locationId, ?int $brandId): Collection
    {
        $cacheKey = 'cache-month-wise-styles-sales-' . $companyId . '-' . $locationId . '-' . $brandId . '-' . $date[0] . '-' . $date[1];

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => Style::query()
                ->select(
                    'styles.id',
                    'styles.name',
                    DB::raw('SUM(sale_items.total_price_paid) as total_amount'),
                    DB::raw('DATE_FORMAT(counter_updates.opened_by_pos_at,"%M") as month_string'),
                    DB::raw('DATE_FORMAT(counter_updates.opened_by_pos_at,"%m") as month'),
                )
                ->join('products', 'products.style_id', '=', 'styles.id')
                ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
                ->leftJoin('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->leftJoin('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                ->leftJoin('counters', 'counter_updates.counter_id', '=', 'counters.id')
                ->leftJoin('locations', 'counters.location_id', '=', 'locations.id')
                ->where('locations.company_id', $companyId)
                ->when($locationId, function ($query) use ($locationId): void {
                    $query->where('counters.location_id', $locationId);
                })
                ->when($brandId, function ($query) use ($brandId): void {
                    $query->where('products.brand_id', $brandId);
                })
                ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($date[0]))
                ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($date[1]))
                ->groupBy('month')
                ->groupBy('styles.id')
                ->get()
        );
    }

    public function getMonthWiseSaleReturns(array $date, int $companyId, ?int $locationId, ?int $brandId): Collection
    {
        $cacheKey = 'cache-month-wise-styles-sale-returns-' . $companyId . '-' . $locationId . '-' . $brandId . '-' . $date[0] . '-' . $date[1];

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => Style::query()
                ->select(
                    'styles.id',
                    'styles.name',
                    DB::raw('SUM(sale_return_items.total_price_paid) as total_amount'),
                    DB::raw('DATE_FORMAT(counter_updates.opened_by_pos_at,"%M") as month_string'),
                    DB::raw('DATE_FORMAT(counter_updates.opened_by_pos_at,"%m") as month'),
                )
                ->join('products', 'products.style_id', '=', 'styles.id')
                ->leftJoin('sale_return_items', 'products.id', '=', 'sale_return_items.product_id')
                ->leftJoin('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
                ->leftJoin('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                ->leftJoin('counters', 'counter_updates.counter_id', '=', 'counters.id')
                ->leftJoin('locations', 'counters.location_id', '=', 'locations.id')
                ->where('locations.company_id', $companyId)
                ->when($locationId, function ($query) use ($locationId): void {
                    $query->where('counters.location_id', $locationId);
                })
                ->when($brandId, function ($query) use ($brandId): void {
                    $query->where('products.brand_id', $brandId);
                })
                ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($date[0]))
                ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($date[1]))
                ->groupBy('month')
                ->groupBy('styles.id')
                ->get()
        );
    }

    public function getFilteredStylesByCompanyId(string $searchText, int $companyId): Collection
    {
        return Style::select('id', 'name')
            ->where('company_id', $companyId)
            ->where('name', 'like', '%' . $searchText . '%')
            ->limit(5)
            ->get();
    }

    public function getByCompanyId(int $companyId, ?string $afterUpdatedAt = null): Collection
    {
        return Style::select('id', 'name')
            ->where('company_id', $companyId)
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->get();
    }

    public function getCachedStylesSalesForChart(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string|array $date,
        bool $refresh = false
    ): Collection {
        if (is_array($date)) {
            $cacheKey = null !== $locationId ? 'cache-style-sales-' . $locationId . $brandId . $date[0] . $date[1] : 'cache-style-sales-' . $date[0] . $date[1] . $brandId;
        } else {
            $cacheKey = null !== $locationId ? 'cache-style-sales-' . $locationId . $brandId . $date : 'cache-style-sales-' . $date . $brandId;
        }

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => Style::query()
                ->select(
                    'styles.id',
                    'styles.name',
                    'style_sale_total.sales_count',
                    DB::raw(
                        '(COALESCE(style_sale_total.total_paid_amount, 0) - COALESCE(style_return_total.return_amount, 0)) as total_sales'
                    ),
                    DB::raw(
                        '(COALESCE(style_sale_total.units_sold, 0) - COALESCE(style_return_total.return_units, 0)) as total_units_sold'
                    ),
                )
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'sale_items.product_id', '=', 'products.id')
                        ->join('styles', 'products.style_id', '=', 'styles.id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $locationId > 0, function ($query) use ($locationId): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $brandId > 0, function ($query) use ($brandId): void {
                            $query->where('products.brand_id', $brandId);
                        })
                        ->when($date, function ($query) use ($date): void {
                            if (is_array($date)) {
                                $query->where(
                                    'counter_updates.opened_by_pos_at',
                                    '>=',
                                    CommonFunctions::addStartTime($date[0])
                                )
                                    ->where(
                                        'counter_updates.opened_by_pos_at',
                                        '<=',
                                        CommonFunctions::addEndTime($date[1])
                                    );
                            }

                            if (is_string($date)) {
                                $query->where(
                                    'counter_updates.opened_by_pos_at',
                                    '>=',
                                    CommonFunctions::addStartTime($date)
                                )
                                    ->where(
                                        'counter_updates.opened_by_pos_at',
                                        '<=',
                                        CommonFunctions::addEndTime($date)
                                    );
                            }
                        })
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->select(
                            'styles.id as style_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('style_id'),
                    'style_sale_total',
                    'style_sale_total.style_id',
                    '=',
                    'styles.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->join('styles', 'products.style_id', '=', 'styles.id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $locationId > 0, function ($query) use ($locationId): void {
                            $query->where('counters.location_id', $locationId);
                        })
                        ->when((int) $brandId > 0, function ($query) use ($brandId): void {
                            $query->where('products.brand_id', $brandId);
                        })
                        ->when($date, function ($query) use ($date): void {
                            if (is_array($date)) {
                                $query->where(
                                    'counter_updates.opened_by_pos_at',
                                    '>=',
                                    CommonFunctions::addStartTime($date[0])
                                )
                                    ->where(
                                        'counter_updates.opened_by_pos_at',
                                        '<=',
                                        CommonFunctions::addEndTime($date[1])
                                    );
                            }

                            if (is_string($date)) {
                                $query->where(
                                    'counter_updates.opened_by_pos_at',
                                    '>=',
                                    CommonFunctions::addStartTime($date)
                                )
                                    ->where(
                                        'counter_updates.opened_by_pos_at',
                                        '<=',
                                        CommonFunctions::addEndTime($date)
                                    );
                            }
                        })
                        ->select(
                            'styles.id as style_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('style_id'),
                    'style_return_total',
                    'style_return_total.style_id',
                    '=',
                    'styles.id'
                )
                ->whereNotNull('style_sale_total.total_paid_amount')
                ->orWhereNotNull('style_sale_total.units_sold')
                ->orWhereNotNull('style_sale_total.sales_count')
                ->orWhereNotNull('style_return_total.return_amount')
                ->orWhereNotNull('style_return_total.return_units')
                ->having('total_sales', '>', 0)
                ->having('total_units_sold', '>', 0)
                ->orderByDesc('total_sales')
                ->get()
        );
    }

    public function getCachedSeasonalTopFiveStyleSalesForChart(
        array $filterData,
        int $companyId,
        bool $refresh = false,
        array $ids = [],
    ): Collection {
        $cacheKey = null !== $filterData['location_id'] ? 'cache-seasonal-style-sales-' . $filterData['location_id'] . $filterData['brand_id'] . $filterData['start_date'] . $filterData['end_date'] : 'cache-seasonal-style-sales-' . $filterData['start_date'] . $filterData['end_date'] . $filterData['brand_id'];

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): Collection => Style::query()
                ->select(
                    'styles.id',
                    'styles.name',
                    'styles.code',
                    'style_sale_total.sales_count',
                    DB::raw(
                        '(COALESCE(style_sale_total.total_paid_amount, 0) - COALESCE(style_return_total.return_amount, 0)) as total_sales'
                    ),
                    DB::raw(
                        '(COALESCE(style_sale_total.units_sold, 0) - COALESCE(style_return_total.return_units, 0)) as total_units_sold'
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
                        ->join('styles', 'products.style_id', '=', 'styles.id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $filterData['location_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when((int) $filterData['brand_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['brand_id']);
                        })
                        ->when($filterData['start_date'] && $filterData['end_date'], function ($query) use (
                            $filterData
                        ): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['start_date'])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['end_date'])
                                );
                        })
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->select(
                            'styles.id as style_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('style_id'),
                    'style_sale_total',
                    'style_sale_total.style_id',
                    '=',
                    'styles.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->join('styles', 'products.style_id', '=', 'styles.id')
                        ->where('locations.company_id', $companyId)
                        ->when((int) $filterData['location_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('counters.location_id', $filterData['location_id']);
                        })
                        ->when((int) $filterData['brand_id'] > 0, function ($query) use ($filterData): void {
                            $query->where('products.brand_id', $filterData['brand_id']);
                        })
                        ->when($filterData['start_date'] && $filterData['end_date'], function ($query) use (
                            $filterData
                        ): void {
                            $query->where(
                                'counter_updates.opened_by_pos_at',
                                '>=',
                                CommonFunctions::addStartTime($filterData['start_date'])
                            )
                                ->where(
                                    'counter_updates.opened_by_pos_at',
                                    '<=',
                                    CommonFunctions::addEndTime($filterData['end_date'])
                                );
                        })
                        ->select(
                            'styles.id as style_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('style_id'),
                    'style_return_total',
                    'style_return_total.style_id',
                    '=',
                    'styles.id'
                )
                ->whereNotNull('style_sale_total.total_paid_amount')
                ->orWhereNotNull('style_sale_total.units_sold')
                ->orWhereNotNull('style_sale_total.sales_count')
                ->orWhereNotNull('style_return_total.return_amount')
                ->orWhereNotNull('style_return_total.return_units')
                ->orderByDesc('total_sales')
                ->take(5)
                ->get()
        );
    }

    public function getStyleSalesSummary(array $filterData, int $companyId): Collection
    {
        return Style::query()
            ->select(
                'styles.id',
                'styles.name',
                DB::raw(
                    '(COALESCE(style_sale_total.total_paid_amount, 0) - COALESCE(style_return_total.return_amount, 0)) as total_sales'
                ),
                DB::raw(
                    '(COALESCE(style_sale_total.units_sold, 0) - COALESCE(style_return_total.return_units, 0)) as total_units_sold'
                )
            )
            ->leftJoinSub(
                DB::table('sales')
                    ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->join('styles', 'products.style_id', '=', 'styles.id')
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
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::SIZES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.size_id', $filterData['id']);
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
                        $query->where('locations.id', (int) $filterData['locationId']);
                    })
                    ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                    ->select(
                        'styles.id as style_id',
                        DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                        DB::raw('SUM(sale_items.quantity) as units_sold')
                    )
                    ->groupBy('style_id'),
                'style_sale_total',
                'style_sale_total.style_id',
                '=',
                'styles.id'
            )
            ->leftJoinSub(
                DB::table('sale_returns')
                    ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                    ->join('styles', 'products.style_id', '=', 'styles.id')
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
                        $filterData['type'] === StoreRevenueDashboardTableFilterTypes::SIZES->value,
                        function ($query) use ($filterData): void {
                            $query->where('products.size_id', $filterData['id']);
                        }
                    )
                    ->where('locations.company_id', $companyId)
                    ->when(array_key_exists('locationId', $filterData), function ($query) use ($filterData): void {
                        $query->where('locations.id', (int) $filterData['locationId']);
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
                        'styles.id as style_id',
                        DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                        DB::raw('SUM(sale_return_items.quantity) as return_units')
                    )
                    ->groupBy('style_id'),
                'style_return_total',
                'style_return_total.style_id',
                '=',
                'styles.id'
            )
            ->whereNotNull('style_sale_total.total_paid_amount')
            ->orWhereNotNull('style_sale_total.units_sold')
            ->orWhereNotNull('style_return_total.return_amount')
            ->orWhereNotNull('style_return_total.return_units')
            ->orderByDesc('total_sales')
            ->get();
    }

    public function doAllStylesExist(int $companyId, array $styleIds): bool
    {
        $totalRecords = Style::whereIntegerInRaw('id', $styleIds)->where('company_id', $companyId)->count();

        return count($styleIds) === $totalRecords;
    }

    public function getIdAndNameColumnNames(): string
    {
        return 'id,name';
    }

    private function getStylesQuery(array $filterData, int $companyId): Builder
    {
        return Style::query()
            ->select('id', 'name', 'code')
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(['name', 'code'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getStyleNameForFilter(array $styleIds): string
    {
        $styleData = [];
        $style = Style::select('name')
            ->whereIntegerInRaw('id', $styleIds)
            ->get();

        if ($style->isNotEmpty()) {
            $styleData = $style->pluck('name')->toArray();
        }

        return implode(', ', $styleData);
    }

    public function getAllByCompanyId(int $companyId): Collection
    {
        return Style::select('id', 'name', 'company_id')
            ->where('company_id', $companyId)
            ->get();
    }
}
