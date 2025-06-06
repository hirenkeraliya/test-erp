<?php

declare(strict_types=1);

namespace App\Domains\SaleReturnItem;

use App\CommonFunctions;
use App\Domains\Attribute\AttributeQueries;
use App\Domains\Brand\BrandQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Counter\CounterQueries;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\MasterProduct\MasterProductQueries;
use App\Domains\Product\Enums\ProductTypes;
use App\Domains\Product\ProductQueries;
use App\Domains\ProductVariantValue\ProductVariantValueQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\Size\SizeQueries;
use App\Models\SaleReturnItem;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SaleReturnItemQueries
{
    public function addNew(
        int $saleReturnReasonId,
        int $saleReturnId,
        int $originalSaleItemId,
        int $productId,
        float $quantity,
        float $totalPricePaid,
        float $itemTax,
        float $itemCartDiscount,
        float $itemDiscountAmount,
        float $totalDiscountAmount
    ): SaleReturnItem {
        return SaleReturnItem::create([
            'sale_return_id' => $saleReturnId,
            'original_sale_item_id' => $originalSaleItemId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'total_price_paid' => CommonFunctions::numberFormat($totalPricePaid),
            'cart_discount_amount' => CommonFunctions::numberFormat($itemCartDiscount),
            'item_discount_amount' => CommonFunctions::numberFormat($itemDiscountAmount),
            'total_discount_amount' => CommonFunctions::numberFormat($totalDiscountAmount),
            'total_tax_amount' => CommonFunctions::numberFormat($itemTax),
            'sale_return_reason_id' => $saleReturnReasonId,
        ]);
    }

    public static function getColumnNamesForSaleUpdate(): string
    {
        return 'id,sale_return_id,cart_discount_amount,item_discount_amount,total_discount_amount,total_tax_amount,total_price_paid';
    }

    public static function getColumnNamesForSaleExchange(): string
    {
        return 'id,sale_return_id,original_sale_item_id,product_id,total_price_paid,sale_return_reason_id';
    }

    public static function getColumnNames(): string
    {
        return 'id,sale_return_id,original_sale_item_id,product_id,total_price_paid';
    }

    public function getBasicColumnNamesInArray(): array
    {
        return ['id', 'sale_return_id', 'original_sale_item_id', 'quantity', 'total_price_paid'];
    }

    public static function getColumnNamesForPos(): string
    {
        return 'id,sale_return_id,original_sale_item_id,product_id,quantity,total_price_paid,cart_discount_amount,item_discount_amount,total_discount_amount,total_tax_amount,sale_return_reason_id';
    }

    public static function getQuantityColumnForSaleExchanges(): string
    {
        return 'id,sale_return_id,quantity';
    }

    public function filterForSalesByPromotersReport(array $filterData): Closure
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $saleReturnQueries = resolve(SaleReturnQueries::class);

        return fn ($query) => $query->select(
            'id',
            'sale_return_id',
            'original_sale_item_id',
            'quantity',
            'total_price_paid',
            'product_id',
            'total_discount_amount',
            'total_tax_amount'
        )
            ->whereHas('saleReturn', function ($query) use (
                $saleReturnQueries,
                $filterData,
                $counterUpdateQueries
            ): void {
                $query->select('id', 'offline_sale_return_id', 'counter_update_id')
                    ->when($filterData['date_range'], function ($query) use (
                        $filterData,
                        $saleReturnQueries
                    ): void {
                        $query->where(
                            $saleReturnQueries->filterByHappenedAtWithinDateRange($filterData['date_range'])
                        );
                    })
                    ->when($filterData['location_ids'], function ($query) use (
                        $filterData,
                        $counterUpdateQueries
                    ): void {
                        $query->whereHas(
                            'counterUpdate',
                            $counterUpdateQueries->filterByCounterStores($filterData['location_ids'])
                        );
                    });
            });
    }

    public function getByIdWithRelation(int $saleReturnItemId): ?SaleReturnItem
    {
        $saleItemQueries = resolve(SaleItemQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);

        return SaleReturnItem::query()
            ->select('id', 'original_sale_item_id')
            ->with([
                'saleItem:' . $saleItemQueries->getBasicColumnNamesForSaleExchanges(),
                'saleItem.promoters:' . $promoterQueries->getBasicColumnNames(),
            ])
            ->find($saleReturnItemId);
    }

    public function getTotalQuantitiesBy(string $previousDate, string $currentDate, int $employeeId): int
    {
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $saleReturnItems = SaleReturnItem::select('id', 'sale_return_id', 'quantity')
            ->whereHas('saleReturn', function ($query) use (
                $saleReturnQueries,
                $previousDate,
                $currentDate,
                $employeeId
            ): void {
                $query->select('id', 'member_id')
                    ->whereHas('member', function ($query) use ($employeeId): void {
                        $query->where('employee_id', $employeeId);
                    })
                    ->where($saleReturnQueries->filterByHappenedAtWithinDateRange([$previousDate, $currentDate]));
            })
            ->get();

        return (int) $saleReturnItems->sum('quantity');
    }

    public function getPromoterWiseSalesReturnItems(array $filteredData, int $promoterId): Collection
    {
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);

        return SaleReturnItem::query()
            ->select(
                'sale_return_items.id',
                'sale_return_items.sale_return_id',
                'sale_return_items.quantity',
                'sale_return_items.original_sale_item_id',
                'sale_return_items.total_price_paid',
                'sale_returns.offline_sale_return_id as receipt_id'
            )
            ->selectRaw('DATE(sale_returns.happened_at) as date')
            ->whereHas('saleReturn', function ($query) use ($filteredData, $counterUpdateQueries): void {
                $query->select('id')->where(
                    'happened_at',
                    '>=',
                    CommonFunctions::addStartTime($filteredData['start_date'])
                )
                    ->where('happened_at', '<=', CommonFunctions::addEndTime($filteredData['end_date']))
                    ->whereHas(
                        'counterUpdate',
                        $counterUpdateQueries->filterByStoreId((int) $filteredData['location_id'])
                    );
            })
            ->join('sale_returns', 'sale_returns.id', 'sale_return_items.sale_return_id')
            ->whereHas('saleItem.promoters', function ($query) use ($promoterId): void {
                $query->select('id')->where('id', $promoterId);
            })->get();
    }

    public function getSaleReturnItemWithProductAndPromoters(int $itemId, int $promoterId): SaleReturnItem
    {
        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $departmentQueries = resolve(DepartmentQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        return SaleReturnItem::query()
            ->with([
                'product:' . $productQueries->getBasicColumns(),
                'product.brand:' . $brandQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
                'product.department:' . $departmentQueries->getBasicColumnNames(),
                'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                'product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'product.masterProduct.department:' . $departmentQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                'saleItem:id',
                'saleItem.promoters' => function ($query) use ($promoterId, $employeeQueries): void {
                    $query->select('id', 'employee_id', 'code')
                        ->with('employee:' . $employeeQueries->getBasicColumnNames())
                        ->whereNot('id', $promoterId);
                },
            ])
            ->findOrFail($itemId);
    }

    public function updateProductId(int $companyId, int $oldProductId, int $newProductId): void
    {
        $saleReturnQueries = resolve(SaleReturnQueries::class);

        $saleReturnItems = SaleReturnItem::query()
            ->select('id', 'sale_return_id', 'product_id')
            ->whereHas('saleReturn', $saleReturnQueries->filterByCompanyId($companyId))
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($saleReturnItems as $saleReturnItem) {
            $saleReturnItem->product_id = $newProductId;
            $saleReturnItem->save();
        }
    }

    public function getCachedTodaySaleReturnsForDashboard(
        int $companyId,
        ?int $locationId,
        ?int $brandId,
        string $startDate,
        string $endDate,
        bool $refresh = false
    ): SaleReturnItem {
        $cacheKey = null !== $locationId ? 'cache-today-sale-returns-dashboard-' . $locationId . $brandId . $startDate . $endDate : 'cache-today-sale-returns-dashboard-' . $startDate . $endDate . $brandId;

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            900,
            fn (): SaleReturnItem => SaleReturnItem::query()
                    ->select(
                        DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                        DB::raw('SUM(sale_return_items.quantity) as return_units'),
                    )
                    ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
                    ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
                    ->join('locations', 'counters.location_id', '=', 'locations.id')
                    ->join('products', 'sale_return_items.product_id', '=', 'products.id')
                    ->where('locations.company_id', $companyId)
                    ->when((int) $locationId > 0, function ($query) use ($locationId): void {
                        $query->where('counters.location_id', $locationId);
                    })
                    ->when((int) $brandId > 0, function ($query) use ($brandId): void {
                        $query->where('products.brand_id', $brandId);
                    })
                    ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($startDate))
                    ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($endDate))
                    ->firstOrFail()
        );
    }

    public function getSaleReturnItemForTheStoreManagerApplicationDashboard(
        int $locationId,
        array $date
    ): SaleReturnItem {
        return SaleReturnItem::query()
            ->select(
                DB::raw('SUM(sale_return_items.total_price_paid) as total_sales_amount'),
                DB::raw('SUM(sale_return_items.quantity) as return_units'),
                DB::raw('COUNT(DISTINCT sale_returns.id) as total_sales'),
            )
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->where('counters.location_id', $locationId)
            ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($date[0]))
            ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($date[1]))
            ->firstOrFail();
    }

    public function getSelectIdColumn(): Closure
    {
        return fn ($query) => $query->select('id');
    }

    public function getOfflineSaleReturnWithRelation(): Closure
    {
        $saleReturnQueries = resolve(SaleReturnQueries::class);

        return fn ($query) => $query->select('id', 'sale_return_id')
            ->with(['saleReturn:' . $saleReturnQueries->getOfflineSaleReturnId()]);
    }

    public function getSaleReturnAndProductRelationColumns(): Closure
    {
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [
            'saleReturn:' . $saleReturnQueries->getOfflineSaleReturnId(),
            'product:' . $productQueries->getBasicColumns(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.brand:' . $brandQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return fn ($query) => $query->select('id', 'sale_return_id', 'product_id')
                ->with($relations);
    }

    public function getSaleReturnProductAndCounterRelationColumns(): Closure
    {
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $counterUpdateQueries = resolve(CounterUpdateQueries::class);
        $counterQueries = resolve(CounterQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);

        $relations = [
            'saleReturn:' . $saleReturnQueries->getOfflineAndCounterUpdateId(),
            'saleReturn.counterUpdate:' . $counterUpdateQueries->getCounterIdCashierIdColumnNames(),
            'saleReturn.counterUpdate.counter:' . $counterQueries->getCounterBasicColumnNames(),
            'saleReturn.counterUpdate.counter.location:' . $locationQueries->getBasicColumnNames(),
            'product:' . $productQueries->getBasicColumns(),
        ];

        if (config('app.product_variant')) {
            $relations = array_merge($relations, [
                'product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
            ]);
        } else {
            $relations = array_merge($relations, [
                'product.brand:' . $brandQueries->getBasicColumnNames(),
                'product.color:' . $colorQueries->getBasicColumnNames(),
                'product.size:' . $sizeQueries->getBasicColumnNames(),
            ]);
        }

        return fn ($query) => $query->select('id', 'sale_return_id', 'product_id', 'quantity')
                ->with($relations);
    }

    public function getSaleReturnProductAndPromoterRelationColumns(): Closure
    {
        $saleReturnQueries = resolve(SaleReturnQueries::class);
        $productQueries = resolve(ProductQueries::class);
        $brandQueries = resolve(BrandQueries::class);
        $colorQueries = resolve(ColorQueries::class);
        $sizeQueries = resolve(SizeQueries::class);
        $promoterQueries = resolve(PromoterQueries::class);
        $employeeQueries = resolve(EmployeeQueries::class);
        $productVariantValueQueries = resolve(ProductVariantValueQueries::class);
        $attributeQueries = resolve(AttributeQueries::class);
        $masterProductQueries = resolve(MasterProductQueries::class);

        return fn ($query) => $query->select('id', 'sale_return_id', 'product_id', 'quantity')
                ->with([
                    'saleReturn:' . $saleReturnQueries->getOfflineSaleReturnId(),
                    'product:' . $productQueries->getBasicColumns(),
                    'product.brand:' . $brandQueries->getBasicColumnNames(),
                    'product.color:' . $colorQueries->getBasicColumnNames(),
                    'product.size:' . $sizeQueries->getBasicColumnNames(),
                    'saleItem:id',
                    'saleItem.promoters:' . $promoterQueries->getBasicColumnNames(),
                    'saleItem.promoters.employee:' . $employeeQueries->getBasicColumnNames(),
                    'product.masterProduct:' . $masterProductQueries->getBasicColumnNames(),
                    'product.masterProduct.brand:' . $brandQueries->getBasicColumnNames(),
                    'product.productVariantValues:' . $productVariantValueQueries->getBasicColumnNames(),
                    'product.productVariantValues.attribute:' . $attributeQueries->getBasicColumnNames(),
                ]);
    }

    public function getIdSaleReturnIdAndQuantityWithSaleReturnRelation(): Closure
    {
        $saleReturnQueries = resolve(SaleReturnQueries::class);

        return fn ($query) => $query->select('id', 'sale_return_id', 'quantity')
            ->with(['saleReturn:' . $saleReturnQueries->getOfflineSaleReturnId()]);
    }

    public function getSelectIdANdOfflineIdColumn(): Closure
    {
        $saleReturnQueries = resolve(SaleReturnQueries::class);

        return fn ($query) => $query->select('id', 'sale_return_id')
            ->with(['saleReturn:' . $saleReturnQueries->getOfflineSaleReturnId()]);
    }

    public function getYesterdaySaleReturnWithSaleReturnItems(string $date): Collection
    {
        return SaleReturnItem::query()
            ->select('sale_return_items.id', 'product_id', 'counters.location_id')
            ->leftJoin('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->leftJoin('counter_updates', 'counter_updates.id', '=', 'sale_returns.counter_update_id')
            ->leftJoin('counters', 'counters.id', '=', 'counter_updates.counter_id')
            ->when($date, function ($query) use ($date): void {
                $query->where('sale_returns.happened_at', '>=', CommonFunctions::addStartTime($date))
                    ->where('sale_returns.happened_at', '<=', CommonFunctions::addEndTime($date));
            })
            ->get();
    }

    public function getSalesReturnForDashboardByDate(
        int $companyId,
        string $startDate,
        string $endDate,
    ): Collection {
        return SaleReturnItem::query()
            ->select(
                'locations.company_id',
                DB::raw('DATE(counter_updates.opened_by_pos_at) as opened_date'),
                DB::raw('SUM(sale_return_items.total_price_paid) as return_amount'),
                DB::raw('SUM(sale_return_items.quantity) as return_units'),
                'companies.uuid as company_uuid'
            )
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
            ->join('locations', 'counters.location_id', '=', 'locations.id')
            ->join('companies', 'locations.company_id', '=', 'companies.id')
            ->where('locations.company_id', $companyId)
            ->where('counter_updates.opened_by_pos_at', '>=', CommonFunctions::addStartTime($startDate))
            ->where('counter_updates.opened_by_pos_at', '<=', CommonFunctions::addEndTime($endDate))
            ->groupBy('locations.company_id', 'opened_date')
            ->get();
    }

    public function getRegularProductSalesReturnsSummary(int $companyId, array $dates = []): Builder
    {
        $cacheKey = 'regular_product_sales_returns_summary_' . $companyId;

        $cacheDuration = now()->addMinutes(10);

        return Cache::remember($cacheKey, $cacheDuration, fn () => DB::table('sale_return_items')
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('products', 'sale_return_items.product_id', '=', 'products.id')
            ->join('counter_updates', 'sale_returns.counter_update_id', '=', 'counter_updates.id')
            ->join('counters', 'counter_updates.counter_id', '=', 'counters.id')
        ->join('locations', 'counters.location_id', '=', 'locations.id')
        ->where('locations.company_id', $companyId)
        ->where('products.type_id', ProductTypes::REGULAR_PRODUCT->value)
        ->when([] !== $dates && isset($dates['start_date']) && isset($dates['end_date']), function ($query) use (
            $dates
        ): void {
            $query->where(DB::raw('DATE(counter_updates.closed_by_pos_at)'), '>=', $dates['start_date'])
                ->where(DB::raw('DATE(counter_updates.closed_by_pos_at)'), '<=', $dates['end_date']);
        })
        ->groupBy([DB::raw('DATE(sale_returns.happened_at)'), 'locations.id', 'products.id'])
        ->select([
            DB::raw('DATE(sale_returns.happened_at) as date'),
            'locations.id as location_id',
            'products.id as product_id',
            DB::raw('-SUM(sale_return_items.quantity) as quantity'),
            DB::raw('-SUM(sale_return_items.total_price_paid) as amount'),
        ]));
    }
}
