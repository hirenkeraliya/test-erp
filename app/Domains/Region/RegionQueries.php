<?php

declare(strict_types=1);

namespace App\Domains\Region;

use App\Domains\Brand\BrandQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Region\DataObjects\RegionData;
use App\Domains\Sale\Enums\SaleStatus;
use App\Models\Region;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RegionQueries
{
    public function listQuery(array $filterData, int $companyId): LengthAwarePaginator
    {
        return $this->getRegions($filterData, $companyId)->paginate($filterData['per_page']);
    }

    public function addNew(RegionData $regionData, int $companyId): Region
    {
        $data = $regionData->all();
        $data['company_id'] = $companyId;

        return Region::create($data);
    }

    public function getById(int $regionId, int $companyId): Region
    {
        return Region::select('id', 'name', 'code', 'manager_name', 'manager_email', 'is_email_verified', 'company_id')
            ->where('company_id', $companyId)
            ->findOrFail($regionId);
    }

    public function update(RegionData $regionData, int $regionId, int $companyId): void
    {
        $region = $this->getById($regionId, $companyId);
        $region->update($regionData->all());
    }

    public function filterByCompany(int $companyId): Closure
    {
        return fn ($query) => $query->where('company_id', $companyId);
    }

    public function getBasicColumnNames(): string
    {
        return 'id,name,code';
    }

    public function getRegionsExport(array $filterData, int $companyId): SupportCollection
    {
        return $this->getRegions($filterData, $companyId)->get();
    }

    public function getRegionByCompanyId(int $companyId): SupportCollection
    {
        return Region::select('id', 'name')
            ->where('company_id', $companyId)
            ->get();
    }

    public function cacheRegionSales(int $companyId, int $brandId): SupportCollection
    {
        $cacheKey = 'cache-region-sales-' . $companyId . '-' . $brandId;

        return Cache::remember(
            $cacheKey,
            900,
            fn (): SupportCollection => Region::query()
                ->select(
                    'regions.id as region_id',
                    'regions.name',
                    DB::raw(
                        '(COALESCE(store_sale_total.total_paid_amount, 0) - COALESCE(store_return_total.return_amount, 0)) as total_sales'
                    ),
                )
                ->leftJoinSub(
                    DB::table('sales')
                        ->join('counter_updates', 'sales.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('regions', 'locations.region_id', '=', 'regions.id')
                        ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
                        ->join('products', 'products.id', '=', 'sale_items.product_id')
                        ->where('locations.company_id', $companyId)
                        ->when(0 !== $brandId, function ($query) use ($brandId): void {
                            $query->where('products.brand_id', $brandId);
                        })
                        ->where(
                            'counter_updates.opened_by_pos_at',
                            '>=',
                            now()->startOfDay()->format('Y-m-d H:i:s')
                        )
                        ->where('counter_updates.opened_by_pos_at', '<=', now()->endOfDay()->format('Y-m-d H:i:s'))
                        ->whereIntegerInRaw('sales.status', SaleStatus::getCommonActiveSaleStatusValues())
                        ->select(
                            'locations.region_id as region_id',
                            DB::raw('SUM(sale_items.total_price_paid) as total_paid_amount'),
                            DB::raw('SUM(sale_items.quantity) as units_sold'),
                            DB::raw('COUNT(sales.id) as sales_count'),
                        )
                        ->groupBy('locations.region_id'),
                    'store_sale_total',
                    'store_sale_total.region_id',
                    '=',
                    'regions.id'
                )
                ->leftJoinSub(
                    DB::table('sale_returns')
                        ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                        ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                        ->join('locations', 'counters.location_id', '=', 'locations.id')
                        ->join('regions', 'locations.region_id', '=', 'regions.id')
                        ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                        ->join('products', 'products.id', '=', 'sale_return_items.product_id')
                        ->where('locations.company_id', $companyId)
                        ->when(0 !== $brandId, function ($query) use ($brandId): void {
                            $query->where('products.brand_id', $brandId);
                        })
                        ->where(
                            'counter_updates.opened_by_pos_at',
                            '>=',
                            now()->startOfDay()->format('Y-m-d H:i:s')
                        )
                        ->where('counter_updates.opened_by_pos_at', '<=', now()->endOfDay()->format('Y-m-d H:i:s'))
                        ->select(
                            'locations.region_id as region_id',
                            DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                            DB::raw('SUM(sale_return_items.quantity) as return_units'),
                        )
                        ->groupBy('locations.region_id'),
                    'store_return_total',
                    'store_return_total.region_id',
                    '=',
                    'regions.id'
                )
                ->whereNotNull('store_sale_total.total_paid_amount')
                ->orWhereNotNull('store_sale_total.units_sold')
                ->orWhereNotNull('store_sale_total.sales_count')
                ->orWhereNotNull('store_return_total.return_amount')
                ->orWhereNotNull('store_return_total.return_units')
                ->orderByDesc('total_sales')
                ->get()
        );
    }

    public function existsByName(string $name, int $companyId): bool
    {
        return Region::whereCaseSensitive('name', $name)->where('company_id', $companyId)->exists();
    }

    public function existsByCode(string $code, int $companyId): bool
    {
        return Region::whereCaseSensitive('code', $code)->where('company_id', $companyId)->exists();
    }

    public function getWithBasicColumns(int $companyId): SupportCollection
    {
        return Region::select('id', 'name')
            ->where('company_id', $companyId)
            ->get();
    }

    public function getRegionsIdColumn(): SupportCollection
    {
        return Region::select('id')
            ->whereNotNull('manager_email')
            ->get();
    }

    public function getRegionByIdWithStoresAndBrands(int $regionId): Region
    {
        $locationQueries = resolve(LocationQueries::class);
        $brandQueries = resolve(BrandQueries::class);

        return Region::select('id', 'company_id', 'name', 'manager_email')
            ->with([
                'locations:' . $locationQueries->getRegionColumnNames(),
                'locations.brands:' . $brandQueries->getBasicColumnNames(),
            ])
            ->whereNotNull('manager_email')
            ->findOrFail($regionId);
    }

    public function existsByCodeExceptCurrentRecord(string $code, string $name, int $companyId): bool
    {
        return Region::whereCaseSensitive('code', $code)
            ->whereNotCaseSensitive('name', $name)
            ->where('company_id', $companyId)
            ->exists();
    }

    public function updateByName(RegionData $regionData, int $companyId): void
    {
        $regionDetails = $regionData->all();
        $regionDetails['company_id'] = $companyId;
        $region = Region::select('id')
            ->where('company_id', $companyId)
            ->where('name', $regionDetails['name'])
            ->first();
        if ($region instanceof Region) {
            $region->update($regionDetails);
        }
    }

    public function getByIdForEmailVerification(int $regionId, int $companyId): Region
    {
        return Region::select('id', 'manager_email')
            ->where('company_id', $companyId)
            ->findOrFail($regionId);
    }

    private function getRegions(array $filterData, int $companyId): Builder
    {
        return Region::query()
            ->select('id', 'name', 'code', 'manager_name', 'manager_email', 'is_email_verified')
            ->where('company_id', $companyId)
            ->when($filterData['search_text'], function ($query) use ($filterData): void {
                $query->where(function ($query) use ($filterData): void {
                    $query
                        ->whereAny(
                            ['name', 'code', 'manager_name', 'manager_email'],
                            'LIKE',
                            '%' . $filterData['search_text'] . '%'
                        );
                });
            })
            ->when($filterData['sort_by'], function ($query) use ($filterData): void {
                $query->orderBy($filterData['sort_by'], $filterData['sort_direction']);
            }, function ($query): void {
                $query->orderBy('id', 'desc');
            });
    }

    public function getRegionNameForFilter(array $regionIds): string
    {
        $regionData = [];
        $region = Region::select('name')
            ->whereIntegerInRaw('id', values: $regionIds)
            ->get();

        if ($region->isNotEmpty()) {
            $regionData = $region->pluck('name')->toArray();
        }

        return implode(', ', $regionData);
    }

    public function getAllByCompanyId(int $companyId): SupportCollection
    {
        return Region::select('id', 'name', 'company_id')
            ->where('company_id', $companyId)
            ->get();
    }
}
