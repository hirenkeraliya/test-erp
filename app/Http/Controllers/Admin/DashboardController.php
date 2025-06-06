<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\CommonFunctions;
use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Color\Resources\DashboardStockOverviewTopSellingColorResource;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\Common\Services\DashboardService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\RoundOffConfiguration;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Dashboard\Exports\ProductStoresSalesDetailsExport;
use App\Domains\Dashboard\Exports\RevenueStoresSalesDetailsExport;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Inventory\Enums\Types;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Location\Resources\DashboardStockOverviewTopSellingLocationResource;
use App\Domains\Member\MemberQueries;
use App\Domains\Member\Resources\DashboardStockOverviewTopSellingMemberResource;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\Enums\SellingTypes;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\DashboardStockOverviewTopSellingProductResource;
use App\Domains\Product\Resources\DashboardStockOverviewWorstSellingProductResource;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\Promoter\Resources\DashboardStockOverviewTopSellingPromoterResource;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses as EnumsStatuses;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\Region\RegionQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Domains\SaleSeason\SaleSeasonQueries;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughFilterTypes;
use App\Domains\SellThroughAggregate\Enums\SellThroughIncludeTypes;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StoreWiseDailyTotal\StoreWiseDailyTotalQueries;
use App\Domains\Style\StyleQueries;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SaleSeason;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::now()->format('Y-m-d');

        if (! $validator->fails() && $request->input('date')) {
            $date = $request->input('date');
        }

        $companyId = session('admin_company_id');
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $locations->prepend([
            'id' => 0,
            'name' => 'All Locations',
            'code' => '',
        ]);

        $brandQueries = resolve(BrandQueries::class);
        $brands = $brandQueries->getBrands($companyId);

        $brands->prepend([
            'id' => 0,
            'name' => 'All Brands',
        ]);

        $brandId = (int) $request->input('brand_id');

        $locationId = (int) $request->input('location_id');

        return Inertia::render('OperationalView', [
            'locations' => $locations,
            'locationId' => $locationId,
            'brandId' => $brandId,
            'brands' => $brands,
            'date' => $date,
        ]);
    }

    public function getOperationalSalesCount(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::now()->format('Y-m-d');

        if (! $validator->fails() && $request->input('date')) {
            $date = $request->input('date');
        }

        $companyId = session('admin_company_id');

        $locationId = (int) $request->input('location_id');
        $brandId = (int) $request->input('brand_id');

        $refresh = (bool) $request->input('refresh');

        if ($refresh) {
            Cache::forget('index-refresh-date-time');
        }

        $dashboardService = resolve(DashboardService::class);
        $todaySalesDetails = $dashboardService->getTodaySalesDetails(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );
        $thisMonthSalesDetails = $dashboardService->getThisMonthSalesDetails(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        $thisYearSalesDetails = $dashboardService->getThisYearSalesDetails(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        return [
            'salesCount' => [
                'todayTotalSaleAmount' => (float) $todaySalesDetails['totalAmount'],
                'todayTotalSalePercentage' => (float) $todaySalesDetails['todayTotalSalePercentage'],
                'previousYearTodaySaleAmount' => (float) $todaySalesDetails['previousYearTodaySaleAmount'],
                'previousYearTodaySalePercentage' => (float) $todaySalesDetails['previousYearTodaySalePercentage'],
                'mtdTotalSaleAmount' => (float) $thisMonthSalesDetails['totalAmount'],
                'mtdTotalSalePercentage' => (float) $thisMonthSalesDetails['mtdTotalSalePercentage'],
                'previousYearMonthSaleAmount' => (float) $thisMonthSalesDetails['previousYearMonthSaleAmount'],
                'previousYearMonthSalePercentage' => (float) $thisMonthSalesDetails['previousYearMonthSalePercentage'],
                'ytdTotalSaleAmount' => (float) $thisYearSalesDetails['totalAmount'],
                'ytdTotalSalePercentage' => (float) $thisYearSalesDetails['ytdTotalSalePercentage'],
                'previousYearTillTodaySaleAmount' => (float) $thisYearSalesDetails['previousYearTillTodaySaleAmount'],
                'previousYearTillTodaySalePercentage' => (float) $thisYearSalesDetails['previousYearTillTodaySalePercentage'],
            ],
            'lastUpdate' => Cache::remember(
                'index-refresh-date-time',
                900,
                fn (): string => now()->format('d-m-y h:i:s A')
            ),
        ];
    }

    public function getOperationalTodaySales(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::now()->format('Y-m-d');

        if (! $validator->fails() && $request->input('date')) {
            $date = $request->input('date');
        }

        $companyId = session('admin_company_id');

        $locationId = (int) $request->input('location_id');
        $brandId = (int) $request->input('brand_id');

        $refresh = (bool) $request->input('refresh');

        $dashboardService = resolve(DashboardService::class);
        $todaySalesDetails = $dashboardService->getTodaySalesDetails(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        return [
            'today' => [
                'totalSale' => $todaySalesDetails['totalSalesCount'],
                'totalUnitsSold' => $todaySalesDetails['totalUnitsSold'],
                'upt' => $todaySalesDetails['todayUpt'],
                'atv' => $todaySalesDetails['todayAtv'],
            ],
        ];
    }

    public function getOperationalThisMonthSales(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::now()->format('Y-m-d');

        if (! $validator->fails() && $request->input('date')) {
            $date = $request->input('date');
        }

        $companyId = session('admin_company_id');
        $locationId = (int) $request->input('location_id');
        $brandId = (int) $request->input('brand_id');

        $refresh = (bool) $request->input('refresh');

        $dashboardService = resolve(DashboardService::class);
        $thisMonthSalesDetails = $dashboardService->getThisMonthSalesDetails(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        return [
            'thisMonth' => [
                'totalSale' => $thisMonthSalesDetails['totalSalesCount'],
                'totalUnitsSold' => $thisMonthSalesDetails['totalUnitsSold'],
                'upt' => $thisMonthSalesDetails['mtdUpt'],
                'atv' => $thisMonthSalesDetails['mtdAtv'],
            ],
        ];
    }

    public function getOperationalThisYearSales(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::now()->format('Y-m-d');

        if (! $validator->fails() && $request->input('date')) {
            $date = $request->input('date');
        }

        $refresh = (bool) $request->input('refresh');

        $companyId = session('admin_company_id');
        $locationId = (int) $request->input('location_id');
        $brandId = (int) $request->input('brand_id');

        $dashboardService = resolve(DashboardService::class);
        $thisYearSalesDetails = $dashboardService->getThisYearSalesDetails(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        return [
            'thisYear' => [
                'totalSale' => $thisYearSalesDetails['totalSalesCount'],
                'totalUnitsSold' => $thisYearSalesDetails['totalUnitsSold'],
                'upt' => $thisYearSalesDetails['ytdUpt'],
                'atv' => $thisYearSalesDetails['ytdAtv'],
            ],
        ];
    }

    public function getOperationalRevenueChartData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::now()->format('Y-m-d');

        if (! $validator->fails() && $request->input('date')) {
            $date = $request->input('date');
        }

        $refresh = (bool) $request->input('refresh');

        $companyId = session('admin_company_id');
        $locationId = (int) $request->input('location_id');
        $brandId = (int) $request->input('brand_id');

        $dashboardService = resolve(DashboardService::class);
        $monthWiseSalesDetails = $dashboardService->getMonthWiseSalesDetails(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        if (! $date instanceof Carbon) {
            /** @var Carbon $date */
            $date = Carbon::createFromFormat('Y-m-d', $date);
        }

        $lastYearMonthWiseSalesDetails = $dashboardService->getMonthWiseSalesDetails(
            $companyId,
            $locationId,
            $brandId,
            $date->subYear()->endOfYear()->format('Y-m-d'),
            $refresh
        );

        $revenueChartData = $dashboardService->getRevenueChartDataWithLastYear(
            $monthWiseSalesDetails,
            $lastYearMonthWiseSalesDetails
        );

        return [
            'revenueChartData' => $revenueChartData,
        ];
    }

    public function getOperationalAtvChartData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::now()->format('Y-m-d');

        if (! $validator->fails() && $request->input('date')) {
            $date = $request->input('date');
        }

        $refresh = (bool) $request->input('refresh');

        $companyId = session('admin_company_id');
        $locationId = (int) $request->input('location_id');
        $brandId = (int) $request->input('brand_id');

        $dashboardService = resolve(DashboardService::class);
        $monthWiseSalesDetails = $dashboardService->getMonthWiseSalesDetails(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );
        $atvChartData = $dashboardService->getATVChartData($monthWiseSalesDetails);

        return [
            'atvChartData' => $atvChartData,
        ];
    }

    public function getOperationalUptChartData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::now()->format('Y-m-d');

        if (! $validator->fails() && $request->input('date')) {
            $date = $request->input('date');
        }

        $refresh = (bool) $request->input('refresh');

        $companyId = session('admin_company_id');
        $locationId = (int) $request->input('location_id');
        $brandId = (int) $request->input('brand_id');

        $dashboardService = resolve(DashboardService::class);
        $monthWiseSalesDetails = $dashboardService->getMonthWiseSalesDetails(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );
        $uptChartData = $dashboardService->getUPTChartData($monthWiseSalesDetails);

        return [
            'uptChartData' => $uptChartData,
        ];
    }

    public function getOperationalTopPromoters(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::now()->format('Y-m-d');

        if (! $validator->fails() && $request->input('date')) {
            $date = $request->input('date');
        }

        $refresh = (bool) $request->input('refresh');

        $companyId = session('admin_company_id');
        $locationId = (int) $request->input('location_id');
        $brandId = (int) $request->input('brand_id');

        $dashboardService = resolve(DashboardService::class);

        return [
            'topPromoters' => $dashboardService->getTopPromoters($companyId, $locationId, $brandId, $date, $refresh),
        ];
    }

    public function getOperationalThisYearTopPromoters(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::now()->format('Y-m-d');

        if (! $validator->fails() && $request->input('date')) {
            $date = $request->input('date');
        }

        $refresh = (bool) $request->input('refresh');

        $companyId = session('admin_company_id');
        $locationId = (int) $request->input('location_id');
        $brandId = (int) $request->input('brand_id');

        $dashboardService = resolve(DashboardService::class);

        return [
            'thisYearTopPromoters' => $dashboardService->getYearlyTopPromoters(
                $companyId,
                $locationId,
                $brandId,
                $date,
                $refresh
            ),
        ];
    }

    public function getSalesDataForChart(int $brandId, array $date, bool $refresh): array
    {
        $locationQueries = resolve(LocationQueries::class);
        $totalSalesByLocations = $locationQueries->getCachedStoresSalesForChart(
            session('admin_company_id'),
            null,
            $brandId,
            $date,
            $refresh
        );

        $totalSalesByLocations = $totalSalesByLocations->map(function ($totalSalesByLocation): array {
            $averageTransactionValue = $totalSalesByLocation->sales_count > 0 ? CommonFunctions::numberFormat(
                $totalSalesByLocation->total_sales / $totalSalesByLocation->sales_count
            ) : 0;

            return [
                'id' => $totalSalesByLocation->id,
                'name' => $totalSalesByLocation->name,
                'code' => $totalSalesByLocation->code,
                'sales_count' => $totalSalesByLocation->sales_count,
                'total_sales' => $totalSalesByLocation->total_sales + RoundOffConfiguration::roundOffCalculationFor(
                    (string) $totalSalesByLocation->total_sales
                ),
                'total_units_sold' => (float) $totalSalesByLocation->total_units_sold,
                'unit_per_transaction' => $totalSalesByLocation->sales_count > 0 ? CommonFunctions::numberFormat(
                    $totalSalesByLocation->total_units_sold / $totalSalesByLocation->sales_count
                ) : 0,
                'average_transaction_value' => $averageTransactionValue + RoundOffConfiguration::roundOffCalculationFor(
                    (string) $averageTransactionValue
                ),
            ];
        });

        $averageTransactionValue = $totalSalesByLocations->sum('sales_count') > 0 ? CommonFunctions::numberFormat(
            $totalSalesByLocations->sum('total_sales') / $totalSalesByLocations->sum('sales_count')
        ) : 0;

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId(session('admin_company_id'));

        $salesTotalData = [
            'name' => 'Grand Total',
            'total_sales' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                CommonFunctions::currencyFormat($totalSalesByLocations->sum('total_sales'))
            ),
            'sales_count' => $totalSalesByLocations->sum('sales_count'),
            'total_units_sold' => CommonFunctions::truncateDecimal($totalSalesByLocations->sum('total_units_sold')),
            'unit_per_transaction' => $totalSalesByLocations->sum('sales_count') ? CommonFunctions::truncateDecimal(
                $totalSalesByLocations->sum('total_units_sold') / $totalSalesByLocations->sum('sales_count')
            ) : 0,
            'average_transaction_value' => CommonFunctions::currencySymbolDisplayWithAmount(
                $currency->getSymbol(),
                CommonFunctions::currencyFormat(
                    $averageTransactionValue + RoundOffConfiguration::roundOffCalculationFor(
                        (string) $averageTransactionValue
                    )
                )
            ),
        ];

        return [$totalSalesByLocations, $salesTotalData];
    }

    public function revenueView(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'date' => ['sometimes', 'array'],
        ]);

        $date = [Carbon::now()->format('Y-m-d'), Carbon::now()->format('Y-m-d')];

        if (! $validator->fails() && $request->input('date')) {
            $date = $request->input('date');
        }

        $refresh = (bool) $request->input('refresh');

        if ($refresh) {
            Cache::forget('index-refresh-date-time-for-revenue');
        }

        $brandQueries = resolve(BrandQueries::class);
        $companyId = session('admin_company_id');

        $brands = $brandQueries->getBrands($companyId);

        $brands->prepend([
            'id' => 0,
            'name' => 'All Brands',
        ]);

        $brandId = (int) $request->input('brand_id');

        [$totalSalesByLocations, $salesTotalData] = $this->getSalesDataForChart($brandId, $date, $refresh);

        $brandQueries = resolve(BrandQueries::class);
        $totalSalesByBrands = $brandQueries->getCachedBrandsSalesForChart(
            session('admin_company_id'),
            null,
            $brandId,
            $date,
            $refresh
        );

        $categoryQueries = resolve(CategoryQueries::class);
        $totalSalesByCategories = $categoryQueries->getCachedCategoriesSalesForChart(
            session('admin_company_id'),
            null,
            $brandId,
            $date,
            $refresh
        );

        $styleQueries = resolve(StyleQueries::class);
        $totalSalesByStyles = $styleQueries->getCachedStylesSalesForChart(
            session('admin_company_id'),
            null,
            $brandId,
            $date,
            $refresh
        );

        $departmentQueries = resolve(DepartmentQueries::class);
        $totalSalesByDepartments = $departmentQueries->getCachedDepartmentSaleForChart(
            session('admin_company_id'),
            null,
            $brandId,
            $date,
            $refresh
        );

        $dashboardService = resolve(DashboardService::class);
        $totalSalesByLocationChartData = $dashboardService->getOnlyFourSales(
            $totalSalesByLocations->pluck('name')->toArray(),
            $totalSalesByLocations->pluck('total_sales')->toArray()
        );

        $totalSalesByBrandChartData = $dashboardService->getOnlyFourSales(
            $totalSalesByBrands->pluck('name')->toArray(),
            $totalSalesByBrands->pluck('total_sales')->toArray()
        );

        $totalSalesByCategoryChartData = $dashboardService->getOnlyFourSales(
            $totalSalesByCategories->pluck('name')->toArray(),
            $totalSalesByCategories->pluck('total_sales')->toArray()
        );

        $totalSalesByStyleChartData = $dashboardService->getOnlyFourSales(
            $totalSalesByStyles->pluck('name')->toArray(),
            $totalSalesByStyles->pluck('total_sales')->toArray()
        );

        $totalSalesByDepartmentChartData = $dashboardService->getOnlyFourSales(
            $totalSalesByDepartments->pluck('name')->toArray(),
            $totalSalesByDepartments->pluck('total_sales')->toArray()
        );

        return Inertia::render('RevenueDashboard', [
            'totalSalesByLocation' => $totalSalesByLocationChartData,
            'totalSalesByBrand' => $totalSalesByBrandChartData,
            'totalSalesByCategory' => $totalSalesByCategoryChartData,
            'totalSalesByStyle' => $totalSalesByStyleChartData,
            'totalSalesByDepartment' => $totalSalesByDepartmentChartData,
            'totalSales' => $totalSalesByLocations->sum('total_sales'),
            'totalUnitsSold' => $totalSalesByLocations->sum('total_units_sold'),
            'salesData' => $totalSalesByLocations,
            'salesTotalData' => $salesTotalData,
            'date' => $date,
            'brands' => $brands,
            'brandId' => $brandId,
            'lastUpdate' => Cache::remember(
                'index-refresh-date-time-for-revenue',
                900,
                fn (): string => now()->format('d-m-y h:i:s A')
            ),
        ]);
    }

    public function storeRevenueView(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        $date = Carbon::now()->format('Y-m-d');

        if (! $validator->fails() && $request->input('date')) {
            $date = $request->input('date');
        }

        $refresh = (bool) $request->input('refresh');

        if ($refresh) {
            Cache::forget('index-refresh-date-time-for-location-revenue');
        }

        $locationQueries = resolve(LocationQueries::class);
        $brandQueries = resolve(BrandQueries::class);

        $companyId = session('admin_company_id');
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);
        $brands = $brandQueries->getBrands($companyId);

        $brands->prepend([
            'id' => 0,
            'name' => 'All Brands',
        ]);

        $locations->prepend([
            'id' => 0,
            'name' => 'All Locations',
            'code' => '',
        ]);

        $locationId = (int) $request->input('location_id');

        $brandId = (int) $request->input('brand_id');

        $totalSalesByLocations = $locationQueries->getCachedStoresSalesForChart(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        $dashboardService = resolve(DashboardService::class);

        [$totalSalesByBrands, $grandTotalSalesByBrand, $totalSalesByBrandChartData] = $dashboardService->getCachedBrandsSalesForChart(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        [$totalSalesByColors, $grandTotalSalesByColor, $totalSalesByColorChartData] = $dashboardService->getCachedColorsSalesForChart(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        [$totalSalesByCategories, $grandTotalSalesByCategory, $totalSalesByCategoryChartData] = $dashboardService->getCachedCategoriesSalesForChart(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        [$totalSalesByDepartments, $grandTotalSalesByDepartment, $totalSalesByDepartmentChartData] = $dashboardService->getCachedDepartmentSaleForChart(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh,
        );

        [$totalSalesByColorGroups, $grandTotalSalesByColorGroup, $totalSalesByColorGroupChartData] = $dashboardService->getCachedColorGroupSalesForChart(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        [$totalSalesBySizes, $grandTotalSalesBySize, $totalSalesBySizeChartData] = $dashboardService->getCachedSizeSalesForChart(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        [$totalSalesByStyles, $grandTotalSalesByStyle, $totalSalesByStyleChartData] = $dashboardService->getCachedStyleSalesForChart(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        [$todayHourlySales, $yesterdayHourlySales, $todayHourlyTotalSales, $yesterdayHourlyTotalSales, $hourlyChartLabel] = $dashboardService->getHourlyBasedData(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        $saleQueries = resolve(SaleQueries::class);
        $totalCreditSalePendingAmount = $saleQueries->totalCreditSalePendingAmount($companyId, $locationId);

        return Inertia::render('StoreRevenueView', [
            'brands' => $brands,
            'locations' => $locations,
            'locationId' => $locationId,
            'brandId' => $brandId,
            'totalCreditSalePendingAmount' => $totalCreditSalePendingAmount,
            'totalSalesByColor' => $totalSalesByColorChartData,
            'totalSalesByBrand' => $totalSalesByBrandChartData,
            'totalSalesByCategory' => $totalSalesByCategoryChartData,
            'totalSalesByDepartment' => $totalSalesByDepartmentChartData,
            'totalSalesByColorGroup' => $totalSalesByColorGroupChartData,
            'totalSalesBySize' => $totalSalesBySizeChartData,
            'totalSalesByStyle' => $totalSalesByStyleChartData,
            'todayHourlySales' => $todayHourlySales,
            'yesterdayHourlySales' => $yesterdayHourlySales,
            'todayHourlyTotalSales' => $todayHourlyTotalSales,
            'yesterdayHourlyTotalSales' => $yesterdayHourlyTotalSales,
            'hourlyChartLabel' => $hourlyChartLabel,
            'totalSales' => $totalSalesByLocations->sum('total_sales'),
            'totalUnitsSold' => $totalSalesByLocations->sum('total_units_sold'),
            'brandsData' => $totalSalesByBrands->toArray(),
            'brandFooterData' => $grandTotalSalesByBrand,
            'colorsData' => $totalSalesByColors->toArray(),
            'colorFooterData' => $grandTotalSalesByColor,
            'categoriesData' => $totalSalesByCategories->toArray(),
            'categoryFooterData' => $grandTotalSalesByCategory,
            'departmentsData' => $totalSalesByDepartments->toArray(),
            'departmentFooterData' => $grandTotalSalesByDepartment,
            'colorGroupsData' => $totalSalesByColorGroups->toArray(),
            'colorGroupFooterData' => $grandTotalSalesByColorGroup,
            'sizesData' => $totalSalesBySizes->toArray(),
            'stylesData' => $totalSalesByStyles->toArray(),
            'sizeFooterData' => $grandTotalSalesBySize,
            'styleFooterData' => $grandTotalSalesByStyle,
            'date' => $date,
            'lastUpdate' => Cache::remember(
                'index-refresh-date-time-for-location-revenue',
                900,
                fn (): string => now()->format('d-m-y h:i:s A')
            ),
            'storeRevenueDashboardTableFilterTypes' => StoreRevenueDashboardTableFilterTypes::getFormattedArrayForStaticUse(),
        ]);
    }

    public function businessView(): Response
    {
        $companyId = session('admin_company_id');
        $brandQueries = resolve(BrandQueries::class);
        $brands = $brandQueries->getBrands($companyId);

        $brands->prepend([
            'id' => 0,
            'name' => 'All Brands',
        ]);

        return Inertia::render('BusinessView', [
            'brands' => $brands,
        ]);
    }

    public function getBusinessViewData(Request $request): array
    {
        $companyId = session('admin_company_id');

        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);
        $companyQueries = resolve(CompanyQueries::class);

        $refresh = (bool) $request->input('refresh');

        if ($refresh) {
            Cache::forget('index-refresh-date-time-for-business');
        }

        $brandId = (int) $request->input('brand_id');

        $currentYearSaleData = $storeWiseDailyTotalQueries->getTotalSalesAmountByDate(
            'this-year-sales-for-business-dashboard',
            now()->startOfYear()->format('Y-m-d'),
            now()->format('Y-m-d'),
            $companyId,
            null,
            $brandId,
            $refresh
        );

        $dashboardService = resolve(DashboardService::class);
        $lastYearToDate = $dashboardService->getLastYearSaleData(
            $companyId,
            null,
            now()->subYearNoOverflow()->startOfYear()->format('Y-m-d'),
            now()->subYearNoOverflow()->format('Y-m-d'),
            $brandId,
            $refresh
        );

        $lastYearSaleData = $dashboardService->getLastYearSaleData(
            $companyId,
            null,
            now()->subYearNoOverflow()->startOfYear()->format('Y-m-d'),
            now()->subYearNoOverflow()->endOfYear()->format('Y-m-d'),
            $brandId,
            $refresh
        );

        $lastYearSaleDataWithoutBrandId = $dashboardService->getLastYearSaleData(
            $companyId,
            null,
            now()->subYearNoOverflow()->startOfYear()->format('Y-m-d'),
            now()->subYearNoOverflow()->endOfYear()->format('Y-m-d'),
            $brandId,
            $refresh
        );

        $yearlySalesDetails = $dashboardService->getYearlySalesDetails($companyId, $brandId, null, $refresh);

        $yearlyTargetPercentage = $companyQueries->getYearlyTarget(session('admin_company_id'));
        $lastYearTotalAmount = (float) $lastYearSaleDataWithoutBrandId['total_amount']; // @phpstan-ignore-line
        $yearlyTarget = $lastYearTotalAmount;
        if ($lastYearTotalAmount > 0) {
            $yearlyTarget = CommonFunctions::numberFormat(
                $lastYearTotalAmount + ($lastYearTotalAmount * $yearlyTargetPercentage / 100)
            );
        }

        $todaySalesDetails = $dashboardService->getTodaySalesDetails(
            $companyId,
            null,
            $brandId,
            now()->format('Y-m-d'),
            $refresh
        );

        $currentYearSaleAmount = (float) $currentYearSaleData['total_amount'];
        $currentYearSalePercentage = 0.00;
        if ($lastYearTotalAmount > 0) {
            $currentYearSalePercentage = CommonFunctions::numberFormat(
                ($currentYearSaleAmount - $lastYearTotalAmount) * 100 / $lastYearTotalAmount
            );
        }

        $lastYearToDatePercentage = 0.00;

        $lastYearToDateAmount = (float) $lastYearToDate['total_amount']; // @phpstan-ignore-line
        if ($lastYearToDateAmount > 0) {
            $lastYearToDatePercentage = CommonFunctions::numberFormat(
                ($currentYearSaleAmount - $lastYearToDateAmount) * 100 / $lastYearToDateAmount
            );
        }

        $yearlyTargetPercentage = 0.00;
        if ($yearlyTarget > 0) {
            $yearlyTargetPercentage = CommonFunctions::numberFormat(
                ($currentYearSaleAmount - $yearlyTarget) * 100 / $yearlyTarget
            );
        }

        $currentDate = Carbon::now();

        $startDate = $currentDate->startOfMonth()->format('Y-m-d');
        $endDate = $currentDate->endOfMonth()->format('Y-m-d');

        if ($request->has('monthRange')) {
            $date = $request->get('monthRange');
            /** @var Carbon $startOfMonthDate */
            $startOfMonthDate = Carbon::createFromFormat('Y-m-d', $date[1] . '-' . $date[0] . '-01');
            $startDate = $startOfMonthDate->format('Y-m-d');
            $endDate = $startOfMonthDate->endOfMonth()->format('Y-m-d');
        }

        $yearlyTarget += RoundOffConfiguration::roundOffCalculationFor((string) $yearlyTarget);

        return [
            'salesCount' => [
                'currentYearSaleData' => $currentYearSaleAmount,
                'currentYearSalePercentage' => $currentYearSalePercentage,
                'lastYearSaleData' => (float) $lastYearSaleData['total_amount'], // @phpstan-ignore-line
                'lastYearToDate' => $lastYearToDateAmount,
                'lastYearToDatePercentage' => $lastYearToDatePercentage,
                'yearlyTarget' => $yearlyTarget,
                'yearlyTargetPercentage' => $yearlyTargetPercentage,
                'todayTotalSaleData' => (float) $todaySalesDetails['totalAmount'],
                'todayTotalSalePercentage' => (float) $todaySalesDetails['todayTotalSalePercentage'],
                'todayDate' => now()->format('F d, Y (l)'),
            ],
            'yearlySalesData' => $yearlySalesDetails,
            'brandWiseData' => $dashboardService->getBrandWiseData($companyId, null, $brandId),
            'styleWiseData' => $dashboardService->getStyleWiseData([$startDate, $endDate], $companyId, null, $brandId),
            'lastUpdate' => Cache::remember(
                'index-refresh-date-time-for-business',
                900,
                fn (): string => now()->format('d-m-y h:i:s A')
            ),
        ];
    }

    public function getStyleChartData(Request $request): array
    {
        $currentDate = Carbon::now();

        $startFormatted = $currentDate->startOfMonth()->format('Y-m-d');
        $endFormatted = $currentDate->endOfMonth()->format('Y-m-d');

        $brandId = (int) $request->get('brand_id');

        if ($request->has('quarter')) {
            $year = (int) Carbon::now()->format('Y');
            $quarter = $request->get('quarter');

            /** @var Carbon $startDate */
            $startDate = Carbon::create($year, ($quarter - 1) * 3 + 1, 1);
            $endDate = $startDate->copy()->endOfQuarter();

            $startFormatted = $startDate->format('Y-m-d');
            $endFormatted = $endDate->format('Y-m-d');
        }

        $companyId = session('admin_company_id');

        if ($request->has('monthRange')) {
            $date = $request->get('monthRange');
            /** @var Carbon $startOfMonthDate */
            $startOfMonthDate = Carbon::createFromFormat('Y-m-d', $date[1] . '-' . $date[0] . '-01');
            $startFormatted = $startOfMonthDate->format('Y-m-d');
            $endFormatted = $startOfMonthDate->endOfMonth()->format('Y-m-d');
        }

        $dashboardService = resolve(DashboardService::class);

        return [
            'styleWiseData' => $dashboardService->getStyleWiseData(
                [$startFormatted, $endFormatted],
                $companyId,
                null,
                $brandId
            ),
        ];
    }

    public function getLiveTopTenStores(int $brandId): array
    {
        $regionQueries = resolve(RegionQueries::class);
        $companyId = session('admin_company_id');

        $liveTopTenLocations = $regionQueries->cacheRegionSales($companyId, $brandId);

        $liveTopTenLocations = $liveTopTenLocations->map(function ($liveTopTenStore): array {
            $totalSales = $liveTopTenStore->total_sales;
            $totalSales += RoundOffConfiguration::roundOffCalculationFor((string) $totalSales);

            return [
                'region_id' => $liveTopTenStore->region_id,
                'name' => $liveTopTenStore->name,
                'total_sales' => $totalSales,
            ];
        });

        return [
            'liveTopTenLocations' => $liveTopTenLocations,
        ];
    }

    public function getStoreSalesByRegion(int $regionId, int $brandId): array
    {
        $companyId = session('admin_company_id');
        $locationQueries = resolve(LocationQueries::class);
        $locationSales = $locationQueries->cacheSaleByRegionId($regionId, $companyId, $brandId);
        $locationSales = $locationSales->map(function ($locationSale): array {
            $totalSales = $locationSale->total_sales;
            $totalSales += RoundOffConfiguration::roundOffCalculationFor((string) $totalSales);

            return [
                'id' => $locationSale->id,
                'name' => $locationSale->name,
                'total_sales' => $totalSales,
            ];
        });

        return [
            'location_sales' => $locationSales,
        ];
    }

    public function stockOverview(Request $request): Response
    {
        $companyId = session('admin_company_id');
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $locations->prepend([
            'id' => 0,
            'name' => 'All Locations',
            'code' => '',
        ]);

        $brandQueries = resolve(BrandQueries::class);
        $brands = $brandQueries->getBrands($companyId);

        $brands->prepend([
            'id' => 0,
            'name' => 'All Brands',
        ]);

        $brandId = (int) $request->input('brand_id');
        $locationId = (int) $request->input('location_id');

        return Inertia::render('StockOverview', [
            'locations' => $locations,
            'brands' => $brands,
            'brandId' => $brandId,
            'locationsId' => $locationId,
            'stockTypes' => [
                'no_stock' => Types::NO_STOCK->value,
                'low_stock_company' => Types::LOW_STOCK_COMPANY->value,
                'low_stock_location' => Types::LOW_STOCK_LOCATION->value,
                'low_stock_product' => Types::LOW_STOCK_PRODUCT->value,
                'negative_stock' => Types::NEGATIVE_STOCK->value,
            ],
            'transferTypes' => [
                'request_order' => StockTransferTypes::REQUEST_ORDER->value,
                'transfer_order' => StockTransferTypes::TRANSFER_ORDER->value,
            ],
            'orderTypes' => OrderTypes::getFormattedArrayForStaticUse(),
            'fulfillmentStatuses' => FulfillmentStatuses::generateStaticCasesArray(),
            'purchaseOrderStatuses' => EnumsStatuses::generateStaticCasesArray(),
            'stockTransferStatuses' => StatusTypes::generateStaticCasesArray(),
            'activeStatus' => ProductStatuses::ACTIVE->value,
            'sellingType' => SellingTypes::SELLING->value,
        ]);
    }

    public function getStockOverview(int $locationId): array
    {
        return [
            'locationId' => $locationId,
        ];
    }

    public function getLowStockOverview(Request $request): array
    {
        $locationId = (int) $request->get('location_id');
        $refresh = (bool) $request->get('refresh');

        $filterData = [
            'location_id' => $locationId,
        ];

        $inventoryQueries = resolve(InventoryQueries::class);

        $lowStockCompanyCount = $inventoryQueries->getCompanyLowStockItems(
            $filterData,
            session('admin_company_id'),
            $refresh
        );
        $lowStockLocationCount = $inventoryQueries->getLocationLowStockItems(
            $filterData,
            session('admin_company_id'),
            $refresh
        );
        $lowStockProductCount = $inventoryQueries->getProductLowStockItems(
            $filterData,
            session('admin_company_id'),
            $refresh
        );

        return [
            'lowStockCompanyCount' => $lowStockCompanyCount,
            'lowStockLocationCount' => $lowStockLocationCount,
            'lowStockProductCount' => $lowStockProductCount,
        ];
    }

    public function getNoStockStockOverview(Request $request): array
    {
        $locationId = (int) $request->get('location_id');
        $refresh = (bool) $request->get('refresh');

        $filterData = [
            'location_id' => $locationId,
        ];

        $inventoryQueries = resolve(InventoryQueries::class);
        $noStockItemCount = $inventoryQueries->getNoStockItems($filterData, session('admin_company_id'), $refresh);

        return [
            'noStockItemCount' => $noStockItemCount,
        ];
    }

    public function getNegativeStockStockOverview(Request $request): array
    {
        $locationId = (int) $request->get('location_id');
        $refresh = (bool) $request->get('refresh');

        $filterData = [
            'location_id' => $locationId,
        ];

        $inventoryQueries = resolve(InventoryQueries::class);
        $negativeStockItemCount = $inventoryQueries->getNegativeStockItems(
            $filterData,
            session('admin_company_id'),
            $refresh
        );

        return [
            'negativeStockItemCount' => $negativeStockItemCount,
        ];
    }

    public function getTransferOrder(int $locationId): array
    {
        $dashboardService = resolve(DashboardService::class);
        $transferOrders = $dashboardService->getTransferOrder($locationId, session('admin_company_id'));

        return [
            'transferOrders' => $transferOrders,
        ];
    }

    public function getPurchaseRequest(int $locationId): array
    {
        $dashboardService = resolve(DashboardService::class);
        $purchaseRequests = $dashboardService->getPurchaseRequest($locationId, session('admin_company_id'));

        return [
            'purchaseRequests' => $purchaseRequests,
        ];
    }

    public function getTransferRequest(int $locationId): array
    {
        $dashboardService = resolve(DashboardService::class);
        $transferRequests = $dashboardService->getTransferRequest($locationId, session('admin_company_id'));

        return [
            'transferRequests' => $transferRequests,
        ];
    }

    public function getSalesOrder(int $locationId): array
    {
        $dashboardService = resolve(DashboardService::class);
        [$salesOrders, $salesDeliveryOrders] = $dashboardService->getSalesOrder(
            $locationId,
            session('admin_company_id')
        );

        return [
            'salesOrders' => $salesOrders,
            'salesDeliveryOrders' => $salesDeliveryOrders,
        ];
    }

    public function getPurchaseOrder(int $locationId): array
    {
        $dashboardService = resolve(DashboardService::class);
        [$purchaseOrders, $purchaseDeliveryOrders] = $dashboardService->getPurchaseOrder(
            $locationId,
            session('admin_company_id')
        );

        return [
            'purchaseOrders' => $purchaseOrders,
            'purchaseDeliveryOrders' => $purchaseDeliveryOrders,
        ];
    }

    public function getRequestOrder(int $locationId): array
    {
        $dashboardService = resolve(DashboardService::class);
        $requestOrders = $dashboardService->getRequestOrder($locationId, session('admin_company_id'));

        return [
            'requestOrders' => $requestOrders,
        ];
    }

    public function getThisMonthTopSellingProducts(Request $request): array
    {
        $locationId = (int) $request->input('location_id');
        $brandId = (int) $request->input('brand_id');
        $refresh = (bool) $request->input('refresh');

        if ($refresh) {
            Cache::forget('index-refresh-date-time-for-stock-overview');
        }

        $productQueries = resolve(ProductQueries::class);
        $thisMonthTopSellingProducts = $productQueries->getCachedTopSellingProduct(
            session('admin_company_id'),
            $locationId,
            $brandId,
            now()->startOfMonth()->format('Y-m-d'),
            now()->format('Y-m-d'),
            $refresh
        );

        return [
            'thisMonthTopSellingProducts' => DashboardStockOverviewTopSellingProductResource::collection(
                $thisMonthTopSellingProducts
            ),
            'lastUpdate' => Cache::remember(
                'index-refresh-date-time-for-stock-overview',
                900,
                fn (): string => now()->format('d-m-y h:i:s A')
            ),
        ];
    }

    public function getThisYearTopSellingProducts(Request $request): array
    {
        $locationId = (int) $request->input('location_id');
        $brandId = (int) $request->input('brand_id');
        $refresh = (bool) $request->input('refresh');

        $productQueries = resolve(ProductQueries::class);
        $thisYearTopSellingProducts = $productQueries->getCachedTopSellingProduct(
            session('admin_company_id'),
            $locationId,
            $brandId,
            now()->startOfYear()->format('Y-m-d'),
            now()->format('Y-m-d'),
            $refresh
        );

        return [
            'thisYearTopSellingProducts' => DashboardStockOverviewTopSellingProductResource::collection(
                $thisYearTopSellingProducts
            ),
        ];
    }

    public function getThisMonthWorstSellingProducts(Request $request): array
    {
        $locationId = (int) $request->input('location_id');
        $brandId = (int) $request->input('brand_id');
        $refresh = (bool) $request->input('refresh');

        if ($refresh) {
            Cache::forget('index-refresh-date-time-for-stock-overview');
        }

        $productQueries = resolve(ProductQueries::class);
        $thisMonthWorstSellingProducts = $productQueries->getCachedWorstSellingProduct(
            session('admin_company_id'),
            $locationId,
            $brandId,
            now()->startOfMonth()->format('Y-m-d'),
            now()->format('Y-m-d'),
            $refresh
        );

        return [
            'thisMonthWorstSellingProducts' => DashboardStockOverviewWorstSellingProductResource::collection(
                $thisMonthWorstSellingProducts
            ),
            'lastUpdate' => Cache::remember(
                'index-refresh-date-time-for-stock-overview',
                900,
                fn (): string => now()->format('d-m-y h:i:s A')
            ),
        ];
    }

    public function getThisYearWorstSellingProducts(Request $request): array
    {
        $locationId = (int) $request->input('location_id');
        $brandId = (int) $request->input('brand_id');
        $refresh = (bool) $request->input('refresh');

        $productQueries = resolve(ProductQueries::class);
        $thisYearWorstSellingProducts = $productQueries->getCachedWorstSellingProduct(
            session('admin_company_id'),
            $locationId,
            $brandId,
            now()->startOfYear()->format('Y-m-d'),
            now()->format('Y-m-d'),
            $refresh
        );

        return [
            'thisYearWorstSellingProducts' => DashboardStockOverviewWorstSellingProductResource::collection(
                $thisYearWorstSellingProducts
            ),
        ];
    }

    public function getThisMonthTopSellingColors(Request $request): array
    {
        $locationId = (int) $request->input('location_id');
        $brandId = (int) $request->input('brand_id');
        $refresh = (bool) $request->input('refresh');

        $colorQueries = resolve(ColorQueries::class);
        $thisMonthTopSellingColors = $colorQueries->getCachedTopSellingColor(
            session('admin_company_id'),
            $locationId,
            $brandId,
            now()->startOfMonth()->format('Y-m-d'),
            now()->format('Y-m-d'),
            $refresh
        );

        return [
            'thisMonthTopSellingColors' => DashboardStockOverviewTopSellingColorResource::collection(
                $thisMonthTopSellingColors
            ),
        ];
    }

    public function getThisYearTopSellingColors(Request $request): array
    {
        $locationId = (int) $request->input('location_id');
        $brandId = (int) $request->input('brand_id');
        $refresh = (bool) $request->input('refresh');

        $colorQueries = resolve(ColorQueries::class);
        $thisYearTopSellingColors = $colorQueries->getCachedTopSellingColor(
            session('admin_company_id'),
            $locationId,
            $brandId,
            now()->startOfYear()->format('Y-m-d'),
            now()->format('Y-m-d'),
            $refresh
        );

        return [
            'thisYearTopSellingColors' => DashboardStockOverviewTopSellingColorResource::collection(
                $thisYearTopSellingColors
            ),
        ];
    }

    public function getTopRankingProducts(?int $locationId = null): array
    {
        $commonIncludeTypes = [
            SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_IN->value,
            SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_OUT->value,
            SellThroughIncludeTypes::STOCK_ADJUSTMENT_IN->value,
            SellThroughIncludeTypes::STOCK_ADJUSTMENT_OUT->value,
        ];

        $additionalIncludeTypes = null === $locationId ? [] : [
            SellThroughIncludeTypes::STOCK_TRANSFER_IN->value,
            SellThroughIncludeTypes::DELIVERY_ORDER_IN->value,
        ];

        $includeTypes = array_merge($commonIncludeTypes, $additionalIncludeTypes);

        $filterData = [
            'location_ids' => null === $locationId ? null : [$locationId],
            'filter_by' => SellThroughFilterTypes::ALL->value,
            'search_text' => null,
            'sort_by' => 'sell_through',
            'sort_direction' => 'desc',
            'date' => now()->format('Y-m-d'),
            'date_range' => null,
            'include_by' => $includeTypes,
        ];

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);
        $topRankingProducts = $sellThroughAggregateQueries->sellThroughAggregateByProductArticleNumberForDashboard(
            $filterData,
            session('admin_company_id')
        );

        return [
            'topRankingProducts' => $topRankingProducts,
        ];
    }

    public function saleTarget(): Response
    {
        $saleTargetQueries = resolve(SaleTargetQueries::class);

        return Inertia::render('SaleTarget', [
            'saleTargetTimeInterval' => TimeIntervalType::getList(),
            'staticSaleTargetTimeInterval' => TimeIntervalType::generateStaticCasesWithLowerArray(),
            'saleTargetTypes' => TargetType::getList(),
            'staticSaleTargetTypes' => TargetType::generateStaticCasesArray(),
            'targetTypeWiseSaleTargets' => [],
            'saleTarget' => $saleTargetQueries->getListForSaleTargetChart(),
        ]);
    }

    public function fetchYearlySaleTarget(?int $filterId): array
    {
        if (0 == $filterId) {
            $filterId = null;
        }

        $year = Carbon::now()->year;
        $companyId = session('admin_company_id');

        $saleTargetQueries = resolve(SaleTargetQueries::class);

        $currentYearlySaleTarget = $saleTargetQueries->getCurrentYearSalesTarget($year, $companyId, $filterId);
        $previousYearlySaleTarget = $saleTargetQueries->getCurrentYearSalesTarget($year - 1, $companyId, $filterId);

        $saleTargetData = $this->processSaleTargetData(
            $currentYearlySaleTarget,
            $previousYearlySaleTarget,
            collect(),
            collect(),
            collect(),
            collect(),
            collect(),
            collect(),
            'yearly'
        );

        $chartData = $this->generateChartData($saleTargetData, 'yearly', $filterId ?? 0);

        $saleTargetIds = $saleTargetData['sale_target_ids'];
        unset($saleTargetData['sale_target_ids']);

        return [
            'cardData' => $saleTargetData,
            'chartData' => $chartData,
            'sale_target_ids' => $saleTargetIds,
        ];
    }

    public function fetchMonthlySaleTarget(?int $filterId): array
    {
        if (0 == $filterId) {
            $filterId = null;
        }

        $year = Carbon::now()->year;
        $companyId = session('admin_company_id');

        $saleTargetQueries = resolve(SaleTargetQueries::class);

        $currentMonthlySaleTarget = $saleTargetQueries->getCurrentMonthSalesTarget($year, $companyId, $filterId);

        $previousMonths = [];

        foreach ($currentMonthlySaleTarget as $targetTypeIndex => $currentMonthSaleTargets) {
            foreach ($currentMonthSaleTargets as $currentMonthSaleTarget) {
                $currentDate = Carbon::createFromDate($year, $currentMonthSaleTarget->month_date);
                $previousMonthDate = $currentDate->subMonth();

                $previousMonthExists = collect($previousMonths)->contains(
                    fn ($monthData): bool => $monthData['previous_month'] === $previousMonthDate->month && $monthData['previous_year'] === $previousMonthDate->year
                );

                if (! $previousMonthExists) {
                    $previousMonths[] = [
                        'previous_month' => $previousMonthDate->month,
                        'previous_year' => $previousMonthDate->year,
                        'target_type' => $targetTypeIndex,
                    ];
                }
            }
        }

        $previousMonthlySaleTarget = $this->getPreviousMonthlySaleTarget($previousMonths, $companyId);

        $saleTargetData = $this->processSaleTargetData(
            collect(),
            collect(),
            $currentMonthlySaleTarget,
            $previousMonthlySaleTarget,
            collect(),
            collect(),
            collect(),
            collect(),
            'monthly'
        );

        $chartData = $this->generateChartData($saleTargetData, 'monthly');

        $saleTargetIds = $saleTargetData['sale_target_ids'];
        unset($saleTargetData['sale_target_ids']);

        return [
            'cardData' => $saleTargetData,
            'chartData' => $chartData,
            'sale_target_ids' => $saleTargetIds,
        ];
    }

    public function fetchWeeklySaleTarget(?int $filterId): array
    {
        if (0 == $filterId) {
            $filterId = null;
        }

        $year = Carbon::now()->year;
        $companyId = session('admin_company_id');

        $saleTargetQueries = resolve(SaleTargetQueries::class);

        $currentWeeklySaleTarget = $saleTargetQueries->getCurrentWeekSalesTarget($year, $companyId, $filterId);
        $previousWeeks = [];

        foreach ($currentWeeklySaleTarget as $targetTypeIndex => $currentWeeklySaleTargets) {
            foreach ($currentWeeklySaleTargets as $currentWeekSaleTarget) {
                $startOfYear = Carbon::createFromDate($year)->startOfYear();
                $startOfWeek = $startOfYear->addWeeks($currentWeekSaleTarget->week_number - 2)->startOfWeek();
                $endOfWeek = $startOfWeek->copy()->endOfWeek();

                $weekExists = collect($previousWeeks)->contains(
                    fn ($monthData): bool => $startOfWeek->format('Y-m-d') === $monthData['start_of_week'] &&
                        $endOfWeek->format('Y-m-d') === $monthData['end_of_week']
                );

                if (! $weekExists) {
                    $previousWeeks[] = [
                        'start_of_week' => $startOfWeek->format('Y-m-d'),
                        'end_of_week' => $endOfWeek->format('Y-m-d'),
                        'target_type' => $targetTypeIndex,
                    ];
                }
            }
        }

        $previousWeekSaleTarget = $this->getPreviousWeekSaleTarget($previousWeeks, $companyId);

        $saleTargetData = $this->processSaleTargetData(
            collect(),
            collect(),
            collect(),
            collect(),
            $currentWeeklySaleTarget,
            $previousWeekSaleTarget,
            collect(),
            collect(),
            'weekly'
        );

        $chartData = $this->generateChartData($saleTargetData, 'weekly');

        $saleTargetIds = $saleTargetData['sale_target_ids'];
        unset($saleTargetData['sale_target_ids']);

        return [
            'cardData' => $saleTargetData,
            'chartData' => $chartData,
            'sale_target_ids' => $saleTargetIds,
        ];
    }

    public function fetchDailySaleTarget(?int $filterId): array
    {
        if (0 == $filterId) {
            $filterId = null;
        }

        $year = Carbon::now()->year;
        $companyId = session('admin_company_id');

        $saleTargetQueries = resolve(SaleTargetQueries::class);

        $currentDailySaleTarget = $saleTargetQueries->getCurrentDailySalesTarget($year, $companyId, $filterId);

        $previousDays = [];

        foreach ($currentDailySaleTarget as $targetTypeIndex => $currentDailySaleTargets) {
            foreach ($currentDailySaleTargets as $currentDaySaleTarget) {
                /** @var Carbon $formateDate */
                $formateDate = Carbon::createFromFormat('Y-m-d', $currentDaySaleTarget->date);
                $date = $formateDate->subDay()->format('Y-m-d');

                $dayExists = collect($previousDays)->contains(
                    fn ($monthData): bool => $monthData['start_of_day'] === $date &&
                        $monthData['end_of_day'] === $date
                );

                if (! $dayExists) {
                    $previousDays[] = [
                        'start_of_day' => $date,
                        'end_of_day' => $date,
                        'target_type' => $targetTypeIndex,
                    ];
                }
            }
        }

        $previousDailySaleTarget = $this->getPreviousDailySaleTarget($previousDays, $companyId);

        $saleTargetData = $this->processSaleTargetData(
            collect(),
            collect(),
            collect(),
            collect(),
            collect(),
            collect(),
            $currentDailySaleTarget,
            $previousDailySaleTarget,
            'daily'
        );

        $chartData = $this->generateChartData($saleTargetData, 'daily');

        $saleTargetIds = $saleTargetData['sale_target_ids'];
        unset($saleTargetData['sale_target_ids']);

        return [
            'cardData' => $saleTargetData,
            'chartData' => $chartData,
            'sale_target_ids' => $saleTargetIds,
        ];
    }

    public function saleTargetGetCardData(Request $request): array
    {
        $year = Carbon::now()->year;
        $companyId = session('admin_company_id');
        $timeIntervalType = TimeIntervalType::getValueByCaseName(
            Str::of($request->get('time_interval_selection'))->title()->value()
        );

        $targetType = TargetType::getValueByCaseName($request->get('target_type'));

        $timeframeIds = $request->get('timeframe_ids') ?? [];
        $locationIds = $request->get('location_ids') ?? [];
        $promoterIds = $request->get('promoter_ids') ?? [];

        $saleTargetQueries = resolve(SaleTargetQueries::class);

        $data = [];
        $chartData = [];

        if ($timeIntervalType === TimeIntervalType::YEARLY->value) {
            $currentYearlySaleTarget = $saleTargetQueries->getYearSalesTarget(
                $locationIds,
                $promoterIds,
                $year,
                $companyId
            );
            $previousYearlySaleTarget = $saleTargetQueries->getYearSalesTarget(
                $locationIds,
                $promoterIds,
                $year - 1,
                $companyId
            );
            $data = $this->processYearlyData($currentYearlySaleTarget, $previousYearlySaleTarget, $targetType);

            if ([] !== $locationIds) {
                $data['location_ids'] = $locationIds;
            }

            if ([] !== $promoterIds) {
                $data['promoter_ids'] = $promoterIds;
            }

            $saleTargetData[$targetType] = [
                'target_type' => TargetType::getFormattedCaseName($targetType),
                'yearly' => $data,
            ];

            $this->prepareYearlyChartData($chartData, $saleTargetData);
        }

        if ($timeIntervalType === TimeIntervalType::MONTHLY->value) {
            $previousMonths = [];
            $currentMonthlySaleTarget = $saleTargetQueries->getMonthSalesTarget(
                $timeframeIds,
                $locationIds,
                $promoterIds,
                $companyId
            );
            foreach ($currentMonthlySaleTarget as $targetTypeIndex => $currentMonthSaleTargets) {
                foreach ($currentMonthSaleTargets as $currentMonthSaleTarget) {
                    $currentDate = Carbon::createFromDate($year, $currentMonthSaleTarget->month_date);
                    $previousMonthDate = $currentDate->subMonth();

                    $previousMonthExists = collect($previousMonths)->contains(
                        fn ($monthData): bool => $monthData['previous_month'] === $previousMonthDate->month && $monthData['previous_year'] === $previousMonthDate->year
                    );

                    if (! $previousMonthExists) {
                        $previousMonths[] = [
                            'previous_month' => $previousMonthDate->month,
                            'previous_year' => $previousMonthDate->year,
                            'target_type' => $targetTypeIndex,
                        ];
                    }
                }
            }

            $previousMonthlySaleTarget = $this->getPreviousMonthlySaleTarget($previousMonths, $companyId);
            $data = $this->processMonthlyData($currentMonthlySaleTarget, $previousMonthlySaleTarget, $targetType);

            if ([] !== $locationIds) {
                $data['location_ids'] = $locationIds;
            }

            if ([] !== $promoterIds) {
                $data['promoter_ids'] = $promoterIds;
            }

            $saleTargetData[$targetType] = [
                'target_type' => TargetType::getFormattedCaseName($targetType),
                'monthly' => $data,
            ];

            $this->prepareMonthlyChartData($chartData, $saleTargetData);
        }

        if ($timeIntervalType === TimeIntervalType::WEEKLY->value) {
            $previousWeeks = [];
            $currentWeeklySaleTarget = $saleTargetQueries->getWeekSalesTarget(
                $timeframeIds,
                $locationIds,
                $promoterIds,
                $companyId
            );

            foreach ($currentWeeklySaleTarget as $targetTypeIndex => $currentWeeklySaleTargets) {
                foreach ($currentWeeklySaleTargets as $currentWeekSaleTarget) {
                    $startOfYear = Carbon::createFromDate($year)->startOfYear();
                    $startOfWeek = $startOfYear->addWeeks($currentWeekSaleTarget->week_number - 2)->startOfWeek();
                    $endOfWeek = $startOfWeek->copy()->endOfWeek();

                    $weekExists = collect($previousWeeks)->contains(
                        fn ($monthData): bool => $startOfWeek->format('Y-m-d') === $monthData['start_of_week'] &&
                            $endOfWeek->format('Y-m-d') === $monthData['end_of_week']
                    );

                    if (! $weekExists) {
                        $previousWeeks[] = [
                            'start_of_week' => $startOfWeek->format('Y-m-d'),
                            'end_of_week' => $endOfWeek->format('Y-m-d'),
                            'target_type' => $targetTypeIndex,
                        ];
                    }
                }
            }

            $previousWeeklySaleTarget = $this->getPreviousWeekSaleTarget($previousWeeks, $companyId);
            $data = $this->processWeeklyData($currentWeeklySaleTarget, $previousWeeklySaleTarget, $targetType);

            if ([] !== $locationIds) {
                $data['location_ids'] = $locationIds;
            }

            if ([] !== $promoterIds) {
                $data['promoter_ids'] = $promoterIds;
            }

            $saleTargetData[$targetType] = [
                'target_type' => TargetType::getFormattedCaseName($targetType),
                'weekly' => $data,
            ];

            $this->prepareWeeklyChartData($chartData, $saleTargetData);
        }

        if ($timeIntervalType === TimeIntervalType::DAILY->value) {
            $previousDays = [];
            $currentDailySaleTarget = $saleTargetQueries->getDailySalesTarget(
                $timeframeIds,
                $locationIds,
                $promoterIds,
                $companyId
            );

            foreach ($currentDailySaleTarget as $targetTypeIndex => $currentDailySaleTargets) {
                foreach ($currentDailySaleTargets as $currentDaySaleTarget) {
                    /** @var Carbon $formateDate */
                    $formateDate = Carbon::createFromFormat('Y-m-d', $currentDaySaleTarget->date);
                    $date = $formateDate->subDay()->format('Y-m-d');

                    $dayExists = collect($previousDays)->contains(
                        fn ($monthData): bool => $monthData['start_of_day'] === $date &&
                            $monthData['end_of_day'] === $date
                    );

                    if (! $dayExists) {
                        $previousDays[] = [
                            'start_of_day' => $date,
                            'end_of_day' => $date,
                            'target_type' => $targetTypeIndex,
                        ];
                    }
                }
            }

            $previousDailySaleTarget = $this->getPreviousDailySaleTarget($previousDays, $companyId);
            $data = $this->processDailyData($currentDailySaleTarget, $previousDailySaleTarget, $targetType);

            if ([] !== $locationIds) {
                $data['location_ids'] = $locationIds;
            }

            if ([] !== $promoterIds) {
                $data['promoter_ids'] = $promoterIds;
            }

            $saleTargetData[$targetType] = [
                'target_type' => TargetType::getFormattedCaseName($targetType),
                'daily' => $data,
            ];

            $this->prepareDailyChartData($chartData, $saleTargetData);
        }

        return [
            'cardData' => $data,
            'chartData' => $chartData,
        ];
    }

    private function getPreviousMonthlySaleTarget(array $previousMonths, int $companyId): Collection
    {
        $saleTargetQueries = resolve(SaleTargetQueries::class);

        return $saleTargetQueries->getPreviousMonthSalesTarget($previousMonths, $companyId);
    }

    private function getPreviousWeekSaleTarget(array $previousMonths, int $companyId): Collection
    {
        $saleTargetQueries = resolve(SaleTargetQueries::class);

        return $saleTargetQueries->getPreviousWeekSalesTarget($previousMonths, $companyId);
    }

    private function getPreviousDailySaleTarget(array $previousDays, int $companyId): Collection
    {
        $saleTargetQueries = resolve(SaleTargetQueries::class);

        return $saleTargetQueries->getPreviousDailySalesTarget($previousDays, $companyId);
    }

    private function processSaleTargetData(
        Collection $currentYearlySaleTarget,
        Collection $previousYearlySaleTarget,
        Collection $currentMonthlySaleTarget,
        Collection $previousMonthlySaleTarget,
        Collection $currentWeeklySaleTarget,
        Collection $previousWeeklySaleTarget,
        Collection $currentDailySaleTarget,
        Collection $previousDailySaleTarget,
        string $type,
    ): array {
        $saleTargetData = [];
        $saleTargetData['sale_target_ids'] = [];
        foreach (TargetType::getList() as $targetType) {
            $typeId = $targetType['id'];

            $saleTargetData[$typeId] = [
                'target_type' => TargetType::getFormattedCaseName($typeId),
            ];

            if ('yearly' === $type || 'all' === $type) {
                $saleTargetData[$typeId]['yearly'] = $this->processYearlyData(
                    $currentYearlySaleTarget,
                    $previousYearlySaleTarget,
                    $typeId
                );

                $saleTargetData['sale_target_ids'] = collect($saleTargetData['sale_target_ids'])
                    ->merge(isset($currentYearlySaleTarget[$typeId])
                        ? $currentYearlySaleTarget[$typeId]->pluck('sale_target_id')
                        : collect())
                    ->merge(isset($previousYearlySaleTarget[$typeId])
                        ? $previousYearlySaleTarget[$typeId]->pluck('sale_target_id')
                        : collect())
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }

            if ('monthly' === $type || 'all' === $type) {
                $saleTargetData[$typeId]['monthly'] = $this->processMonthlyData(
                    $currentMonthlySaleTarget,
                    $previousMonthlySaleTarget,
                    $typeId
                );
                $saleTargetData['sale_target_ids'] = collect($saleTargetData['sale_target_ids'])
                    ->merge(isset($currentMonthlySaleTarget[$typeId])
                        ? $currentMonthlySaleTarget[$typeId]->pluck('sale_target_id')
                        : collect())
                    ->merge(isset($previousMonthlySaleTarget[$typeId])
                        ? $previousMonthlySaleTarget[$typeId]->pluck('sale_target_id')
                        : collect())
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }

            if ('weekly' === $type || 'all' === $type) {
                $saleTargetData[$typeId]['weekly'] = $this->processWeeklyData(
                    $currentWeeklySaleTarget,
                    $previousWeeklySaleTarget,
                    $typeId
                );
                $saleTargetData['sale_target_ids'] = collect($saleTargetData['sale_target_ids'])
                    ->merge(isset($currentWeeklySaleTarget[$typeId])
                        ? $currentWeeklySaleTarget[$typeId]->pluck('sale_target_id')
                        : collect())
                    ->merge(isset($previousWeeklySaleTarget[$typeId])
                        ? $previousWeeklySaleTarget[$typeId]->pluck('sale_target_id')
                        : collect())
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }

            if ('daily' === $type || 'all' === $type) {
                $saleTargetData[$typeId]['daily'] = $this->processDailyData(
                    $currentDailySaleTarget,
                    $previousDailySaleTarget,
                    $typeId
                );
                $saleTargetData['sale_target_ids'] = collect($saleTargetData['sale_target_ids'])
                    ->merge(isset($currentDailySaleTarget[$typeId])
                        ? $currentDailySaleTarget[$typeId]->pluck('sale_target_id')
                        : collect())
                    ->merge(isset($previousDailySaleTarget[$typeId])
                        ? $previousDailySaleTarget[$typeId]->pluck('sale_target_id')
                        : collect())
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }
        }

        return $saleTargetData;
    }

    private function processYearlyData(
        Collection $currentYearlySaleTarget,
        Collection $previousYearlySaleTarget,
        int $typeId,
    ): array {
        $current = $currentYearlySaleTarget[$typeId] ?? collect();
        $previous = $previousYearlySaleTarget[$typeId] ?? collect();

        $locationIds = $this->getLocationIds($current, $typeId);
        $locations = [];
        if ([] !== $locationIds) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getByIdsWithNameAndCode(session('admin_company_id'), $locationIds);
        }

        $promoterIds = $this->getPromoterIds($current, $typeId);

        $promoters = [];
        if ([] !== $promoterIds) {
            $promoterQueries = resolve(PromoterQueries::class);
            $promoters = $promoterQueries->getPromoterByIds($promoterIds, session('admin_company_id'));

            $promoters->transform(function ($promoter): array {
                /** @var Employee $employee */
                $employee = $promoter->employee;

                return [
                    'id' => $promoter->id,
                    'name' => $employee->getFullName(),
                ];
            });
        }

        return [
            'target' => $current->sum('target_value'),
            'achieved' => $current->sum('achieved_value'),
            'previous_target' => $previous->sum('target_value'),
            'previous_achieved' => $previous->sum('achieved_value'),
            'location_ids' => $locationIds,
            'promoter_ids' => $promoterIds,
            'locations' => $locations,
            'promoters' => $promoters,
        ];
    }

    private function processMonthlyData(
        Collection $currentMonthlySaleTarget,
        Collection $previousMonthlySaleTarget,
        int $typeId,
    ): array {
        $current = $currentMonthlySaleTarget[$typeId] ?? collect();
        $previous = $previousMonthlySaleTarget[$typeId] ?? collect();

        $months = $current->pluck('month')->filter()->unique()->toArray();
        $saleTargetTimeframeIds = $current->pluck('sale_target_timeframe_id')->filter()->unique()->toArray();

        /**
         * @var array<int, int|string> $saleTargetTimeframeIds
         * @var array<int, string> $months
         */
        $filter = collect($saleTargetTimeframeIds)->zip($months)->map(fn ($item): array => [
            'id' => $item[0],
            'name' => $item[1],
        ])->toArray();

        $locationIds = $this->getLocationIds($current, $typeId);
        $locations = [];
        if ([] !== $locationIds) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getByIdsWithNameAndCode(session('admin_company_id'), $locationIds);
        }

        $promoterIds = $this->getPromoterIds($current, $typeId);

        $promoters = [];
        if ([] !== $promoterIds) {
            $promoterQueries = resolve(PromoterQueries::class);
            $promoters = $promoterQueries->getPromoterByIds($promoterIds, session('admin_company_id'));

            $promoters->transform(function ($promoter): array {
                /** @var Employee $employee */
                $employee = $promoter->employee;

                return [
                    'id' => $promoter->id,
                    'name' => $employee->getFullName(),
                ];
            });
        }

        return [
            'target' => $current->sum('target_value'),
            'achieved' => $current->sum('achieved_value'),
            'previous_target' => $previous->sum('target_value'),
            'previous_achieved' => $previous->sum('achieved_value'),
            'location_ids' => $locationIds,
            'promoter_ids' => $promoterIds,
            'months' => $current->pluck('month_date')->filter()->unique()->toArray(),
            'filter' => $filter,
            'locations' => $locations,
            'promoters' => $promoters,
            'label' => 'Months',
        ];
    }

    private function processWeeklyData(
        Collection $currentWeeklySaleTarget,
        Collection $previousWeeklySaleTarget,
        int $typeId,
    ): array {
        $current = $currentWeeklySaleTarget[$typeId] ?? collect();
        $previous = $previousWeeklySaleTarget[$typeId] ?? collect();

        $weekNumbers = $current->pluck('week_number')->filter()->unique()->toArray();
        $saleTargetTimeframeIds = $current->pluck('sale_target_timeframe_id')->filter()->unique()->toArray();

        /**
         * @var int[] $weekNumbers
         * @return array<int, string>
         */
        $weekDescriptions = collect($weekNumbers)->mapWithKeys(function ($weekNumber) {
            $year = date('Y');
            $startOfWeek = Carbon::now()->setISODate((int) $year, $weekNumber)->startOfWeek();
            $endOfWeek = $startOfWeek->copy()->endOfWeek();

            $description = sprintf('Week %d (', $weekNumber) . $startOfWeek->format('M j') . ' - ' . $endOfWeek->format(
                'M j'
            ) . ')';

            return [
                $weekNumber => $description,
            ];
        })->toArray();

        /**
         * @var int[] $saleTargetTimeframeIds
         * @var array<int, string> $weekDescriptions
         * @return array<int, array{id: int, name: string}>
         */
        $filter = collect($saleTargetTimeframeIds)->zip($weekDescriptions)->map(fn ($item): array => [
            'id' => $item[0],
            'name' => $item[1],
        ])->toArray();

        $locationIds = $this->getLocationIds($current, $typeId);
        $locations = [];
        if ([] !== $locationIds) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getByIdsWithNameAndCode(session('admin_company_id'), $locationIds);
        }

        $promoterIds = $this->getPromoterIds($current, $typeId);

        $promoters = [];
        if ([] !== $promoterIds) {
            $promoterQueries = resolve(PromoterQueries::class);
            $promoters = $promoterQueries->getPromoterByIds($promoterIds, session('admin_company_id'));

            $promoters->transform(function ($promoter): array {
                /** @var Employee $employee */
                $employee = $promoter->employee;

                return [
                    'id' => $promoter->id,
                    'name' => $employee->getFullName(),
                ];
            });
        }

        return [
            'target' => $current->sum('target_value'),
            'achieved' => $current->sum('achieved_value'),
            'previous_target' => $previous->sum('target_value'),
            'previous_achieved' => $previous->sum('achieved_value'),
            'location_ids' => $locationIds,
            'promoter_ids' => $promoterIds,
            'weeks' => $current->pluck('week_number')->filter()->unique()->toArray(),
            'filter' => $filter,
            'locations' => $locations,
            'promoters' => $promoters,
            'label' => 'Weeks',
        ];
    }

    private function processDailyData(
        Collection $currentDailySaleTarget,
        Collection $previousDailySaleTarget,
        int $typeId,
    ): array {
        $current = $currentDailySaleTarget[$typeId] ?? collect();
        $previous = $previousDailySaleTarget[$typeId] ?? collect();

        $dates = $current->pluck('date')->filter()->unique()->toArray();
        $saleTargetTimeframeIds = $current->pluck('sale_target_timeframe_id')->filter()->unique()->toArray();

        /**
         * @var int[] $saleTargetTimeframeIds
         * @var string[] $dates
         * @return array<int, array{id: int, name: string}>
         */
        $filter = collect($saleTargetTimeframeIds)->zip($dates)->map(fn ($item): array => [
            'id' => $item[0],
            'name' => $item[1],
        ])->toArray();
        $locationIds = $this->getLocationIds($current, $typeId);
        $locations = [];
        if ([] !== $locationIds) {
            $locationQueries = resolve(LocationQueries::class);
            $locations = $locationQueries->getByIdsWithNameAndCode(session('admin_company_id'), $locationIds);
        }

        $promoterIds = $this->getPromoterIds($current, $typeId);
        $promoters = [];
        if ([] !== $promoterIds) {
            $promoterQueries = resolve(PromoterQueries::class);
            $promoters = $promoterQueries->getPromoterByIds($promoterIds, session('admin_company_id'));

            $promoters->transform(function ($promoter): array {
                /** @var Employee $employee */
                $employee = $promoter->employee;

                return [
                    'id' => $promoter->id,
                    'name' => $employee->getFullName(),
                ];
            });
        }

        return [
            'target' => $current->sum('target_value'),
            'achieved' => $current->sum('achieved_value'),
            'previous_target' => $previous->sum('target_value'),
            'previous_achieved' => $previous->sum('achieved_value'),
            'location_ids' => $locationIds,
            'promoter_ids' => $promoterIds,
            'dates' => $current->pluck('date')->filter()->unique()->toArray(),
            'filter' => $filter,
            'locations' => $locations,
            'promoters' => $promoters,
            'label' => 'Days',
        ];
    }

    private function getLocationIds(Collection $saleTargets, int $typeId): array
    {
        $saleTargetQueries = resolve(SaleTargetQueries::class);

        if ($typeId === TargetType::STORE_WISE->value) {
            return $saleTargetQueries->getLocations(
                $saleTargets->pluck('sale_target_id')->filter()->toArray(),
                session('admin_company_id')
            )
                ->pluck('locations')->collapse()->pluck('id')->unique()->values()->all();
        }

        return [];
    }

    private function getPromoterIds(Collection $saleTargets, int $typeId): array
    {
        $saleTargetQueries = resolve(SaleTargetQueries::class);
        if ($typeId === TargetType::PROMOTER_WISE->value) {
            return $saleTargetQueries->getPromoters(
                $saleTargets->pluck('sale_target_id')->filter()->toArray(),
                session('admin_company_id')
            )
                ->pluck('promoters')->collapse()->pluck('id')->unique()->values()->all();
        }

        return [];
    }

    private function generateChartData(array $saleTargetData, string $type, int $filterId = 0): array
    {
        $chartData = [];

        match ($type) {
            'yearly' => $this->prepareYearlyChartData($chartData, $saleTargetData, $filterId),
            'monthly' => $this->prepareMonthlyChartData($chartData, $saleTargetData, $filterId),
            'weekly' => $this->prepareWeeklyChartData($chartData, $saleTargetData, $filterId),
            'daily' => $this->prepareDailyChartData($chartData, $saleTargetData, $filterId),
            default => throw new InvalidArgumentException('Invalid type: ' . $type),
        };

        return $chartData;
    }

    private function prepareYearlyChartData(array &$chartData, array $saleTargetData, int $filterId = 0): void
    {
        $date = Carbon::now();
        $saleQueries = resolve(SaleQueries::class);

        $chartData = [];
        $companyId = session('admin_company_id');
        $dateRange = [$date->startOfYear()->format('Y-m-d H:i:s'), $date->endOfYear()->format('Y-m-d H:i:s')];
        $previousDateRange = [
            $date->subYear()->startOfYear()->format('Y-m-d H:i:s'),
            $date->endOfYear()->format('Y-m-d H:i:s'),
        ];

        $sales = $saleQueries->getYearlySalesAndSaleReturnsGroupByMonth(
            $dateRange,
            $companyId,
            null,
            null,
            TargetType::COMPANY_WISE->value,
            $filterId
        );
        $chartData[TargetType::getFormattedCaseName(
            TargetType::COMPANY_WISE->value
        )]['yearly']['current'] = $this->formatChartData($sales);

        $previousSales = $saleQueries->getYearlySalesAndSaleReturnsGroupByMonth(
            $previousDateRange,
            $companyId,
            null,
            null,
            TargetType::COMPANY_WISE->value,
            $filterId
        );
        $chartData[TargetType::getFormattedCaseName(
            TargetType::COMPANY_WISE->value
        )]['yearly']['previous'] = $this->formatChartData($previousSales);

        if (! empty($saleTargetData[TargetType::STORE_WISE->value]['yearly']['location_ids'])) {
            $salesLocationWise = $saleQueries->getYearlySalesAndSaleReturnsGroupByMonth(
                $dateRange,
                $companyId,
                $saleTargetData[TargetType::STORE_WISE->value]['yearly']['location_ids'],
                null,
                TargetType::STORE_WISE->value,
                $filterId
            );
            $chartData[TargetType::getFormattedCaseName(
                TargetType::STORE_WISE->value
            )]['yearly']['current'] = $this->formatChartData($salesLocationWise);

            $salesLocationWisePrevious = $saleQueries->getYearlySalesAndSaleReturnsGroupByMonth(
                $previousDateRange,
                $companyId,
                $saleTargetData[TargetType::STORE_WISE->value]['yearly']['location_ids'],
                null,
                TargetType::STORE_WISE->value,
                $filterId
            );
            $chartData[TargetType::getFormattedCaseName(
                TargetType::STORE_WISE->value
            )]['yearly']['previous'] = $this->formatChartData($salesLocationWisePrevious);
        }

        if (! empty($saleTargetData[TargetType::PROMOTER_WISE->value]['yearly']['promoter_ids'])) {
            $salesPromoterWise = $saleQueries->getYearlySalesAndSaleReturnsGroupByMonth(
                $dateRange,
                $companyId,
                null,
                $saleTargetData[TargetType::PROMOTER_WISE->value]['yearly']['promoter_ids'],
                TargetType::PROMOTER_WISE->value,
                $filterId
            );
            $chartData[TargetType::getFormattedCaseName(
                TargetType::PROMOTER_WISE->value
            )]['yearly']['current'] = $this->formatChartData($salesPromoterWise);

            $salesPromoterWisePrevious = $saleQueries->getYearlySalesAndSaleReturnsGroupByMonth(
                $previousDateRange,
                $companyId,
                null,
                $saleTargetData[TargetType::PROMOTER_WISE->value]['yearly']['promoter_ids'],
                TargetType::PROMOTER_WISE->value,
                $filterId
            );
            $chartData[TargetType::getFormattedCaseName(
                TargetType::PROMOTER_WISE->value
            )]['yearly']['previous'] = $this->formatChartData($salesPromoterWisePrevious);
        }
    }

    private function prepareMonthlyChartData(array &$chartData, array $saleTargetData, int $filterId = 0): void
    {
        $saleQueries = resolve(SaleQueries::class);
        $companyId = session('admin_company_id');

        if (isset($saleTargetData[TargetType::COMPANY_WISE->value])) {
            $dateRanges = $this->getMonthlyDateRanges($saleTargetData, TargetType::COMPANY_WISE->value);
            $previousDateRanges = $this->getPreviousMonthlyDateRanges($saleTargetData, TargetType::COMPANY_WISE->value);

            $sales = $saleQueries->getMonthlySalesAndSaleReturnsGroupByMonth(
                $dateRanges,
                $companyId,
                null,
                null,
                TargetType::COMPANY_WISE->value,
                $filterId
            );
            $chartData[TargetType::getFormattedCaseName(
                TargetType::COMPANY_WISE->value
            )]['monthly']['current'] = $this->formatChartData($sales);

            $previousSales = $saleQueries->getMonthlySalesAndSaleReturnsGroupByMonth(
                $previousDateRanges,
                $companyId,
                null,
                null,
                TargetType::COMPANY_WISE->value,
                $filterId
            );

            $chartData[TargetType::getFormattedCaseName(
                TargetType::COMPANY_WISE->value
            )]['monthly']['previous'] = $this->formatChartData($previousSales);
        }

        if (! empty($saleTargetData[TargetType::STORE_WISE->value]['monthly']['location_ids'])) {
            $dateRanges = $this->getMonthlyDateRanges($saleTargetData, TargetType::STORE_WISE->value);
            $previousDateRanges = $this->getPreviousMonthlyDateRanges($saleTargetData, TargetType::STORE_WISE->value);

            $salesLocationWise = $saleQueries->getMonthlySalesAndSaleReturnsGroupByMonth(
                $dateRanges,
                $companyId,
                $saleTargetData[TargetType::STORE_WISE->value]['monthly']['location_ids'],
                null,
                TargetType::STORE_WISE->value,
                $filterId
            );
            $chartData[TargetType::getFormattedCaseName(
                TargetType::STORE_WISE->value
            )]['monthly']['current'] = $this->formatChartData($salesLocationWise);

            $salesLocationWisePrevious = $saleQueries->getMonthlySalesAndSaleReturnsGroupByMonth(
                $previousDateRanges,
                $companyId,
                $saleTargetData[TargetType::STORE_WISE->value]['monthly']['location_ids'],
                null,
                TargetType::STORE_WISE->value,
                $filterId
            );
            $chartData[TargetType::getFormattedCaseName(
                TargetType::STORE_WISE->value
            )]['monthly']['previous'] = $this->formatChartData($salesLocationWisePrevious);
        }

        if (! empty($saleTargetData[TargetType::PROMOTER_WISE->value]['monthly']['promoter_ids'])) {
            $dateRanges = $this->getMonthlyDateRanges($saleTargetData, TargetType::PROMOTER_WISE->value);
            $previousDateRanges = $this->getPreviousMonthlyDateRanges(
                $saleTargetData,
                TargetType::PROMOTER_WISE->value
            );

            $salesPromoterWise = $saleQueries->getMonthlySalesAndSaleReturnsGroupByMonth(
                $dateRanges,
                $companyId,
                null,
                $saleTargetData[TargetType::PROMOTER_WISE->value]['monthly']['promoter_ids'],
                TargetType::PROMOTER_WISE->value,
                $filterId
            );
            $chartData[TargetType::getFormattedCaseName(
                TargetType::PROMOTER_WISE->value
            )]['monthly']['current'] = $this->formatChartData($salesPromoterWise);

            $salesPromoterWisePrevious = $saleQueries->getMonthlySalesAndSaleReturnsGroupByMonth(
                $previousDateRanges,
                $companyId,
                null,
                $saleTargetData[TargetType::PROMOTER_WISE->value]['monthly']['promoter_ids'],
                TargetType::PROMOTER_WISE->value,
                $filterId
            );
            $chartData[TargetType::getFormattedCaseName(
                TargetType::PROMOTER_WISE->value
            )]['monthly']['previous'] = $this->formatChartData($salesPromoterWisePrevious);
        }
    }

    private function prepareWeeklyChartData(array &$chartData, array $saleTargetData, int $filterId = 0): void
    {
        $saleQueries = resolve(SaleQueries::class);
        $companyId = session('admin_company_id');

        $dateRanges = $this->getWeeklyDateRanges($saleTargetData, TargetType::COMPANY_WISE->value);
        $previousDateRanges = $this->getPreviousWeeklyDateRanges($saleTargetData, TargetType::COMPANY_WISE->value);

        $sales = $saleQueries->getWeeklySalesAndSaleReturnsGroupByWeek(
            $dateRanges,
            $companyId,
            null,
            null,
            TargetType::COMPANY_WISE->value,
            $filterId
        );
        $chartData[TargetType::getFormattedCaseName(
            TargetType::COMPANY_WISE->value
        )]['weekly']['current'] = $this->formatChartData($sales);

        $previousSales = $saleQueries->getWeeklySalesAndSaleReturnsGroupByWeek(
            $previousDateRanges,
            $companyId,
            null,
            null,
            TargetType::COMPANY_WISE->value,
            $filterId
        );
        $chartData[TargetType::getFormattedCaseName(
            TargetType::COMPANY_WISE->value
        )]['weekly']['previous'] = $this->formatChartData($previousSales);

        if (! empty($saleTargetData[TargetType::STORE_WISE->value]['weekly']['location_ids'])) {
            $dateRanges = $this->getWeeklyDateRanges($saleTargetData, TargetType::STORE_WISE->value);
            $previousDateRanges = $this->getPreviousWeeklyDateRanges($saleTargetData, TargetType::STORE_WISE->value);

            $salesLocationWise = $saleQueries->getWeeklySalesAndSaleReturnsGroupByWeek(
                $dateRanges,
                $companyId,
                $saleTargetData[TargetType::STORE_WISE->value]['weekly']['location_ids'],
                null,
                TargetType::STORE_WISE->value,
                $filterId
            );
            $chartData[TargetType::getFormattedCaseName(
                TargetType::STORE_WISE->value
            )]['weekly']['current'] = $this->formatChartData($salesLocationWise);

            $salesLocationWisePrevious = $saleQueries->getWeeklySalesAndSaleReturnsGroupByWeek(
                $previousDateRanges,
                $companyId,
                $saleTargetData[TargetType::STORE_WISE->value]['weekly']['location_ids'],
                null,
                TargetType::STORE_WISE->value,
                $filterId
            );
            $chartData[TargetType::getFormattedCaseName(
                TargetType::STORE_WISE->value
            )]['weekly']['pervious'] = $this->formatChartData($salesLocationWisePrevious);
        }

        if (! empty($saleTargetData[TargetType::PROMOTER_WISE->value]['weekly']['promoter_ids'])) {
            $dateRanges = $this->getWeeklyDateRanges($saleTargetData, TargetType::PROMOTER_WISE->value);
            $previousDateRanges = $this->getPreviousWeeklyDateRanges($saleTargetData, TargetType::PROMOTER_WISE->value);

            $salesPromoterWise = $saleQueries->getWeeklySalesAndSaleReturnsGroupByWeek(
                $dateRanges,
                $companyId,
                null,
                $saleTargetData[TargetType::PROMOTER_WISE->value]['weekly']['promoter_ids'],
                TargetType::PROMOTER_WISE->value,
                $filterId
            );
            $chartData[TargetType::getFormattedCaseName(
                TargetType::PROMOTER_WISE->value
            )]['weekly']['current'] = $this->formatChartData($salesPromoterWise);

            $salesPromoterWisePrevious = $saleQueries->getWeeklySalesAndSaleReturnsGroupByWeek(
                $previousDateRanges,
                $companyId,
                null,
                $saleTargetData[TargetType::PROMOTER_WISE->value]['weekly']['promoter_ids'],
                TargetType::PROMOTER_WISE->value,
                $filterId
            );
            $chartData[TargetType::getFormattedCaseName(
                TargetType::PROMOTER_WISE->value
            )]['weekly']['previous'] = $this->formatChartData($salesPromoterWisePrevious);
        }
    }

    private function prepareDailyChartData(array &$chartData, array $saleTargetData, int $filterId = 0): void
    {
        $saleQueries = resolve(SaleQueries::class);
        $companyId = session('admin_company_id');

        $dateRanges = $this->getDailyDateRanges($saleTargetData, TargetType::COMPANY_WISE->value);
        $previousDateRanges = $this->getPreviousDailyDateRanges($saleTargetData, TargetType::COMPANY_WISE->value);
        $sales = $saleQueries->getDailySalesAndSaleReturnsGroupByWeek(
            $dateRanges,
            $companyId,
            null,
            null,
            TargetType::COMPANY_WISE->value,
            $filterId
        );
        $chartData[TargetType::getFormattedCaseName(
            TargetType::COMPANY_WISE->value
        )]['daily']['current'] = $this->formatChartData($sales);

        $previousSales = $saleQueries->getDailySalesAndSaleReturnsGroupByWeek(
            $previousDateRanges,
            $companyId,
            null,
            null,
            TargetType::COMPANY_WISE->value,
            $filterId
        );
        $chartData[TargetType::getFormattedCaseName(
            TargetType::COMPANY_WISE->value
        )]['daily']['previous'] = $this->formatChartData($previousSales);

        if (! empty($saleTargetData[TargetType::STORE_WISE->value]['daily']['location_ids'])) {
            $dateRanges = $this->getDailyDateRanges($saleTargetData, TargetType::STORE_WISE->value);
            $previousDateRanges = $this->getPreviousDailyDateRanges($saleTargetData, TargetType::STORE_WISE->value);

            $salesLocationWise = $saleQueries->getDailySalesAndSaleReturnsGroupByWeek(
                $dateRanges,
                $companyId,
                $saleTargetData[TargetType::STORE_WISE->value]['daily']['location_ids'],
                null,
                TargetType::STORE_WISE->value,
                $filterId
            );
            $chartData[TargetType::getFormattedCaseName(
                TargetType::STORE_WISE->value
            )]['daily']['current'] = $this->formatChartData($salesLocationWise);

            $salesLocationWisePrevious = $saleQueries->getDailySalesAndSaleReturnsGroupByWeek(
                $previousDateRanges,
                $companyId,
                $saleTargetData[TargetType::STORE_WISE->value]['daily']['location_ids'],
                null,
                TargetType::STORE_WISE->value,
                $filterId
            );
            $chartData[TargetType::getFormattedCaseName(
                TargetType::STORE_WISE->value
            )]['daily']['previous'] = $this->formatChartData($salesLocationWisePrevious);
        }

        if (! empty($saleTargetData[TargetType::PROMOTER_WISE->value]['daily']['promoter_ids'])) {
            $dateRanges = $this->getDailyDateRanges($saleTargetData, TargetType::PROMOTER_WISE->value);
            $previousDateRanges = $this->getPreviousDailyDateRanges($saleTargetData, TargetType::PROMOTER_WISE->value);

            $salesPromoterWise = $saleQueries->getDailySalesAndSaleReturnsGroupByWeek(
                $dateRanges,
                $companyId,
                null,
                $saleTargetData[TargetType::PROMOTER_WISE->value]['daily']['promoter_ids'],
                TargetType::PROMOTER_WISE->value,
                $filterId
            );
            $chartData[TargetType::getFormattedCaseName(
                TargetType::PROMOTER_WISE->value
            )]['daily']['current'] = $this->formatChartData($salesPromoterWise);

            $salesPromoterWisePrevious = $saleQueries->getDailySalesAndSaleReturnsGroupByWeek(
                $previousDateRanges,
                $companyId,
                null,
                $saleTargetData[TargetType::PROMOTER_WISE->value]['daily']['promoter_ids'],
                TargetType::PROMOTER_WISE->value,
                $filterId
            );
            $chartData[TargetType::getFormattedCaseName(
                TargetType::PROMOTER_WISE->value
            )]['daily']['previous'] = $this->formatChartData($salesPromoterWisePrevious);
        }
    }

    private function getMonthlyDateRanges(array $saleTargetData, int $typeId): array
    {
        $dateRanges = [];
        $currentYear = Carbon::now()->year;
        foreach ($saleTargetData[$typeId]['monthly']['months'] as $month) {
            $date = Carbon::createFromDate($currentYear, $month);
            $dateRanges[] = [$date->startOfMonth()->format('Y-m-d H:i:s'), $date->endOfMonth()->format('Y-m-d H:i:s')];
        }

        return $dateRanges;
    }

    private function getPreviousMonthlyDateRanges(array $saleTargetData, int $typeId): array
    {
        $dateRanges = [];
        $currentYear = Carbon::now()->subYear()->year;
        foreach ($saleTargetData[$typeId]['monthly']['months'] as $month) {
            $date = Carbon::createFromDate($currentYear, $month);
            $dateRanges[] = [$date->startOfMonth()->format('Y-m-d H:i:s'), $date->endOfMonth()->format('Y-m-d H:i:s')];
        }

        return $dateRanges;
    }

    private function getWeeklyDateRanges(array $saleTargetData, int $typeId): array
    {
        $dateRanges = [];
        $currentYear = Carbon::now()->year;

        if (! isset($saleTargetData[$typeId])) {
            return $dateRanges;
        }

        foreach ($saleTargetData[$typeId]['weekly']['weeks'] as $weekNumber) {
            $startOfWeek = Carbon::createFromDate($currentYear)->firstOfYear()->addWeeks(
                $weekNumber - 1
            )->startOfWeek();
            $endOfWeek = $startOfWeek->copy()->endOfWeek();

            $dateRanges[] = [$startOfWeek->format('Y-m-d H:i:s'), $endOfWeek->format('Y-m-d H:i:s')];
        }

        return $dateRanges;
    }

    private function getPreviousWeeklyDateRanges(array $saleTargetData, int $typeId): array
    {
        $dateRanges = [];
        $currentYear = Carbon::now()->subYear()->year;

        if (! isset($saleTargetData[$typeId])) {
            return $dateRanges;
        }

        foreach ($saleTargetData[$typeId]['weekly']['weeks'] as $weekNumber) {
            $startOfWeek = Carbon::createFromDate($currentYear)->firstOfYear()->addWeeks(
                $weekNumber - 1
            )->startOfWeek();
            $endOfWeek = $startOfWeek->copy()->endOfWeek();

            $dateRanges[] = [$startOfWeek->format('Y-m-d H:i:s'), $endOfWeek->format('Y-m-d H:i:s')];
        }

        return $dateRanges;
    }

    private function getDailyDateRanges(array $saleTargetData, int $typeId): array
    {
        $dateRanges = [];
        foreach ($saleTargetData[$typeId]['daily']['dates'] as $saleTargetDate) {
            $dateRanges[] = [$saleTargetDate, $saleTargetDate];
        }

        return $dateRanges;
    }

    private function getPreviousDailyDateRanges(array $saleTargetData, int $typeId): array
    {
        $dateRanges = [];

        foreach ($saleTargetData[$typeId]['daily']['dates'] as $saleTargetDate) {
            $dateRanges[] = [$saleTargetDate, $saleTargetDate];
        }

        return $dateRanges;
    }

    private function formatChartData(Collection $sales, bool $isTitle = false): array
    {
        $data = [];
        $labels = [];
        $target = [];

        foreach ($sales as $monthIndex => $sale) {
            $data[] = $sale['net_sales'] ?? 0;
            $labels[] = $isTitle ? $sale['week_name'] : $sale['month'] ?? $monthIndex;
            $target[] = $sale['target'] ?? 0;
        }

        return [
            'data' => $data,
            'labels' => $labels,
            'target' => $target,
        ];
    }

    public function saleTargetWeeklySales(Request $request): array
    {
        $year = Carbon::now()->year;
        $requestMonth = (int) $request->input('month');
        $month = $requestMonth;
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');
        $dateRange = [$startDate, $endDate];

        $previousYear = Carbon::now()->subYear()->year;
        $previousStartDate = Carbon::createFromDate($previousYear, $month, 1)->startOfMonth()->format('Y-m-d');
        $previousEndDate = Carbon::createFromDate($previousYear, $month, 1)->endOfMonth()->format('Y-m-d');

        $previousDateRange = [$previousStartDate, $previousEndDate];

        $targetType = $request->input('target_type');
        $locationIds = $request->input('location_ids', []);
        $promoterIds = $request->input('promoter_ids', []);

        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getWeeklySalesAndSaleReturns(
            session('admin_company_id'),
            $month,
            $year,
            $locationIds,
            $promoterIds,
            TargetType::getValueByCaseName($targetType),
            $dateRange,
        );

        $chartData[$targetType] = [
            'weekly' => [
                'current' => [
                    'labels' => [],
                    'data' => [],
                    'target' => [],
                ],
                'previous' => [
                    'labels' => [],
                    'data' => [],
                    'target' => [],
                ],
            ],
        ];

        foreach ($sales as $weekIndex => $sale) {
            $chartData[$targetType]['weekly']['current']['data'][] = $sale['net_sales'];
            $chartData[$targetType]['weekly']['current']['labels'][] = 'Week ' . $weekIndex;
            $chartData[$targetType]['weekly']['current']['weeks'][] = $weekIndex;
            $chartData[$targetType]['weekly']['current']['target'][] = $sale['target'];
        }

        $previousSales = $saleQueries->getWeeklySalesAndSaleReturns(
            session('admin_company_id'),
            $month,
            $previousYear,
            $locationIds,
            $promoterIds,
            TargetType::getValueByCaseName($targetType),
            $previousDateRange,
        );

        foreach ($previousSales as $weekIndex => $sale) {
            $chartData[$targetType]['weekly']['previous']['data'][] = $sale['net_sales'];
            $chartData[$targetType]['weekly']['previous']['labels'][] = 'Week ' . $weekIndex;
            $chartData[$targetType]['weekly']['previous']['weeks'][] = $weekIndex;
            $chartData[$targetType]['weekly']['previous']['target'][] = $sale['target'];
        }

        return [
            'chartData' => $chartData,
        ];
    }

    public function saleTargetDailySales(Request $request): array
    {
        $year = Carbon::now()->year;
        $week = (int) $request->input('week');
        $targetType = $request->input('target_type');
        $locationIds = $request->input('location_ids', []);
        $promoterIds = $request->input('promoter_ids', []);

        $startDate = Carbon::now()->setISODate($year, $week)->startOfWeek()->format('Y-m-d');
        $endDate = Carbon::now()->setISODate($year, $week)->endOfWeek()->format('Y-m-d');
        $dateRange = [$startDate, $endDate];

        $previousYear = Carbon::now()->subYear()->year;
        $previousStartDate = Carbon::now()->setISODate($previousYear, $week)->startOfWeek()->format('Y-m-d');
        $previousEndDate = Carbon::now()->setISODate($previousYear, $week)->endOfWeek()->format('Y-m-d');

        $previousDateRange = [$previousStartDate, $previousEndDate];

        $chartData[$targetType] = [
            'daily' => [
                'current' => [
                    'labels' => [],
                    'data' => [],
                    'target' => [],
                ],
                'previous' => [
                    'labels' => [],
                    'data' => [],
                    'target' => [],
                ],
            ],
        ];

        $saleQueries = resolve(SaleQueries::class);
        $sales = $saleQueries->getDailySalesAndSaleReturns(
            session('admin_company_id'),
            $week,
            $year,
            $locationIds,
            $promoterIds,
            TargetType::getValueByCaseName($targetType),
            $dateRange
        );
        foreach ($sales as $sale) {
            $chartData[$targetType]['daily']['current']['data'][] = $sale['net_sales'];
            $chartData[$targetType]['daily']['current']['labels'][] = $sale['date'];
            $chartData[$targetType]['daily']['current']['target'][] = $sale['target'];
        }

        $previousSales = $saleQueries->getDailySalesAndSaleReturns(
            session('admin_company_id'),
            $week,
            $year,
            $locationIds,
            $promoterIds,
            TargetType::getValueByCaseName($targetType),
            $previousDateRange
        );
        foreach ($previousSales as $sale) {
            $chartData[$targetType]['daily']['previous']['data'][] = $sale['net_sales'];
            $chartData[$targetType]['daily']['previous']['labels'][] = $sale['date'];
            $chartData[$targetType]['daily']['previous']['target'][] = $sale['target'];
        }

        return [
            'chartData' => $chartData,
        ];
    }

    public function demandForecasting(): Response
    {
        $skuDemandFile = public_path('files/sku_demand.csv');
        $skuDemandData = [];
        if (false !== ($handle = fopen($skuDemandFile, 'r'))) {
            /** @var array $headers */
            $headers = fgetcsv($handle, 1000, ',');
            while (false !== ($data = fgetcsv($handle, 1000, ','))) {
                $row = array_combine($headers, $data);
                $skuDemandData[] = $row;
            }

            fclose($handle);
        }

        $skuDemandData = collect($skuDemandData);
        $d1 = $skuDemandData->filter(fn ($record): bool => (int) $record['D+1'] > (int) $record['stock']);
        $d3 = $skuDemandData->filter(fn ($record): bool => (int) $record['D+3'] > (int) $record['stock']);
        $d5 = $skuDemandData->filter(fn ($record): bool => (int) $record['D+5'] > (int) $record['stock']);
        $d7 = $skuDemandData->filter(fn ($record): bool => (int) $record['D+7'] > (int) $record['stock']);

        return Inertia::render('DemandForecasting', [
            'accumulatedStaticReportType' => SellThroughTypes::getFormattedArrayForStaticUse(),
            'd1' => $d1->values()->toArray(),
            'd3' => $d3->values()->toArray(),
            'd5' => $d5->values()->toArray(),
            'd7' => $d7->values()->toArray(),
            'lowStockSKU' => $skuDemandData,
        ]);
    }

    public function seasonal(): Response
    {
        $companyId = session('admin_company_id');
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $saleSeasons = $saleSeasonQueries->getWithBasicColumns($companyId);

        $locations->prepend([
            'id' => 0,
            'name' => 'All Locations',
            'code' => '',
        ]);

        $brandQueries = resolve(BrandQueries::class);
        $brands = $brandQueries->getBrands($companyId);

        $brands->prepend([
            'id' => 0,
            'name' => 'All Brands',
        ]);

        return Inertia::render('Seasonal', [
            'locations' => $locations,
            'brands' => $brands,
            'saleSeasons' => $saleSeasons,
        ]);
    }

    public function getSeasonalData(Request $request): array
    {
        $filterData = $request->validate([
            'sale_season_id' => ['required'],
            'location_id' => ['sometimes', 'nullable'],
            'brand_id' => ['sometimes', 'nullable'],
        ]);

        $filterData['location_id'] ??= 0;
        $filterData['brand_id'] ??= 0;

        $companyId = session('admin_company_id');

        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $saleSeason = $saleSeasonQueries->getById((int) $filterData['sale_season_id'], $companyId);

        if (! $saleSeason instanceof SaleSeason) {
            abort(412, 'please Select Proper Sale Seasons');
        }

        $filterData = [
            'start_date' => $saleSeason->start_date,
            'end_date' => $saleSeason->end_date,
            'location_id' => $filterData['location_id'],
            'brand_id' => $filterData['brand_id'],
        ];

        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);
        $storeWiseTotals = $storeWiseDailyTotalQueries->getSaleSeasonalData($filterData, $companyId);

        $totalSalesAmount = $storeWiseTotals->sum('total_sales_amount');
        $totalSalesCount = $storeWiseTotals->sum('total_sales_count');
        $totalUnitsSold = $storeWiseTotals->sum('total_units_sold');
        $totalUnitsReturn = $storeWiseTotals->sum('total_units_return');
        $totalAmountReturn = $storeWiseTotals->sum('total_amount_return');

        $totalSalesAmount += RoundOffConfiguration::roundOffCalculationFor((string) $totalSalesAmount);

        $totalAmountReturn += RoundOffConfiguration::roundOffCalculationFor((string) $totalAmountReturn);

        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleDiscounts = $saleDiscountQueries->getSaleDiscountBasedOnFilterForSaleSeasonalSum($filterData, $companyId);

        $saleDiscounts += RoundOffConfiguration::roundOffCalculationFor((string) $saleDiscounts);

        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleItemDiscounts = $saleItemDiscountQueries->getSaleItemDiscountBasedOnFilterForSaleSeasonalSum(
            $filterData,
            $companyId
        );

        $saleItemDiscounts += RoundOffConfiguration::roundOffCalculationFor((string) $saleItemDiscounts);

        $totalAtv = 0 != $totalSalesCount ? ($totalSalesAmount - $totalAmountReturn) / $totalSalesCount : 0;

        $totalAtv += RoundOffConfiguration::roundOffCalculationFor((string) $totalAtv);

        return [
            'sales' => $totalSalesAmount - $totalAmountReturn,
            'total_receipt' => $totalSalesCount,
            'total_units_sold' => $totalUnitsSold - $totalUnitsReturn,
            'upt' => 0 != $totalSalesCount ? ($totalUnitsSold - $totalUnitsReturn) / $totalSalesCount : 0,
            'atv' => $totalAtv,
            'total_discounts' => $saleDiscounts + $saleItemDiscounts,
        ];
    }

    public function getSeasonalChartData(Request $request): array
    {
        $filterData = $request->validate([
            'sale_season_id' => ['required'],
            'location_id' => ['sometimes', 'nullable'],
            'brand_id' => ['sometimes', 'nullable'],
        ]);

        $filterData['location_id'] ??= 0;
        $filterData['brand_id'] ??= 0;

        $companyId = session('admin_company_id');

        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $saleSeason = $saleSeasonQueries->getById((int) $filterData['sale_season_id'], $companyId);

        if (! $saleSeason instanceof SaleSeason) {
            abort(412, 'please Select Proper Sale Seasons');
        }

        $filterData = [
            'start_date' => $saleSeason->start_date,
            'end_date' => $saleSeason->end_date,
            'location_id' => $filterData['location_id'],
            'brand_id' => $filterData['brand_id'],
        ];

        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);
        $storeWiseTotals = $storeWiseDailyTotalQueries->getSaleSeasonalData($filterData, $companyId);

        $storeWiseTotalsGroupedByBrand = $storeWiseTotals->groupBy('brand_id');
        $storeWiseTotalsGroupedByRegion = $storeWiseTotals->filter(
            fn ($storeWiseTotal): bool => $storeWiseTotal->location && null !== $storeWiseTotal->location->region_id
        )->groupBy('location.region_id');

        $brandChartSales = [];
        $regionChartSales = [];

        foreach ($storeWiseTotalsGroupedByBrand as $brandId => $storeWiseTotal) {
            $sales = $storeWiseTotal->sum('total_sales_amount') - $storeWiseTotal->sum('total_amount_return');
            $brandChartSales[$brandId]['brand_name'] = $storeWiseTotal->first()->brand->name;
            $brandChartSales[$brandId]['sales'] = $sales;
        }

        foreach ($storeWiseTotalsGroupedByRegion as $regionId => $storeWiseTotal) {
            $sales = $storeWiseTotal->sum('total_sales_amount') - $storeWiseTotal->sum('total_amount_return');
            $regionChartSales[$regionId]['store_region_name'] = $storeWiseTotal->first()->location->region->name;
            $regionChartSales[$regionId]['sales'] = $sales;
        }

        /** @var string $jsonData */
        $jsonData = file_get_contents(base_path('/resources/js/common/vendor/corporaColorXkcd.json'));
        $colorCodes = json_decode($jsonData, true);

        $dashboardService = resolve(DashboardService::class);
        $seasonalTopFiveColorChartsData = $dashboardService->getCachedSeasonalTopFiveColorsSalesForChart(
            $filterData,
            $companyId,
            $colorCodes,
            refreshData: false
        );
        $seasonalTopFiveCategoryChartsData = $dashboardService->getCachedSeasonalTopFiveCategorySalesForChart(
            $filterData,
            $companyId,
            refreshData: false
        );
        $seasonalTopFiveStyleChartsData = $dashboardService->getCachedSeasonalTopFiveStyleSalesForChart(
            $filterData,
            $companyId,
            refreshData: false
        );
        $seasonalTopFiveDepartmentChartsData = $dashboardService->getCachedSeasonalTopFiveDepartmentSalesForChart(
            $filterData,
            $companyId,
            refreshData: false
        );
        $seasonalTopFiveColorGroupChartsData = $dashboardService->getCachedSeasonalTopFiveColorGroupSalesForChart(
            $filterData,
            $companyId,
            $colorCodes,
            refreshData: false
        );
        $seasonalTopFiveSizeChartsData = $dashboardService->getCachedSeasonalTopFiveSizeSalesForChart(
            $filterData,
            $companyId,
            refreshData: false
        );
        $seasonalByWeekColorChartsData = $dashboardService->getCachedWeekDistributionColorForChart(
            $filterData,
            $companyId,
            refreshData: false
        );
        $seasonalStockWithSizeChartsData = $dashboardService->getCachedStockWithSizeForChart(
            $filterData,
            $companyId,
            refreshData: false
        );

        $weeklySalesAndCounts = [];
        $storeWiseSalesAndCounts = [];

        foreach ($storeWiseTotals as $storeTotal) {
            $location = $storeTotal->location;
            $storeTotalDate = $storeTotal->date;
            $weekStartDate = Carbon::parse($storeTotalDate)->startOfWeek()->format('Y-m-d');

            $weeklySalesAndCounts[$weekStartDate] ??= [
                'totalAmount' => 0,
                'totalCount' => 0,
            ];
            $storeWiseSalesAndCounts[$storeTotal->location_id] ??= [
                'totalAmount' => 0,
                'totalCount' => 0,
            ];

            $weeklySalesAndCounts[$weekStartDate]['totalAmount'] += $storeTotal->total_sales_amount;
            $weeklySalesAndCounts[$weekStartDate]['totalCount'] += $storeTotal->total_sales_count;

            $storeWiseSalesAndCounts[$storeTotal->location_id]['totalAmount'] += $storeTotal->total_sales_amount;
            $storeWiseSalesAndCounts[$storeTotal->location_id]['totalCount'] += $storeTotal->total_sales_count;

            if (! isset($storeWiseSalesAndCounts[$storeTotal->location_id]['location'])) {
                $storeWiseSalesAndCounts[$storeTotal->location_id]['location'] = $location->name;
                $storeWiseSalesAndCounts[$storeTotal->location_id]['location_code'] = $location->code;
            }
        }

        ksort($weeklySalesAndCounts);
        $count = 1;
        $weeklySalesAndCounts = array_map(function (array $entry) use (&$count): array {
            $entry['week'] = 'Week ' . $count;
            $count++;

            return $entry;
        }, $weeklySalesAndCounts);

        $weeklySalesAndCountsChartData = [
            'data' => [
                [
                    'data' => array_column($weeklySalesAndCounts, 'totalAmount'),
                    'name' => 'Sales',
                    'type' => 'bar',
                ],
                [
                    'data' => array_column($weeklySalesAndCounts, 'totalCount'),
                    'name' => 'Orders',
                    'type' => 'bar',
                ],
            ],
            'labels' => array_column($weeklySalesAndCounts, 'week'),
            'legendData' => ['Sales', 'Orders'],
        ];

        $storeWiseSalesAndCounts = collect($storeWiseSalesAndCounts)
            ->sortByDesc('totalAmount')
            ->take(10)
            ->toArray();

        $storeWiseSalesAndCountsChartData = [
            'data' => [
                [
                    'data' => array_column($storeWiseSalesAndCounts, 'totalAmount'),
                    'name' => 'Sales',
                    'type' => 'bar',
                ],
                [
                    'data' => array_column($storeWiseSalesAndCounts, 'totalCount'),
                    'name' => 'Orders',
                    'type' => 'bar',
                ],
            ],
            'labels' => array_column($storeWiseSalesAndCounts, 'location'),
            'code_based_labels' => array_column($storeWiseSalesAndCounts, 'location_code'),
            'legendData' => ['Sales', 'Orders'],
        ];

        return [
            'brand_wise_chart_data' => [
                'labels' => array_column($brandChartSales, 'brand_name'),
                'data' => array_column($brandChartSales, 'sales'),
            ],
            'region_wise_chart_data' => [
                'labels' => array_column($regionChartSales, 'store_region_name'),
                'data' => array_column($regionChartSales, 'sales'),
            ],
            'color_top_five_chart' => $seasonalTopFiveColorChartsData,
            'category_top_five_chart' => $seasonalTopFiveCategoryChartsData,
            'style_top_five_chart' => $seasonalTopFiveStyleChartsData,
            'department_top_five_chart' => $seasonalTopFiveDepartmentChartsData,
            'color_group_top_five_chart' => $seasonalTopFiveColorGroupChartsData,
            'size_top_five_chart' => $seasonalTopFiveSizeChartsData,
            'week_based_color_chart' => $seasonalByWeekColorChartsData,
            'stock_with_size_chart' => $seasonalStockWithSizeChartsData,
            'sale_week_wise_chart_data' => $weeklySalesAndCountsChartData,
            'sale_store_wise_chart_data' => $storeWiseSalesAndCountsChartData,
        ];
    }

    public function getSeasonalTotalDiscounts(Request $request): array
    {
        $filterData = $request->validate([
            'sale_season_id' => ['required'],
            'location_id' => ['sometimes', 'nullable'],
            'brand_id' => ['sometimes', 'nullable'],
        ]);

        $filterData['location_id'] ??= 0;
        $filterData['brand_id'] ??= 0;

        $companyId = session('admin_company_id');

        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $saleSeason = $saleSeasonQueries->getById((int) $filterData['sale_season_id'], $companyId);

        if (! $saleSeason instanceof SaleSeason) {
            abort(412, 'please Select Proper Sale Seasons');
        }

        $filterData = [
            'start_date' => $saleSeason->start_date,
            'end_date' => $saleSeason->end_date,
            'location_id' => $filterData['location_id'],
            'brand_id' => $filterData['brand_id'],
        ];
        $totalDiscounts = [];

        $saleDiscountQueries = resolve(SaleDiscountQueries::class);
        $saleDiscounts = $saleDiscountQueries->getSaleDiscountBasedOnFilterForSaleSeasonal($filterData, $companyId);

        $saleItemDiscountQueries = resolve(SaleItemDiscountQueries::class);
        $saleItemDiscounts = $saleItemDiscountQueries->getSaleItemDiscountBasedOnFilterForSaleSeasonal(
            $filterData,
            $companyId
        );

        $discounts = $saleDiscounts->merge($saleItemDiscounts);

        foreach ($discounts->groupBy('discountable_type') as $discountableType => $discountGroups) {
            $totalDiscounts[$discountableType]['title'] = CommonFunctions::stringTitleLowerCase(
                $discountGroups->first()->discountable_type
            );
            $totalDiscounts[$discountableType]['amount'] = $discountGroups->sum(
                'amount'
            ) + RoundOffConfiguration::roundOffCalculationFor((string) $discountGroups->sum('amount'));
            $totalDiscounts[$discountableType]['usages'] = $discountGroups->count();
            $totalDiscounts[$discountableType]['sub_details'] = $this->prepareSubDetails(
                $discountGroups->where('discountable_type', $discountGroups->first()->discountable_type),
                $discountableType
            );
        }

        return [
            'discounts' => array_values($totalDiscounts),
        ];
    }

    public function getSeasonalComparisonData(Request $request): array
    {
        $filterData = $request->validate([
            'sale_season_id' => ['required'],
            'location_id' => ['sometimes', 'nullable'],
            'brand_id' => ['sometimes', 'nullable'],
            'comparison_x_sale_season_id' => ['required', 'nullable'],
            'comparison_y_sale_season_id' => ['required', 'nullable'],
        ]);

        $companyId = session('admin_company_id');

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $xSaleSeason = $saleSeasonQueries->getById((int) $filterData['comparison_x_sale_season_id'], $companyId);
        $ySaleSeason = $saleSeasonQueries->getById((int) $filterData['comparison_y_sale_season_id'], $companyId);

        $xStoreWiseTotals = $this->getSaleSeasonalChartData($xSaleSeason, $filterData, $companyId);
        $yStoreWiseTotals = $this->getSaleSeasonalChartData($ySaleSeason, $filterData, $companyId);

        $xTotalSalesAmount = $xStoreWiseTotals->sum('total_sales_amount') - $xStoreWiseTotals->sum(
            'total_amount_return'
        );
        $xTotalSalesAmount += RoundOffConfiguration::roundOffCalculationFor((string) $xTotalSalesAmount);

        $yTotalSalesAmount = $yStoreWiseTotals->sum('total_sales_amount') - $yStoreWiseTotals->sum(
            'total_amount_return'
        );
        $yTotalSalesAmount += RoundOffConfiguration::roundOffCalculationFor((string) $yTotalSalesAmount);

        $xTotalUnitSold = $xStoreWiseTotals->sum('total_units_sold') - $xStoreWiseTotals->sum('total_units_return');
        $yTotalUnitSold = $yStoreWiseTotals->sum('total_units_sold') - $yStoreWiseTotals->sum('total_units_return');
        $xUpt = $xStoreWiseTotals->sum('total_sales_count') !== 0 ? CommonFunctions::numberFormat(
            ($xStoreWiseTotals->sum('total_units_sold') - $xStoreWiseTotals->sum(
                'total_units_return'
            )) / $xStoreWiseTotals->sum('total_sales_count')
        ) : 0;
        $yUpt = $yStoreWiseTotals->sum('total_sales_count') !== 0 ? CommonFunctions::numberFormat(
            ($yStoreWiseTotals->sum('total_units_sold') - $yStoreWiseTotals->sum(
                'total_units_return'
            )) / $yStoreWiseTotals->sum('total_sales_count')
        ) : 0;
        $xAtv = $xStoreWiseTotals->sum('total_sales_count') !== 0 ? CommonFunctions::numberFormat(
            $xTotalSalesAmount / $xStoreWiseTotals->sum('total_sales_count')
        ) : 0;
        $yAtv = $yStoreWiseTotals->sum('total_sales_count') !== 0 ? CommonFunctions::numberFormat(
            $yTotalSalesAmount / $yStoreWiseTotals->sum('total_sales_count')
        ) : 0;

        $xAtv += RoundOffConfiguration::roundOffCalculationFor((string) $xAtv);
        $yAtv += RoundOffConfiguration::roundOffCalculationFor((string) $yAtv);

        $storeWiseData = [
            [
                'title' => 'Sales',
                'x' => CommonFunctions::currencySymbolDisplayWithAmount(
                    $currency->getSymbol(),
                    $xTotalSalesAmount,
                    false,
                    2,
                    true
                ),
                'y' => CommonFunctions::currencySymbolDisplayWithAmount(
                    $currency->getSymbol(),
                    $yTotalSalesAmount,
                    false,
                    2,
                    true
                ),
                'performance' => $xTotalSalesAmount > 0 ? CommonFunctions::numberFormat(
                    (($yTotalSalesAmount - $xTotalSalesAmount) * 100) / $xTotalSalesAmount
                ) : 0,
            ],
            [
                'title' => 'Orders',
                'x' => $xStoreWiseTotals->sum('total_sales_count'),
                'y' => $yStoreWiseTotals->sum('total_sales_count'),
                'performance' => $xStoreWiseTotals->sum('total_sales_count') !== 0 ? CommonFunctions::numberFormat(
                    (($yStoreWiseTotals->sum('total_sales_count') - $xStoreWiseTotals->sum(
                        'total_sales_count'
                    )) * 100) / $xStoreWiseTotals->sum('total_sales_count')
                ) : 0,
            ],
            [
                'title' => 'Units Sold',
                'x' => $xStoreWiseTotals->sum('total_units_sold') - $xStoreWiseTotals->sum('total_units_return'),
                'y' => $yStoreWiseTotals->sum('total_units_sold') - $yStoreWiseTotals->sum('total_units_return'),
                'performance' => 0 !== $xTotalUnitSold ? CommonFunctions::numberFormat(
                    (($yTotalUnitSold - $xTotalUnitSold) * 100) / $xTotalUnitSold
                ) : 0,
            ],
            [
                'title' => 'UPT',
                'x' => $xUpt,
                'y' => $yUpt,
                'performance' => 0 !== $xUpt ? CommonFunctions::numberFormat((($yUpt - $xUpt) * 100) / $xUpt) : 0,
            ],
            [
                'title' => 'ATV',
                'x' => $xAtv,
                'y' => $yAtv,
                'performance' => $xAtv > 0 ? CommonFunctions::numberFormat((($yAtv - $xAtv) * 100) / $xAtv) : 0,
            ],
        ];

        $xStoreWiseTotalsGroupedByBrand = $xStoreWiseTotals->groupBy('brand_id');
        $yStoreWiseTotalsGroupedByBrand = $yStoreWiseTotals->groupBy('brand_id');

        $xBrandChartSales = [];

        foreach ($xStoreWiseTotalsGroupedByBrand as $storeWiseTotal) {
            $sales = $storeWiseTotal->sum('total_sales_amount') - $storeWiseTotal->sum('total_amount_return');

            $xBrandChartSales[0]['name'] = $xSaleSeason->name;
            $xBrandChartSales[0]['type'] = 'bar';
            $xBrandChartSales[0]['data'][] = $sales;
        }

        foreach ($yStoreWiseTotalsGroupedByBrand as $storeWiseTotal) {
            $sales = $storeWiseTotal->sum('total_sales_amount') - $storeWiseTotal->sum('total_amount_return');
            $xBrandChartSales[1]['name'] = $ySaleSeason->name;
            $xBrandChartSales[1]['type'] = 'bar';
            $xBrandChartSales[1]['data'][] = $sales;
        }

        return [
            'comparisonChartData' => [
                'data' => $xBrandChartSales,
                'legendData' => collect($xBrandChartSales)->pluck('name')->toArray(),
                'labels' => $xStoreWiseTotals->pluck('brand.name')->unique()->values()->toArray(),
            ],
            'comparisonData' => $storeWiseData,
        ];
    }

    public function getSeasonalMemberComparisonData(Request $request): array
    {
        $filterData = $request->validate([
            'sale_season_id' => ['required'],
            'location_id' => ['sometimes', 'nullable'],
            'brand_id' => ['sometimes', 'nullable'],
            'comparison_x_sale_season_id' => ['required', 'nullable'],
            'comparison_y_sale_season_id' => ['required', 'nullable'],
        ]);

        $companyId = session('admin_company_id');

        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $xSaleSeason = $saleSeasonQueries->getById((int) $filterData['comparison_x_sale_season_id'], $companyId);
        $ySaleSeason = $saleSeasonQueries->getById((int) $filterData['comparison_y_sale_season_id'], $companyId);

        $xMemberData = $this->getSeasonalMemberData($xSaleSeason, $filterData, $companyId);
        $yMemberData = $this->getSeasonalMemberData($ySaleSeason, $filterData, $companyId);

        $memberChart = [];

        $seasons = [$xSaleSeason, $ySaleSeason];

        foreach ($seasons as $index => $season) {
            $memberData = (0 == $index) ? $xMemberData : $yMemberData;

            foreach ($memberData as $member) {
                $date = $member->date;
                $weekStartDate = Carbon::parse($date)->startOfWeek()->format('Y-m-d');

                if (! isset($memberChart[$index])) {
                    $memberChart[$index] = [
                        'name' => $season->name,
                        'type' => 'line',
                        'data' => [],
                    ];
                }

                if (isset($memberChart[$index]['data'][$weekStartDate])) {
                    $memberChart[$index]['data'][$weekStartDate] += $member->members_count;
                } else {
                    $memberChart[$index]['data'][$weekStartDate] = $member->members_count;
                }
            }
        }

        foreach ($memberChart as &$chart) {
            $chart['data'] = array_values($chart['data']);
        }

        $weeks = [];
        if ([] !== $memberChart) {
            $maxLength = max(array_map('count', array_column($memberChart, 'data')));
            $weeks = array_map(fn ($week): string => 'Week ' . $week, range(1, $maxLength));
        }

        return [
            'comparisonSeasonalMemberChartData' => [
                'data' => $memberChart,
                'legendData' => collect($memberChart)->pluck('name')->toArray(),
                'labels' => $weeks,
            ],
        ];
    }

    private function getSeasonalMemberData(SaleSeason $saleSeason, array $filterData, int $companyId): Collection
    {
        $filterData = [
            'start_date' => $saleSeason->start_date,
            'end_date' => $saleSeason->end_date,
            'location_id' => $filterData['location_id'],
            'brand_id' => $filterData['brand_id'],
        ];

        $memberQueries = resolve(MemberQueries::class);

        return $memberQueries->getSeasonalMemberData($filterData, $companyId);
    }

    public function getSeasonalSalesComparisonData(Request $request): array
    {
        $filterData = $request->validate([
            'sale_season_id' => ['required'],
            'location_id' => ['sometimes', 'nullable'],
            'brand_id' => ['sometimes', 'nullable'],
            'comparison_x_sale_season_id' => ['sometimes', 'nullable'],
            'comparison_y_sale_season_id' => ['sometimes', 'nullable'],
        ]);

        $companyId = session('admin_company_id');

        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $xSaleSeason = $saleSeasonQueries->getById((int) $filterData['comparison_x_sale_season_id'], $companyId);
        $ySaleSeason = $saleSeasonQueries->getById((int) $filterData['comparison_y_sale_season_id'], $companyId);

        $yStoreWiseTotals = $this->getAnalyticsBeforeTenDaysOfSeasonalData($ySaleSeason, $filterData, $companyId);
        $xStoreWiseTotals = $this->getAnalyticsBeforeTenDaysOfSeasonalData($xSaleSeason, $filterData, $companyId);

        $xBrandChartSales = [];

        foreach ($xStoreWiseTotals as $storeWiseTotal) {
            $sales = $storeWiseTotal->total_sales_amount - $storeWiseTotal->total_amount_return;

            $xBrandChartSales[0]['name'] = $xSaleSeason->name;
            $xBrandChartSales[0]['type'] = 'line';
            $xBrandChartSales[0]['data'][] = $sales;
        }

        foreach ($yStoreWiseTotals as $storeWiseTotal) {
            $sales = $storeWiseTotal->total_sales_amount - $storeWiseTotal->total_amount_return;
            $xBrandChartSales[1]['name'] = $ySaleSeason->name;
            $xBrandChartSales[1]['type'] = 'line';
            $xBrandChartSales[1]['data'][] = $sales;
        }

        return [
            'comparisonSeasonalSalesChartData' => [
                'data' => $xBrandChartSales,
                'legendData' => collect($xBrandChartSales)->pluck('name')->toArray(),
                'labels' => ['D-1', 'D-2', 'D-3', 'D-4', 'D-5', 'D-6', 'D-7', 'D-8', 'D-9', 'D-10'],
            ],
        ];
    }

    public function getSeasonalSalesComparisonChartData(Request $request): array
    {
        $filterData = $request->validate([
            'sale_season_id' => ['required'],
            'location_id' => ['sometimes', 'nullable'],
            'brand_id' => ['sometimes', 'nullable'],
            'comparison_x_sale_season_id' => ['sometimes', 'nullable'],
            'comparison_y_sale_season_id' => ['sometimes', 'nullable'],
        ]);

        $companyId = session('admin_company_id');

        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $xSaleSeason = $saleSeasonQueries->getById((int) $filterData['comparison_x_sale_season_id'], $companyId);
        $ySaleSeason = $saleSeasonQueries->getById((int) $filterData['comparison_y_sale_season_id'], $companyId);

        $filterData = [
            'x_start_date' => $xSaleSeason->start_date,
            'x_end_date' => $xSaleSeason->end_date,
            'y_start_date' => $ySaleSeason->start_date,
            'y_end_date' => $ySaleSeason->end_date,
            'location_id' => $filterData['location_id'],
            'brand_id' => $filterData['brand_id'],
            'x_sale_season_name' => $xSaleSeason->name,
            'y_sale_season_name' => $ySaleSeason->name,
        ];

        $dashboardService = resolve(DashboardService::class);
        $seasonalTopFiveColorChartsData = $dashboardService->getCachedSeasonalTopFiveColorsSalesForChartComparison(
            $filterData,
            $companyId,
            refreshData: false
        );
        $seasonalTopFiveCategoryChartsData = $dashboardService->getCachedSeasonalTopFiveCategorySalesForChartComparison(
            $filterData,
            $companyId,
            refreshData: false
        );
        $seasonalTopFiveStyleChartsData = $dashboardService->getCachedSeasonalTopFiveStyleSalesForChartComparison(
            $filterData,
            $companyId,
            refreshData: false
        );
        $seasonalTopFiveDepartmentChartsData = $dashboardService->getCachedSeasonalTopFiveDepartmentSalesForChartComparison(
            $filterData,
            $companyId,
            refreshData: false
        );
        $seasonalTopFiveColorGroupChartsData = $dashboardService->getCachedSeasonalTopFiveColorGroupSalesForChartComparison(
            $filterData,
            $companyId,
            refreshData: false
        );
        $seasonalTopFiveSizeChartsData = $dashboardService->getCachedSeasonalTopFiveSizeSalesForChartComparison(
            $filterData,
            $companyId,
            refreshData: false
        );

        return [
            'comparison_color_top_five_chart' => $seasonalTopFiveColorChartsData,
            'comparison_category_top_five_chart' => $seasonalTopFiveCategoryChartsData,
            'comparison_style_top_five_chart' => $seasonalTopFiveStyleChartsData,
            'comparison_department_top_five_chart' => $seasonalTopFiveDepartmentChartsData,
            'comparison_color_group_top_five_chart' => $seasonalTopFiveColorGroupChartsData,
            'comparison_size_top_five_chart' => $seasonalTopFiveSizeChartsData,
        ];
    }

    public function printRevenueViewStoresSales(Request $request): string
    {
        $date = $request->input('date');
        $refresh = false;

        $brandId = (int) $request->input('brand_id');
        $companyId = session('admin_company_id');

        $brandName = 'All';
        if (0 !== $brandId) {
            $brandQueries = resolve(BrandQueries::class);
            $brand = $brandQueries->getById($brandId);
            $brandName = $brand->name;
        }

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        [$totalSalesByLocations, $salesTotalData] = $this->getSalesDataForChart($brandId, $date, $refresh);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        return view('prints.revenue_dashboard_store_sales_details', [
            'locationSales' => $totalSalesByLocations,
            'salesTotalData' => $salesTotalData,
            'company' => $company,
            'brandName' => $brandName,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
            'dateRange' => $date,
            'currencySymbol' => $currency->getSymbol(),
        ])->render();
    }

    public function printStoreRevenue(Request $request): string
    {
        $date = $request->input('date');
        [$data, $totalData, $brandName, $company, $filterType, $locationName, $currencySymbol] = $this->getDataForExportAndPdf(
            $request
        );

        return view('prints.product_dashboard_store_sales_details', [
            'salesData' => $data,
            'totalData' => $totalData,
            'filterType' => $filterType,
            'selectedDate' => $date,
            'company' => $company,
            'brandName' => $brandName,
            'locationName' => $locationName,
            'currencySymbol' => $currencySymbol,
            'date' => Carbon::now()->format('d-m-Y D h:s:i A'),
        ])->render();
    }

    public function exportStoreRevenue(string $filename, Request $request): BinaryFileResponse
    {
        [$data, $totalData, $brandName, $company, $filterType, $locationName, $currencySymbol] = $this->getDataForExportAndPdf(
            $request
        );

        return Excel::download(
            new ProductStoresSalesDetailsExport(
                $data,
                $totalData,
                $brandName,
                $company,
                $filterType,
                $locationName,
                $currencySymbol,
                $request->get('date'),
            ),
            $filename
        );
    }

    public function getDataForExportAndPdf(Request $request): array
    {
        $date = $request->input('date');
        $refresh = false;

        $brandId = (int) $request->input('brand_id');
        $companyId = session('admin_company_id');
        $locationId = (int) $request->input('location_id');
        $type = (int) $request->input('type');

        $filterType = StoreRevenueDashboardTableFilterTypes::getFormattedCaseName($type);

        $brandName = 'All';
        if (0 !== $brandId) {
            $brandQueries = resolve(BrandQueries::class);
            $brand = $brandQueries->getById($brandId);
            $brandName = $brand->name;
        }

        $locationName = 'All';
        if (0 !== $locationId) {
            $locationQueries = resolve(LocationQueries::class);
            $location = $locationQueries->getById($locationId, $companyId, LocationTypes::STORE->value);
            $locationName = $location->name;
        }

        $dashboardService = resolve(DashboardService::class);
        $data = [];
        $totalData = [];
        if (StoreRevenueDashboardTableFilterTypes::CATEGORIES->value === $type) {
            [$data, $totalData] = $dashboardService->getCachedCategoriesSalesForChart(
                $companyId,
                $locationId,
                $brandId,
                $date,
                $refresh
            );
        }

        if (StoreRevenueDashboardTableFilterTypes::COLORS->value === $type) {
            [$data, $totalData] = $dashboardService->getCachedColorsSalesForChart(
                $companyId,
                $locationId,
                $brandId,
                $date,
                $refresh
            );
        }

        if (StoreRevenueDashboardTableFilterTypes::BRANDS->value === $type) {
            [$data, $totalData] = $dashboardService->getCachedBrandsSalesForChart(
                $companyId,
                $locationId,
                $brandId,
                $date,
                $refresh
            );
        }

        if (StoreRevenueDashboardTableFilterTypes::DEPARTMENTS->value === $type) {
            [$data, $totalData] = $dashboardService->getCachedDepartmentSaleForChart(
                $companyId,
                $locationId,
                $brandId,
                $date,
                $refresh,
            );
        }

        if (StoreRevenueDashboardTableFilterTypes::COLOR_GROUPS->value === $type) {
            [$data, $totalData] = $dashboardService->getCachedColorGroupSalesForChart(
                $companyId,
                $locationId,
                $brandId,
                $date,
                $refresh
            );
        }

        if (StoreRevenueDashboardTableFilterTypes::SIZES->value === $type) {
            [$data, $totalData] = $dashboardService->getCachedSizeSalesForChart(
                $companyId,
                $locationId,
                $brandId,
                $date,
                $refresh
            );
        }

        if (StoreRevenueDashboardTableFilterTypes::STYLES->value === $type) {
            [$data, $totalData] = $dashboardService->getCachedStyleSalesForChart(
                $companyId,
                $locationId,
                $brandId,
                $date,
                $refresh
            );
        }

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        return [$data, $totalData, $brandName, $company, $filterType, $locationName, $currency->getSymbol()];
    }

    public function exportRevenueStoresSales(string $filename, Request $request): BinaryFileResponse
    {
        $date = $request->input('date');
        $refresh = false;

        $companyId = session('admin_company_id');
        $brandId = (int) $request->input('brand_id');

        [$totalSalesByLocations, $salesTotalData] = $this->getSalesDataForChart($brandId, $date, $refresh);

        $companyQueries = resolve(CompanyQueries::class);
        $company = $companyQueries->getNameAndCodeById($companyId);

        $brandName = 'All';
        if (0 !== $brandId) {
            $brandQueries = resolve(BrandQueries::class);
            $brand = $brandQueries->getById($brandId);
            $brandName = $brand->name;
        }

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        return Excel::download(
            new RevenueStoresSalesDetailsExport(
                $totalSalesByLocations,
                $salesTotalData,
                $brandName,
                $company,
                $currency->getSymbol(),
                $date
            ),
            $filename
        );
    }

    public function basketAnalysis(): Response
    {
        $companyId = session('admin_company_id');
        $productQueries = resolve(ProductQueries::class);
        $locationQueries = resolve(LocationQueries::class);

        $basketAnalysisFile = public_path('files/basket_analysis.csv');
        $basketAnalysisData = [];
        if (false !== ($handle = fopen($basketAnalysisFile, 'r'))) {
            /** @var array $headers */
            $headers = fgetcsv($handle, 1000, ',');
            while (false !== ($data = fgetcsv($handle, 1000, ','))) {
                $row = array_combine($headers, $data);
                $basketAnalysisData[] = $row;
            }

            fclose($handle);
        }

        $productIds = array_unique(
            array_merge(array_column($basketAnalysisData, 'product_id'), array_column(
                $basketAnalysisData,
                'comparison_product_id'
            ))
        );
        $locationIds = array_unique(array_column($basketAnalysisData, 'location_id'));

        $productNames = $productQueries->getIdAndNameByIds($productIds, $companyId);
        $locations = $locationQueries->getIdAndNameByIds($locationIds, $companyId);

        foreach ($basketAnalysisData as $key => $item) {
            $product = $productNames->firstWhere('id', $item['product_id']);
            if ($product) {
                $basketAnalysisData[$key]['product_name'] = $product->name;
            }

            $comparisonProduct = $productNames->firstWhere('id', $item['comparison_product_id']);
            if ($comparisonProduct) {
                $basketAnalysisData[$key]['comparison_product_name'] = $comparisonProduct->name;
            }
        }

        $basketAnalysisData = collect($basketAnalysisData);
        $basketAnalysisData = $basketAnalysisData->groupBy('location_id');

        return Inertia::render('BasketAnalysis', [
            'basketAnalysisData' => $basketAnalysisData->toArray(),
            'locations' => $locations,
            'products' => $productNames,
        ]);
    }

    public function dataAnalysis(): Response
    {
        return Inertia::render('DataAnalysis');
    }

    private function prepareSubDetails(Collection $discountable, string $discountableType): array
    {
        $discountable = $discountable->whereNotNull('discountable');

        $records = [];
        if ($discountableType === ModelMapping::VOUCHER->name) {
            foreach ($discountable->groupBy('discountable.voucher_configuration_id') as $saleDiscounts) {
                $records[] = [
                    'title' => 'Vouchers',
                    'usages' => $saleDiscounts->count(),
                    'amount' => $saleDiscounts->sum('amount'),
                ];
            }
        }

        if ($discountableType === ModelMapping::SALE_PRICE_OVERRIDE->name) {
            foreach ($discountable->groupBy('discountable.negotiator_type') as $saleDiscounts) {
                $records[] = [
                    'title' => 'Cart Level Price Override',
                    'usages' => $saleDiscounts->count(),
                    'amount' => $saleDiscounts->sum('amount'),
                ];
            }
        }

        if ($discountableType === ModelMapping::SALE_ITEM_PRICE_OVERRIDE->name) {
            foreach ($discountable->groupBy('discountable.negotiator_type') as $saleDiscounts) {
                $records[] = [
                    'title' => 'Item Level Price Override',
                    'usages' => $saleDiscounts->count(),
                    'amount' => $saleDiscounts->sum('amount'),
                ];
            }
        }

        if ($discountableType === ModelMapping::PROMOTION->name) {
            foreach ($discountable->groupBy('discountable.name') as $saleDiscounts) {
                $records[] = [
                    'title' => 'Promotions',
                    'usages' => $saleDiscounts->count(),
                    'amount' => $saleDiscounts->sum('amount'),
                ];
            }
        }

        if ($discountableType === ModelMapping::COMPLIMENTARY_ITEM_REASON->name) {
            foreach ($discountable->groupBy('discountable.reason') as $saleDiscounts) {
                $records[] = [
                    'title' => 'Complimentary',
                    'usages' => $saleDiscounts->count(),
                    'amount' => $saleDiscounts->sum('amount'),
                ];
            }
        }

        if ($discountableType === ModelMapping::DREAM_PRICE->name) {
            foreach ($discountable->groupBy('discountable.name') as $saleDiscounts) {
                $records[] = [
                    'title' => 'Price Markdown',
                    'usages' => $saleDiscounts->count(),
                    'amount' => $saleDiscounts->sum('amount'),
                ];
            }
        }

        return $records;
    }

    private function getAnalyticsBeforeTenDaysOfSeasonalData(
        SaleSeason $saleSeason,
        array $filterData,
        int $companyId,
    ): Collection {
        /** @var Carbon $startDate */
        $startDate = Carbon::createFromFormat('Y-m-d', $saleSeason->start_date);

        $filterData = [
            'start_date' => $startDate->subDays(10)->format('Y-m-d'),
            'end_date' => $saleSeason->start_date,
            'location_id' => $filterData['location_id'],
            'brand_id' => $filterData['brand_id'],
        ];

        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);

        return $storeWiseDailyTotalQueries->getAnalyticsForLastTenDaysOfSeasonalData($filterData, $companyId);
    }

    private function getSaleSeasonalChartData(SaleSeason $saleSeason, array $filterData, int $companyId): Collection
    {
        $filterData = [
            'start_date' => $saleSeason->start_date,
            'end_date' => $saleSeason->end_date,
            'location_id' => $filterData['location_id'],
            'brand_id' => $filterData['brand_id'],
        ];

        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);

        return $storeWiseDailyTotalQueries->getSaleSeasonalData($filterData, $companyId);
    }

    public function memberDashboardIndex(): Response
    {
        $companyId = session('admin_company_id');
        $locationQueries = resolve(LocationQueries::class);
        $locations = $locationQueries->getStoreWithBasicColumns($companyId);

        $locations->prepend([
            'id' => 0,
            'name' => 'All Locations',
            'code' => '',
        ]);

        return Inertia::render('Member', [
            'locations' => $locations,
        ]);
    }

    public function getMemberCountDetails(Request $request): array
    {
        $date = Carbon::now();
        $companyId = session('admin_company_id');
        $memberQueries = resolve(MemberQueries::class);

        $filterData = $request->validate([
            'location_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $locationId = array_key_exists('location_id', $filterData) ? (int) $filterData['location_id'] : 0;

        $todayMemberCounts = $memberQueries->getTodayMemberCount($companyId, $date->format('Y-m-d'), $locationId);
        $thisWeekMemberCounts = $memberQueries->getThisWeekMemberCount(
            [$date->copy()->startOfWeek()->format('Y-m-d H:i:s'), $date->copy()->endOfWeek()->format('Y-m-d H:i:s')],
            $companyId,
            $locationId
        );
        $thisMonthMemberCounts = $memberQueries->getThisMonthMemberCount(
            [$date->copy()->startOfMonth()->format('Y-m-d H:i:s'), $date->copy()->endOfMonth()->format('Y-m-d H:i:s')],
            $companyId,
            $locationId
        );
        $thisYearMemberCounts = $memberQueries->getThisYearMemberCount(
            (int) $date->copy()->format('Y'),
            $companyId,
            $locationId
        );
        $lastYearMemberCounts = $memberQueries->getLastYearMemberCount(
            (int) $date->copy()->subYear()->format('Y'),
            $companyId,
            $locationId
        );

        return [
            'member_details' => [
                'today_member_counts' => [
                    'label' => 'Today',
                    'value' => $todayMemberCounts,
                ],
                'this_week_member_counts' => [
                    'label' => 'This Week',
                    'value' => $thisWeekMemberCounts,
                ],
                'this_month_member_counts' => [
                    'label' => 'This Month',
                    'value' => $thisMonthMemberCounts,
                ],
                'this_year_member_counts' => [
                    'label' => 'This Year',
                    'value' => $thisYearMemberCounts,
                ],
                'last_year_member_counts' => [
                    'label' => 'Last Year',
                    'value' => $lastYearMemberCounts,
                ],
            ],
        ];
    }

    public function getNewAndExistingMemberInChartData(Request $request): array
    {
        $currentYear = (int) Carbon::now()->format('Y');
        $companyId = session('admin_company_id');
        $memberQueries = resolve(MemberQueries::class);

        $filterData = $request->validate([
            'location_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $salesWithMember = $memberQueries->getNewAndExistingMembers(
            $currentYear,
            $companyId,
            array_key_exists('location_id', $filterData) ? (int) $filterData['location_id'] : 0
        );

        $salesWithMember->transform(fn ($member): array => [
            'month' => $member['month'],
            'new_members' => (int) $member['new_members'],
            'existing_members' => (int) $member['existing_members'],
        ]);

        $labels = $salesWithMember->pluck('month')->toArray();
        $newMemberMonthWiseData = $salesWithMember->pluck('new_members')->toArray();
        $existingMemberMonthWiseData = $salesWithMember->pluck('existing_members')->toArray();

        return [
            'member_details' => [
                'labels' => $labels,
                'new_member_month_wise_data' => $newMemberMonthWiseData,
                'existing_member_month_wise_data' => $existingMemberMonthWiseData,
            ],
        ];
    }

    public function getMemberAgeGroupDetails(Request $request): array
    {
        $currentYear = (int) Carbon::now()->format('Y');
        $companyId = session('admin_company_id');
        $saleQueries = resolve(SaleQueries::class);

        $filterData = $request->validate([
            'location_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $memberAgeGroupDetails = $saleQueries->getMemberAgeGroupCounts(
            $currentYear,
            $companyId,
            array_key_exists('location_id', $filterData) ? (int) $filterData['location_id'] : 0
        );

        return [
            'age_group_details' => $memberAgeGroupDetails->toArray(),
        ];
    }

    public function getMemberGenderDetails(Request $request): array
    {
        $currentYear = (int) Carbon::now()->format('Y');
        $companyId = session('admin_company_id');
        $saleQueries = resolve(SaleQueries::class);

        $filterData = $request->validate([
            'location_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $memberWithGenderDetails = $saleQueries->getMemberGender(
            $currentYear,
            $companyId,
            array_key_exists('location_id', $filterData) ? (int) $filterData['location_id'] : 0
        );

        return [
            'gender_details' => $memberWithGenderDetails,
        ];
    }

    public function getTopTenMembersByYear(Request $request): array
    {
        $companyId = session('admin_company_id');

        $filterData = $request->validate([
            'location_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $memberQueries = resolve(MemberQueries::class);
        $thisYearTopSellingMembers = $memberQueries->topTenSellingMembers(
            $companyId,
            array_key_exists('location_id', $filterData) ? (int) $filterData['location_id'] : 0,
            now()->startOfYear()->format('Y-m-d'),
            now()->format('Y-m-d'),
        );

        return [
            'top_ten_members_by_year' => DashboardStockOverviewTopSellingMemberResource::collection(
                $thisYearTopSellingMembers
            ),
        ];
    }

    public function getTopTenMembersByMonth(Request $request): array
    {
        $companyId = session('admin_company_id');

        $filterData = $request->validate([
            'location_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $memberQueries = resolve(MemberQueries::class);
        $thisMonthTopSellingMembers = $memberQueries->topTenSellingMembers(
            $companyId,
            array_key_exists('location_id', $filterData) ? (int) $filterData['location_id'] : 0,
            now()->startOfMonth()->format('Y-m-d'),
            now()->format('Y-m-d'),
        );

        return [
            'top_ten_members_by_month' => DashboardStockOverviewTopSellingMemberResource::collection(
                $thisMonthTopSellingMembers
            ),
        ];
    }

    public function getInactiveMembersCounts(Request $request): array
    {
        $companyId = session('admin_company_id');

        $filterData = $request->validate([
            'location_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $locationId = array_key_exists('location_id', $filterData) ? (int) $filterData['location_id'] : 0;

        $saleQueries = resolve(SaleQueries::class);
        $inactiveMembers90DaysCount = $saleQueries->getInactiveMembers($companyId, $locationId, 90);

        $inactiveMembers180DaysCount = $saleQueries->getInactiveMembers($companyId, $locationId, 180);

        return [
            'inactive_members_90_days_count' => $inactiveMembers90DaysCount,
            'inactive_members_180_days_count' => $inactiveMembers180DaysCount,
        ];
    }

    public function getTopTenLocation(Request $request): array
    {
        $currentView = $request->get('current_view', 'yearly');
        $targetId = (int) $request->get('target_id', 0);
        $saleTargetIds = $request->get('sale_target_ids', []);
        $companyId = session('admin_company_id');

        $dateRange = match ($currentView) {
            'yearly' => [Carbon::now()->startOfYear()->format('Y-m-d'), Carbon::now()->endOfYear()->format('Y-m-d')],
            'monthly' => [Carbon::now()->startOfMonth()->format('Y-m-d'), Carbon::now()->endOfMonth()->format('Y-m-d')],
            'weekly' => [Carbon::now()->startOfWeek()->format('Y-m-d'), Carbon::now()->endOfWeek()->format('Y-m-d')],
            'daily' => [Carbon::now()->format('Y-m-d'), Carbon::now()->format('Y-m-d')],
            default => [Carbon::now()->startOfYear()->format('Y-m-d'), Carbon::now()->endOfYear()->format('Y-m-d')],
        };

        $locationQueries = resolve(LocationQueries::class);
        $topSellingLocation = $locationQueries->topTenSellingLocation(
            $dateRange,
            $targetId,
            $companyId,
            $saleTargetIds
        );

        return [
            'top_ten_location' => DashboardStockOverviewTopSellingLocationResource::collection($topSellingLocation),
        ];
    }

    public function getWorstTenLocation(Request $request): array
    {
        $currentView = $request->get('current_view', 'yearly');
        $targetId = (int) $request->get('target_id', 0);
        $saleTargetIds = $request->get('sale_target_ids', []);
        $companyId = session('admin_company_id');

        $dateRange = match ($currentView) {
            'yearly' => [Carbon::now()->startOfYear()->format('Y-m-d'), Carbon::now()->endOfYear()->format('Y-m-d')],
            'monthly' => [Carbon::now()->startOfMonth()->format('Y-m-d'), Carbon::now()->endOfMonth()->format('Y-m-d')],
            'weekly' => [Carbon::now()->startOfWeek()->format('Y-m-d'), Carbon::now()->endOfWeek()->format('Y-m-d')],
            'daily' => [Carbon::now()->format('Y-m-d'), Carbon::now()->format('Y-m-d')],
            default => [Carbon::now()->startOfYear()->format('Y-m-d'), Carbon::now()->endOfYear()->format('Y-m-d')],
        };

        $locationQueries = resolve(LocationQueries::class);
        $worstSellingLocation = $locationQueries->worstTenSellingLocation(
            $dateRange,
            $targetId,
            $companyId,
            $saleTargetIds
        );

        return [
            'worst_ten_location' => DashboardStockOverviewTopSellingLocationResource::collection(
                $worstSellingLocation
            ),
        ];
    }

    public function getTopTenPromoter(Request $request): array
    {
        $currentView = $request->get('current_view', 'yearly');
        $targetId = (int) $request->get('target_id', 0);
        $saleTargetIds = $request->get('sale_target_ids', []);
        $companyId = session('admin_company_id');

        $dateRange = match ($currentView) {
            'yearly' => [Carbon::now()->startOfYear()->format('Y-m-d'), Carbon::now()->endOfYear()->format('Y-m-d')],
            'monthly' => [Carbon::now()->startOfMonth()->format('Y-m-d'), Carbon::now()->endOfMonth()->format('Y-m-d')],
            'weekly' => [Carbon::now()->startOfWeek()->format('Y-m-d'), Carbon::now()->endOfWeek()->format('Y-m-d')],
            'daily' => [Carbon::now()->format('Y-m-d'), Carbon::now()->format('Y-m-d')],
            default => [Carbon::now()->startOfYear()->format('Y-m-d'), Carbon::now()->endOfYear()->format('Y-m-d')],
        };

        $promoterQueries = resolve(PromoterQueries::class);
        $topSellingPromoter = $promoterQueries->getTopSellingPromoter(
            $companyId,
            $targetId,
            $dateRange,
            $saleTargetIds
        );

        return [
            'top_ten_promoter' => DashboardStockOverviewTopSellingPromoterResource::collection($topSellingPromoter),
        ];
    }

    public function getWorstTenPromoter(Request $request): array
    {
        $currentView = $request->get('current_view', 'yearly');
        $targetId = (int) $request->get('target_id', 0);
        $saleTargetIds = $request->get('sale_target_ids', []);
        $companyId = session('admin_company_id');

        $dateRange = match ($currentView) {
            'yearly' => [Carbon::now()->startOfYear()->format('Y-m-d'), Carbon::now()->endOfYear()->format('Y-m-d')],
            'monthly' => [Carbon::now()->startOfMonth()->format('Y-m-d'), Carbon::now()->endOfMonth()->format('Y-m-d')],
            'weekly' => [Carbon::now()->startOfWeek()->format('Y-m-d'), Carbon::now()->endOfWeek()->format('Y-m-d')],
            'daily' => [Carbon::now()->format('Y-m-d'), Carbon::now()->format('Y-m-d')],
            default => [Carbon::now()->startOfYear()->format('Y-m-d'), Carbon::now()->endOfYear()->format('Y-m-d')],
        };

        $promoterQueries = resolve(PromoterQueries::class);
        $worstSellingPromoter = $promoterQueries->getWorstSellingPromoter(
            $companyId,
            $targetId,
            $dateRange,
            $saleTargetIds
        );

        return [
            'worst_ten_promoter' => DashboardStockOverviewTopSellingPromoterResource::collection(
                $worstSellingPromoter
            ),
        ];
    }
}
