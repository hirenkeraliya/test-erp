<?php

declare(strict_types=1);

namespace App\Domains\Brand;

use App\CommonFunctions;
use App\Domains\Brand\DataObjects\BrandData;
use App\Domains\Company\CompanyQueries;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\Brand;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BrandQueries
{
    public function listQuery(array $filterData): LengthAwarePaginator
    {
        return Brand::query()
            ->select('id', 'name', 'code')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->whereAny(['name', 'code'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->paginate($filterData['per_page']);
    }

    public function addNew(BrandData $brandData): void
    {
        Brand::create($brandData->all());
    }

    public function getById(int $brandId): Brand
    {
        return Brand::select('id', 'name', 'code')->findOrFail($brandId);
    }

    public function getFirstForEcommerceSync(int $saleChannelId): ?Brand
    {
        return Brand::select('id')
            ->whereDoesntHave('brandChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->orderBy('id', 'asc')
            ->first();
    }

    public function getLastForEcommerceSync(int $saleChannelId): ?Brand
    {
        return Brand::select('id')
            ->whereDoesntHave('brandChannelReferences', function ($query) use ($saleChannelId): void {
                $query->where('sale_channel_id', $saleChannelId);
            })
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getBrandEcommerceChannelByStartAndEndId(
        int $startId,
        int $endId,
        int $saleChannelId
    ): Collection {
        return Brand::select('id', 'name', 'code')
        ->whereDoesntHave('brandChannelReferences', function ($query) use ($saleChannelId): void {
            $query->where('sale_channel_id', $saleChannelId);
        })
            ->where('id', '>=', $startId)
            ->where('id', '<=', $endId)
            ->get();
    }

    public function getByIdWithCompanies(int $brandId): Brand
    {
        $companyQueries = resolve(CompanyQueries::class);

        return Brand::query()
            ->select('id', 'name', 'code')
            ->with('companies:' . $companyQueries->getBasicColumnNamesWithCode())
            ->findOrFail($brandId);
    }

    public function update(BrandData $brandData, int $brandId): void
    {
        $brand = $this->getById($brandId);
        $brand->update($brandData->all());
    }

    public function getWithBasicColumns(): Collection
    {
        return Brand::select('id', 'name')->get();
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,code';
    }

    public function getBasicColumnNamesForEcommerce(): string
    {
        return 'id,name,code,created_at,updated_at';
    }

    public function getIdAndNameColumnNames(): string
    {
        return 'id,name';
    }

    public function getCompanyBrands(int $companyId, ?string $afterUpdatedAt = null): Collection
    {
        $companyQueries = new CompanyQueries();

        return Brand::select('id', 'name')
            ->whereHas('companies', $companyQueries->filterById($companyId))
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->get();
    }

    public function searchByName(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')->where('name', 'like', '%' . $searchText . '%');
    }

    public function searchByColumns(string $searchText): Closure
    {
        return fn ($query) => $query
            ->whereAny(['name', 'code'], 'LIKE', '%' . $searchText . '%');
    }

    public function getFilteredBrandsByCompanyId(string $searchText, int $companyId): SupportCollection
    {
        $companyQueries = resolve(CompanyQueries::class);

        return Brand::select('id', 'name')
            ->whereHas('companies', $companyQueries->filterById($companyId))
            ->where('name', 'like', '%' . $searchText . '%')
            ->limit(5)
            ->get();
    }

    public function filterByIds(array $brandIds): Closure
    {
        return fn ($query) => $query->select('id')->whereIntegerInRaw('id', $brandIds);
    }

    public function getByIds(array $brandIds): SupportCollection
    {
        return Brand::select('name')
            ->whereIntegerInRaw('id', $brandIds)
            ->get();
    }

    public function getIdByName(string $name): int
    {
        /** @var Brand $brand */
        $brand = Brand::where('name', $name)->first();

        return $brand->id;
    }

    public function existsByName(string $name, int $companyId): bool
    {
        $companyQueries = new CompanyQueries();

        return Brand::select('id')
            ->whereCaseSensitive('name', $name)
            ->whereHas('companies', $companyQueries->filterById($companyId))
            ->exists();
    }

    public function existsByNames(array $names, int $companyId): Collection
    {
        $companyQueries = new CompanyQueries();

        return Brand::select('id', 'name')
            ->whereIn('name', $names)
            ->whereHas('companies', $companyQueries->filterById($companyId))
            ->get();
    }

    public function doExistsById(int $companyId, array $brandIds): bool
    {
        $companyQueries = new CompanyQueries();

        $totalRecords = Brand::whereIntegerInRaw('id', $brandIds)
            ->whereHas('companies', $companyQueries->filterById($companyId))
            ->count();

        return count($brandIds) === $totalRecords;
    }

    public function doBrandNamesExists(array $brandNames, int $companyId): bool
    {
        $companyQueries = new CompanyQueries();

        $totalRecords = Brand::whereInCaseSensitive('name', $brandNames)
            ->whereHas('companies', $companyQueries->filterById($companyId))
            ->count();

        return count($brandNames) === $totalRecords;
    }

    /**
     * @return mixed[]
     */
    public function getIdsByNames(array $brandNames, int $companyId): array
    {
        $companyQueries = new CompanyQueries();

        return Brand::select('id')->whereIn('name', $brandNames)
            ->whereHas('companies', $companyQueries->filterById($companyId))
            ->get()->pluck('id')->toArray();
    }

    public function getCachedBrandsSalesForChart(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string|array $date,
        bool $refresh = false
    ): SupportCollection {
        if (is_array($date)) {
            $cacheKey = null !== $locationId ? 'cache-brands-sales-' . $locationId . $brandId . $date[0] . $date[1] : 'cache-brands-sales-' . $date[0] . $date[1] . $brandId;
        } else {
            $cacheKey = null !== $locationId ? 'cache-brands-sales-' . $locationId . $brandId . $date : 'cache-brands-sales-' . $date . $brandId;
        }

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): SupportCollection => Brand::query()
                ->select(
                    'brands.id',
                    'brands.name',
                    'brand_sale_total.sales_count',
                    DB::raw(
                        '(COALESCE(brand_sale_total.total_paid_amount, 0) - COALESCE(brand_return_total.return_amount, 0)) as total_sales'
                    ),
                    DB::raw(
                        '(COALESCE(brand_sale_total.units_sold, 0) - COALESCE(brand_return_total.return_units, 0)) as total_units_sold'
                    ),
                )
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'sale_items.product_id', '=', 'products.id')
                        ->join('brands', 'products.brand_id', '=', 'brands.id')
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
                            'brands.id as brand_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('brand_id'),
                    'brand_sale_total',
                    'brand_sale_total.brand_id',
                    '=',
                    'brands.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->join('brands', 'products.brand_id', '=', 'brands.id')
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
                            'brands.id as brand_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('brand_id'),
                    'brand_return_total',
                    'brand_return_total.brand_id',
                    '=',
                    'brands.id'
                )
                ->whereNotNull('brand_sale_total.total_paid_amount')
                ->orWhereNotNull('brand_sale_total.units_sold')
                ->orWhereNotNull('brand_sale_total.sales_count')
                ->orWhereNotNull('brand_return_total.return_amount')
                ->orWhereNotNull('brand_return_total.return_units')
                ->having('total_sales', '>', 0)
                ->having('total_units_sold', '>', 0)
                ->orderByDesc('total_sales')
                ->get()
        );
    }

    public function getMonthWiseBrandsSales(int $companyId, ?int $locationId, ?int $brandId): Collection
    {
        $cacheKey = 'cache-month-wise-brands-sales-' . $companyId . '-' . $locationId . '-' . $brandId;

        return Cache::remember(
            $cacheKey,
            900,
            fn (): SupportCollection => Brand::query()
                ->select(
                    'brands.id',
                    'brands.name',
                    DB::raw('SUM(sale_items.total_price_paid) as total_amount'),
                    DB::raw('DATE_FORMAT(counter_updates.opened_by_pos_at,"%M") as month_string'),
                    DB::raw('DATE_FORMAT(counter_updates.opened_by_pos_at,"%m") as month'),
                )
                ->join('products', 'products.brand_id', '=', 'brands.id')
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
                    $query->where('brands.id', $brandId);
                })
                ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                ->where('counter_updates.opened_by_pos_at', '>=', now()->startOfYear()->format('Y-m-d'))
                ->where('counter_updates.opened_by_pos_at', '<=', now()->endOfYear()->format('Y-m-d'))
                ->groupBy('month')
                ->groupBy('brands.id')
                ->get()
        );
    }

    public function getMonthWiseBrandsSaleReturns(int $companyId, ?int $locationId, ?int $brandId): Collection
    {
        $cacheKey = 'cache-month-wise-brands-sale-returns-' . $companyId . '-' . $locationId . '-' . $brandId;

        return Cache::remember(
            $cacheKey,
            900,
            fn (): SupportCollection => Brand::query()
                ->select(
                    'brands.id',
                    'brands.name',
                    DB::raw('SUM(sale_return_items.total_price_paid) as total_amount'),
                    DB::raw('DATE_FORMAT(counter_updates.opened_by_pos_at,"%M") as month_string'),
                    DB::raw('DATE_FORMAT(counter_updates.opened_by_pos_at,"%m") as month'),
                )
                ->join('products', 'products.brand_id', '=', 'brands.id')
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
                    $query->where('brands.id', $brandId);
                })
                ->where('counter_updates.opened_by_pos_at', '>=', now()->startOfYear()->format('Y-m-d'))
                ->where('counter_updates.opened_by_pos_at', '<=', now()->endOfYear()->format('Y-m-d'))
                ->groupBy('month')
                ->groupBy('brands.id')
                ->get()
        );
    }

    public function getByCompanyId(int $companyId): Collection
    {
        $companyQueries = resolve(CompanyQueries::class);

        return Brand::select('id', 'name')
            ->whereHas('companies', $companyQueries->filterById($companyId))
            ->get();
    }

    public function getBrands(int $companyId): SupportCollection
    {
        $companyQueries = new CompanyQueries();

        return Brand::select('id', 'name')
            ->whereHas('companies', $companyQueries->filterById($companyId))
            ->get();
    }

    public function getBrandSalesSummary(array $filterData, int $companyId): SupportCollection
    {
        return Brand::query()
            ->select(
                'brands.id',
                'brands.name',
                DB::raw(
                    '(COALESCE(brand_sale_total.total_paid_amount, 0) - COALESCE(brand_return_total.return_amount, 0)) as total_sales'
                ),
                DB::raw(
                    '(COALESCE(brand_sale_total.units_sold, 0) - COALESCE(brand_return_total.return_units, 0)) as total_units_sold'
                )
            )
            ->leftJoinSub(
                DB::table('sales')
                    ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->join('brands', 'products.brand_id', '=', 'brands.id')
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
                        'brands.id as brand_id',
                        DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                        DB::raw('SUM(sale_items.quantity) as units_sold')
                    )
                    ->groupBy('brand_id'),
                'brand_sale_total',
                'brand_sale_total.brand_id',
                '=',
                'brands.id'
            )
            ->leftJoinSub(
                DB::table('sale_returns')
                    ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                    ->join('brands', 'products.brand_id', '=', 'brands.id')
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
                        'brands.id as brand_id',
                        DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                        DB::raw('SUM(sale_return_items.quantity) as return_units')
                    )
                    ->groupBy('brand_id'),
                'brand_return_total',
                'brand_return_total.brand_id',
                '=',
                'brands.id'
            )
            ->whereNotNull('brand_sale_total.total_paid_amount')
            ->orWhereNotNull('brand_sale_total.units_sold')
            ->orWhereNotNull('brand_return_total.return_amount')
            ->orWhereNotNull('brand_return_total.return_units')
            ->orderByDesc('total_sales')
            ->get();
    }

    public function firstOrCreateByName(string $name, int $companyId): int
    {
        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getById($companyId);
        $brandId = Brand::firstOrCreate([
            'name' => $name,
        ])->id;
        $company->brands()->sync([$brandId]);

        return $brandId;
    }

    public function getSalesRecordsGroupedByBrandAndRegion(
        array $filterData,
        int $companyId,
        bool $excludeProductsWithNoPrice
    ): SupportCollection {
        $currentDate = $filterData['date'] ?? Carbon::now()->format('Y-m-d');

        /** @var Carbon $yesterdayDateCarbon */
        $yesterdayDateCarbon = Carbon::createFromFormat('Y-m-d', $currentDate);
        $yesterdayDate = $yesterdayDateCarbon->subDays()->format('m-d');
        $currentYearSales = DB::table('brands')
            ->select(
                'brands.name as brand_name',
                'locations.name as location_name',
                'regions.name as region_name',
                'locations.code',
                DB::raw('DATE(sales.happened_at) as date'),
                DB::raw("DATE_FORMAT(sales.happened_at, '%d-%m-%Y') AS date"),
                DB::raw('SUM(sale_items.total_price_paid) as total_price_paid')
            )
           ->when(config('app.product_variant'), function ($query): void {
               $query->leftJoin('master_products', 'brands.id', '=', 'master_products.brand_id')
                    ->leftJoin('products', 'master_products.id', '=', 'products.master_product_id');
           }, function ($query): void {
               $query->leftJoin('products', 'brands.id', '=', 'products.brand_id');
           })
            ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('sale_item_promoter', 'sale_items.id', '=', 'sale_item_promoter.sale_item_id')
            ->leftJoin('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->leftJoin('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->leftJoin('locations', 'locations.id', '=', 'counters.location_id')
            ->leftJoin('regions', 'regions.id', '=', 'locations.region_id')
            ->whereIn('sales.status', [
                SaleStatus::REGULAR_SALE->value,
                SaleStatus::COMPLETE_LAYAWAY_SALE->value,
                SaleStatus::COMPLETE_CREDIT_SALE->value,
            ])
            ->where('locations.company_id', $companyId)
            ->where(function ($query) use ($currentDate): void {
                $query->where('sales.happened_at', '>=', CommonFunctions::addStartTime($currentDate))
                    ->where('sales.happened_at', '<=', CommonFunctions::addEndTime($currentDate));
            })
            ->when(
                isset($filterData['e_invoice_submitted']) && null != $filterData['e_invoice_submitted'],
                function ($query) use ($filterData): void {
                    $query->whereNot('sales.digital_invoice_submitted', $filterData['e_invoice_submitted']);
                }
            )
            ->when($excludeProductsWithNoPrice, function ($query): void {
                $query->where('products.retail_price', '>', 0);
            })
            ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereIntegerInRaw('brands.id', $filterData['brand_ids']);
            })
            ->when($filterData['counter_ids'] && null !== $filterData['counter_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereIntegerInRaw('counters.id', $filterData['counter_ids']);
            })
            ->when($filterData['department_ids'] && null !== $filterData['department_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereIntegerInRaw(
                    config('app.product_variant') ? 'master_products.department_id' : 'products.department_id',
                    $filterData['department_ids']
                );
            })
            ->when($filterData['location_ids'] && [] !== $filterData['location_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
            })
            ->when($filterData['promoter_ids'] && null !== $filterData['promoter_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereIntegerInRaw('sale_item_promoter.promoter_id', $filterData['promoter_ids']);
            })
            ->groupBy(
                'brands.name',
                'locations.name',
                'locations.region_id',
                'locations.code',
                DB::raw('DATE(sales.happened_at)')
            )
            ->get();

        $previousYearsSales = DB::table('brands')
            ->select(
                'brands.name as brand_name',
                'locations.name as location_name',
                'locations.region_id',
                'regions.name as region_name',
                'locations.code',
                DB::raw("DATE_FORMAT(sales.happened_at, '%d-%m-%Y') AS date"),
                DB::raw('SUM(sale_items.total_price_paid) as total_price_paid')
            )
            ->when(config('app.product_variant'), function ($query): void {
                $query->leftJoin('master_products', 'brands.id', '=', 'master_products.brand_id')
                    ->leftJoin('products', 'master_products.id', '=', 'products.master_product_id');
            }, function ($query): void {
                $query->leftJoin('products', 'brands.id', '=', 'products.brand_id');
            })
            ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('sale_item_promoter', 'sale_items.id', '=', 'sale_item_promoter.sale_item_id')
            ->leftJoin('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
            ->leftJoin('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->leftJoin('locations', 'locations.id', '=', 'counters.location_id')
            ->leftJoin('regions', 'regions.id', '=', 'locations.region_id')
            ->whereIn('sales.status', [
                SaleStatus::REGULAR_SALE->value,
                SaleStatus::COMPLETE_LAYAWAY_SALE->value,
                SaleStatus::COMPLETE_CREDIT_SALE->value,
            ])
            ->where('locations.company_id', $companyId)
            ->where(DB::raw("DATE_FORMAT(sales.happened_at, '%m-%d')"), '=', $yesterdayDate)
            ->when($excludeProductsWithNoPrice, function ($query): void {
                $query->where('products.retail_price', '>', 0);
            })
            ->when(
                isset($filterData['e_invoice_submitted']) && null != $filterData['e_invoice_submitted'],
                function ($query) use ($filterData): void {
                    $query->whereNot('sales.digital_invoice_submitted', $filterData['e_invoice_submitted']);
                }
            )
            ->when($filterData['brand_ids'] && null !== $filterData['brand_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereIntegerInRaw('brands.id', $filterData['brand_ids']);
            })
            ->when($filterData['counter_ids'] && null !== $filterData['counter_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereIntegerInRaw('counters.id', $filterData['counter_ids']);
            })
            ->when($filterData['department_ids'] && null !== $filterData['department_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereIntegerInRaw(
                    config('app.product_variant') ? 'master_products.department_id' : 'products.department_id',
                    $filterData['department_ids']
                );
            })
            ->when($filterData['location_ids'] && [] !== $filterData['location_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereIntegerInRaw('locations.id', $filterData['location_ids']);
            })
            ->when($filterData['promoter_ids'] && null !== $filterData['promoter_ids'], function ($query) use (
                $filterData
            ): void {
                $query->whereIntegerInRaw('sale_item_promoter.promoter_id', $filterData['promoter_ids']);
            })
            ->groupBy(
                'brands.name',
                'locations.name',
                'locations.region_id',
                'locations.code',
                DB::raw('DATE(sales.happened_at)')
            )
            ->get();

        return $currentYearSales
            ->merge($previousYearsSales)
            ->groupBy(['brand_name', 'region_name', 'date'])
            ->sortByDesc('date');
    }

    public function getBrandsExport(array $filterData): Collection
    {
        return Brand::query()
            ->select('id', 'name', 'code')
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query->whereAny(['name', 'code'], 'LIKE', '%' . $filterData['search_text'] . '%');
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            })
            ->get();
    }

    public function getBrandNameForFilter(array $brandIds): string
    {
        $brandData = [];
        $brand = Brand::select('name')
            ->whereIntegerInRaw('id', values: $brandIds)
            ->get();

        if ($brand->isNotEmpty()) {
            $brandData = $brand->pluck('name')->toArray();
        }

        return implode(', ', $brandData);
    }

    public function getAllByCompanyId(int $companyId): SupportCollection
    {
        $companyQueries = resolve(CompanyQueries::class);

        return Brand::select('id', 'name')
            ->whereHas('companies', $companyQueries->filterById($companyId))
            ->get();
    }

    public function getIdByFirstCompanyBrand(int $companyId): ?int
    {
        $companyQueries = resolve(CompanyQueries::class);

        return Brand::select('id', 'name')
            ->whereHas('companies', $companyQueries->filterById($companyId))
            ->first()
            ?->id;
    }
}
