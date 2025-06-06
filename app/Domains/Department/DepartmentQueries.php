<?php

declare(strict_types=1);

namespace App\Domains\Department;

use App\CommonFunctions;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Department\DataObjects\DepartmentData;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\Department;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DepartmentQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getDepartmentQuery($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(DepartmentData $departmentData, int $companyId): Department
    {
        $data = $departmentData->all();
        $data['company_id'] = $companyId;

        return Department::create($data);
    }

    public function getById(int $departmentId, int $companyId): Department
    {
        return Department::select('id', 'name', 'code', 'commission_percentage', 'flat_commission', 'discount_type')
            ->where('company_id', $companyId)
            ->findOrFail($departmentId);
    }

    public function update(DepartmentData $departmentData, int $departmentId, int $companyId): void
    {
        $department = $this->getById($departmentId, $companyId);
        $department->update($departmentData->all());
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getWithBasicColumns(int $companyId, ?string $afterUpdatedAt = null): Collection
    {
        return Department::select('id', 'name')
            ->where('company_id', $companyId)
            ->when($afterUpdatedAt, function ($query) use ($afterUpdatedAt): void {
                $query->where('updated_at', '>=', $afterUpdatedAt);
            })
            ->get();
    }

    public function existsByName(string $name, int $companyId): bool
    {
        return Department::whereCaseSensitive('name', $name)->where('company_id', $companyId)->exists();
    }

    public function getIdByName(string $name, int $companyId): int
    {
        return Department::firstOrCreate([
            'name' => $name,
            'company_id' => $companyId,
        ])->id;
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,code,commission_percentage,discount_type,flat_commission,company_id';
    }

    public function getBasicColumnNamesForEcommerce(): string
    {
        return 'id,name,code,created_at,updated_at';
    }

    public function searchByColumns(string $searchText): Closure
    {
        return fn ($query) => $query->select('id')
            ->whereAny(['name', 'code'], 'LIKE', '%' . $searchText . '%');
    }

    public function getFilteredDepartmentsByCompanyId(string $searchText, int $companyId): SupportCollection
    {
        return Department::select('id', 'name')
            ->where('company_id', $companyId)
            ->where('name', 'like', '%' . $searchText . '%')
            ->limit(5)
            ->get();
    }

    public function filterById(int $departmentId): Closure
    {
        return fn ($query) => $query->where('id', $departmentId);
    }

    public function filterByIds(array $departmentIds): Closure
    {
        return fn ($query) => $query->select('id')->whereIntegerInRaw('id', $departmentIds);
    }

    public function getByIds(array $departmentIds): SupportCollection
    {
        return Department::select('name')
            ->whereIntegerInRaw('id', $departmentIds)
            ->get();
    }

    public function getDepartmentsExport(array $filterData, int $companyId): SupportCollection
    {
        return $this->getDepartmentQuery($filterData, $companyId)->get();
    }

    public function getBasicColumnNamesForHappyHours(): string
    {
        return 'id,name';
    }

    public function getIdByNameForImportRecord(string $name): int
    {
        /** @var Department $department */
        $department = Department::where('name', $name)->first();

        return $department->id;
    }

    public function getCachedDepartmentSaleForChart(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string|array $date,
        bool $refresh = false
    ): SupportCollection {
        if (is_array($date)) {
            $cacheKey = null !== $locationId ? 'cache-departments-sales-' . $locationId . $brandId . $date[0] . $date[1] : 'cache-departments-sales-' . $date[0] . $date[1] . $brandId;
        } else {
            $cacheKey = null !== $locationId ? 'cache-departments-sales-' . $locationId . $brandId . $date : 'cache-departments-sales-' . $date . $brandId;
        }

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            Cache::has($cacheKey) && Cache::get($cacheKey)->isNotEmpty() ? 600 : 150,
            fn (): SupportCollection => Department::query()
                ->select(
                    'departments.id',
                    'departments.name',
                    'department_sale_total.sales_count',
                    DB::raw(
                        '(COALESCE(department_sale_total.total_paid_amount, 0) - COALESCE(department_return_total.return_amount, 0)) as total_sales'
                    ),
                    DB::raw(
                        '(COALESCE(department_sale_total.units_sold, 0) - COALESCE(department_return_total.return_units, 0)) as total_units_sold'
                    ),
                )
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'sale_items.product_id', '=', 'products.id')
                        ->join('departments', 'products.department_id', '=', 'departments.id')
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
                            'departments.id as department_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('department_id'),
                    'department_sale_total',
                    'department_sale_total.department_id',
                    '=',
                    'departments.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->join('departments', 'products.department_id', '=', 'departments.id')
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
                            'departments.id as department_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('department_id'),
                    'department_return_total',
                    'department_return_total.department_id',
                    '=',
                    'departments.id'
                )
                ->whereNotNull('department_sale_total.total_paid_amount')
                ->orWhereNotNull('department_sale_total.units_sold')
                ->orWhereNotNull('department_sale_total.sales_count')
                ->orWhereNotNull('department_return_total.return_amount')
                ->orWhereNotNull('department_return_total.return_units')
                ->having('total_sales', '>', 0)
                ->having('total_units_sold', '>', 0)
                ->orderByDesc('total_sales')
                ->get()
        );
    }

    public function getCachedSeasonalTopFiveDepartmentSalesForChart(
        array $filterData,
        int $companyId,
        bool $refresh = false,
        array $ids = [],
    ): SupportCollection {
        $cacheKey = null !== $filterData['location_id'] ? 'cache-seasonal-departments-sales-' . $filterData['location_id'] . $filterData['brand_id'] . $filterData['start_date'] . $filterData['end_date'] : 'cache-departments-sales-' . $filterData['start_date'] . $filterData['end_date'] . $filterData['brand_id'];

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            Cache::has($cacheKey) && Cache::get($cacheKey)->isNotEmpty() ? 600 : 150,
            fn (): SupportCollection => Department::query()
                ->select(
                    'departments.id',
                    'departments.name',
                    'departments.code',
                    'department_sale_total.sales_count',
                    DB::raw(
                        '(COALESCE(department_sale_total.total_paid_amount, 0) - COALESCE(department_return_total.return_amount, 0)) as total_sales'
                    ),
                    DB::raw(
                        '(COALESCE(department_sale_total.units_sold, 0) - COALESCE(department_return_total.return_units, 0)) as total_units_sold'
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
                        ->join('departments', 'products.department_id', '=', 'departments.id')
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
                            'departments.id as department_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('department_id'),
                    'department_sale_total',
                    'department_sale_total.department_id',
                    '=',
                    'departments.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                        ->join('departments', 'products.department_id', '=', 'departments.id')
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
                            'departments.id as department_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('department_id'),
                    'department_return_total',
                    'department_return_total.department_id',
                    '=',
                    'departments.id'
                )
                ->whereNotNull('department_sale_total.total_paid_amount')
                ->orWhereNotNull('department_sale_total.units_sold')
                ->orWhereNotNull('department_sale_total.sales_count')
                ->orWhereNotNull('department_return_total.return_amount')
                ->orWhereNotNull('department_return_total.return_units')
                ->orderByDesc('total_sales')
                ->take(5)
                ->get()
        );
    }

    public function getDepartmentSalesSummary(array $filterData, int $companyId): SupportCollection
    {
        return Department::query()
            ->select(
                'departments.id',
                'departments.name',
                DB::raw(
                    '(COALESCE(department_sale_total.total_paid_amount, 0) - COALESCE(department_return_total.return_amount, 0)) as total_sales'
                ),
                DB::raw(
                    '(COALESCE(department_sale_total.units_sold, 0) - COALESCE(department_return_total.return_units, 0)) as total_units_sold'
                )
            )
            ->leftJoinSub(
                DB::table('sales')
                    ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->join('departments', 'products.department_id', '=', 'departments.id')
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
                        'departments.id as department_id',
                        DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                        DB::raw('SUM(sale_items.quantity) as units_sold')
                    )
                    ->groupBy('department_id'),
                'department_sale_total',
                'department_sale_total.department_id',
                '=',
                'departments.id'
            )
            ->leftJoinSub(
                DB::table('sale_returns')
                    ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                    ->join('departments', 'products.department_id', '=', 'departments.id')
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
                        'departments.id as department_id',
                        DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                        DB::raw('SUM(sale_return_items.quantity) as return_units')
                    )
                    ->groupBy('department_id'),
                'department_return_total',
                'department_return_total.department_id',
                '=',
                'departments.id'
            )
            ->whereNotNull('department_sale_total.total_paid_amount')
            ->orWhereNotNull('department_sale_total.units_sold')
            ->orWhereNotNull('department_return_total.return_amount')
            ->orWhereNotNull('department_return_total.return_units')
            ->orderByDesc('total_sales')
            ->get();
    }

    public function doAllDepartmentExist(int $companyId, array $departmentIds): bool
    {
        $totalRecords = Department::whereIntegerInRaw('id', $departmentIds)->where('company_id', $companyId)->count();

        return count($departmentIds) === $totalRecords;
    }

    private function getDepartmentQuery(array $filterData, int $companyId): Builder
    {
        return Department::query()
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

    public function getDepartmentNamesByIds(int $companyId, array $departmentIds): ?Department
    {
        return Department::selectRaw("GROUP_CONCAT(CONCAT(name) SEPARATOR ', ') AS names")
            ->whereIntegerInRaw('id', $departmentIds)
            ->where('company_id', $companyId)
            ->first();
    }

    public function getByIdOnlyName(int $departmentId, int $companyId): Department
    {
        return Department::query()
            ->select('id', 'name')
            ->where('company_id', $companyId)
            ->findOrFail($departmentId);
    }

    public function getDepartmentNameForFilter(array $departmentIds): string
    {
        $departmentData = [];
        $department = Department::select('name')
            ->whereIntegerInRaw('id', values: $departmentIds)
            ->get();

        if ($department->isNotEmpty()) {
            $departmentData = $department->pluck('name')->toArray();
        }

        return implode(', ', $departmentData);
    }
}
