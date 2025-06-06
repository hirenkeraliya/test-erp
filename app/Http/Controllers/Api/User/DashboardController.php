<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\User;

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
use App\Domains\Department\DepartmentQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Inventory\Enums\Types;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\DashboardStockOverviewTopSellingProductResource;
use App\Domains\Product\Resources\DashboardStockOverviewWorstSellingProductResource;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Domains\SaleSeason\SaleSeasonQueries;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\Resources\SaleTargetDashboardListResource;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughFilterTypes;
use App\Domains\SellThroughAggregate\Enums\SellThroughIncludeTypes;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StoreWiseDailyTotal\StoreWiseDailyTotalQueries;
use App\Domains\Style\StyleQueries;
use App\Domains\User\DataObjects\CompanyOwnerBusinessViewApiData;
use App\Domains\User\DataObjects\CompanyOwnerOperationalAtvChartApiData;
use App\Domains\User\DataObjects\CompanyOwnerOperationalRevenueChartApiData;
use App\Domains\User\DataObjects\CompanyOwnerOperationalSalesApiData;
use App\Domains\User\DataObjects\CompanyOwnerOperationalThisMonthSalesApiData;
use App\Domains\User\DataObjects\CompanyOwnerOperationalThisYearSalesApiData;
use App\Domains\User\DataObjects\CompanyOwnerOperationalThisYearTopPromotersApiData;
use App\Domains\User\DataObjects\CompanyOwnerOperationalTodaySalesApiData;
use App\Domains\User\DataObjects\CompanyOwnerOperationalTopPromotersApiData;
use App\Domains\User\DataObjects\CompanyOwnerOperationalUptChartApiData;
use App\Domains\User\DataObjects\CompanyOwnerRevenueViewApiData;
use App\Domains\User\DataObjects\CompanyOwnerSeasonalApiData;
use App\Domains\User\DataObjects\CompanyOwnerSeasonalChartApiData;
use App\Domains\User\DataObjects\CompanyOwnerSeasonalComparisonApiData;
use App\Domains\User\DataObjects\CompanyOwnerSeasonalMemberComparisonApiData;
use App\Domains\User\DataObjects\CompanyOwnerSeasonalSalesComparisonApiData;
use App\Domains\User\DataObjects\CompanyOwnerSeasonalSalesComparisonChartApiData;
use App\Domains\User\DataObjects\CompanyOwnerSeasonalTotalDiscountApiData;
use App\Domains\User\DataObjects\CompanyOwnerStoreRevenueViewApiData;
use App\Domains\User\DataObjects\CompanyOwnerStyleChartApiData;
use App\Domains\User\DataObjects\CompanyOwnerThisMonthTopSellingColorsApiData;
use App\Domains\User\DataObjects\CompanyOwnerThisMonthTopSellingProductsApiData;
use App\Domains\User\DataObjects\CompanyOwnerThisMonthWorstSellingProductsApiData;
use App\Domains\User\DataObjects\CompanyOwnerThisYearTopSellingColorsApiData;
use App\Domains\User\DataObjects\CompanyOwnerThisYearTopSellingProductsApiData;
use App\Domains\User\DataObjects\CompanyOwnerThisYearWorstSellingProductsApiData;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Promoter;
use App\Models\SaleSeason;
use App\Models\SaleTarget;
use App\Models\SaleTargetTimeframe;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

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

        return [
            'locations' => $locations,
            'brands' => $brands,
        ];
    }

    public function getOperationalAtvChartData(
        Request $request,
        CompanyOwnerOperationalAtvChartApiData $companyOwnerOperationalAtvChartApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $date = Carbon::now()->format('Y-m-d');

        if ($companyOwnerOperationalAtvChartApiData->date) {
            $date = $companyOwnerOperationalAtvChartApiData->date;
        }

        $refresh = (bool) $companyOwnerOperationalAtvChartApiData->refresh;

        $locationId = (int) $companyOwnerOperationalAtvChartApiData->location_id;
        $brandId = (int) $companyOwnerOperationalAtvChartApiData->brand_id;

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

    public function getOperationalUptChartData(
        Request $request,
        CompanyOwnerOperationalUptChartApiData $companyOwnerOperationalUptChartApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $date = Carbon::now()->format('Y-m-d');

        if ($companyOwnerOperationalUptChartApiData->date) {
            $date = $companyOwnerOperationalUptChartApiData->date;
        }

        $refresh = (bool) $companyOwnerOperationalUptChartApiData->refresh;

        $locationId = (int) $companyOwnerOperationalUptChartApiData->location_id;
        $brandId = (int) $companyOwnerOperationalUptChartApiData->brand_id;

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

    public function getOperationalRevenueChartData(
        Request $request,
        CompanyOwnerOperationalRevenueChartApiData $companyOwnerOperationalRevenueChartApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        /** @var Carbon $date */
        $date = Carbon::now();

        if ($companyOwnerOperationalRevenueChartApiData->date) {
            /** @var Carbon $date */
            $date = Carbon::createFromFormat('Y-m-d', $companyOwnerOperationalRevenueChartApiData->date);
        }

        $refresh = (bool) $companyOwnerOperationalRevenueChartApiData->refresh;

        $locationId = (int) $companyOwnerOperationalRevenueChartApiData->location_id;
        $brandId = (int) $companyOwnerOperationalRevenueChartApiData->brand_id;

        $dashboardService = resolve(DashboardService::class);
        $monthWiseSalesDetails = $dashboardService->getMonthWiseSalesDetails(
            $companyId,
            $locationId,
            $brandId,
            $date->format('Y-m-d'),
            $refresh
        );

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

    public function getOperationalSalesCount(
        Request $request,
        CompanyOwnerOperationalSalesApiData $companyOwnerOperationalSalesApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);
        $date = Carbon::now()->format('Y-m-d');

        if ($companyOwnerOperationalSalesApiData->date) {
            $date = $companyOwnerOperationalSalesApiData->date;
        }

        $locationId = (int) $companyOwnerOperationalSalesApiData->location_id;
        $brandId = (int) $companyOwnerOperationalSalesApiData->brand_id;

        $refresh = (bool) $companyOwnerOperationalSalesApiData->refresh;

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

    public function getOperationalTodaySales(
        Request $request,
        CompanyOwnerOperationalTodaySalesApiData $companyOwnerOperationalTodaySalesApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $date = Carbon::now()->format('Y-m-d');

        if ($companyOwnerOperationalTodaySalesApiData->date) {
            $date = $companyOwnerOperationalTodaySalesApiData->date;
        }

        $locationId = (int) $companyOwnerOperationalTodaySalesApiData->location_id;
        $brandId = (int) $companyOwnerOperationalTodaySalesApiData->brand_id;

        $refresh = (bool) $companyOwnerOperationalTodaySalesApiData->refresh;

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
                'totalSale' => (float) $todaySalesDetails['totalSalesCount'],
                'totalUnitsSold' => (float) $todaySalesDetails['totalUnitsSold'],
                'upt' => (float) $todaySalesDetails['todayUpt'],
                'atv' => (float) $todaySalesDetails['todayAtv'],
            ],
        ];
    }

    public function getOperationalThisMonthSales(
        Request $request,
        CompanyOwnerOperationalThisMonthSalesApiData $companyOwnerOperationalThisMonthSalesApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $date = Carbon::now()->format('Y-m-d');

        if ($companyOwnerOperationalThisMonthSalesApiData->date) {
            $date = $companyOwnerOperationalThisMonthSalesApiData->date;
        }

        $locationId = (int) $companyOwnerOperationalThisMonthSalesApiData->location_id;
        $brandId = (int) $companyOwnerOperationalThisMonthSalesApiData->brand_id;

        $refresh = (bool) $companyOwnerOperationalThisMonthSalesApiData->refresh;

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
                'totalSale' => (float) $thisMonthSalesDetails['totalSalesCount'],
                'totalUnitsSold' => (float) $thisMonthSalesDetails['totalUnitsSold'],
                'upt' => (float) $thisMonthSalesDetails['mtdUpt'],
                'atv' => (float) $thisMonthSalesDetails['mtdAtv'],
            ],
        ];
    }

    public function getOperationalThisYearSales(
        Request $request,
        CompanyOwnerOperationalThisYearSalesApiData $companyOwnerOperationalThisYearSalesApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $date = Carbon::now()->format('Y-m-d');

        if ($companyOwnerOperationalThisYearSalesApiData->date) {
            $date = $companyOwnerOperationalThisYearSalesApiData->date;
        }

        $refresh = (bool) $companyOwnerOperationalThisYearSalesApiData->refresh;

        $locationId = (int) $companyOwnerOperationalThisYearSalesApiData->location_id;
        $brandId = (int) $companyOwnerOperationalThisYearSalesApiData->brand_id;

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
                'totalSale' => (float) $thisYearSalesDetails['totalSalesCount'],
                'totalUnitsSold' => (float) $thisYearSalesDetails['totalUnitsSold'],
                'upt' => (float) $thisYearSalesDetails['ytdUpt'],
                'atv' => (float) $thisYearSalesDetails['ytdAtv'],
            ],
        ];
    }

    public function getOperationalTopPromoters(
        Request $request,
        CompanyOwnerOperationalTopPromotersApiData $companyOwnerOperationalTopPromotersApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $date = Carbon::now()->format('Y-m-d');

        if ($companyOwnerOperationalTopPromotersApiData->date) {
            $date = $companyOwnerOperationalTopPromotersApiData->date;
        }

        $refresh = (bool) $companyOwnerOperationalTopPromotersApiData->refresh;

        $locationId = (int) $companyOwnerOperationalTopPromotersApiData->location_id;
        $brandId = (int) $companyOwnerOperationalTopPromotersApiData->brand_id;

        $dashboardService = resolve(DashboardService::class);

        return [
            'topPromoters' => $dashboardService->getTopPromoters($companyId, $locationId, $brandId, $date, $refresh),
        ];
    }

    public function getOperationalThisYearTopPromoters(
        Request $request,
        CompanyOwnerOperationalThisYearTopPromotersApiData $companyOwnerOperationalThisYearTopPromotersApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $date = Carbon::now()->format('Y-m-d');

        if ($companyOwnerOperationalThisYearTopPromotersApiData->date) {
            $date = $companyOwnerOperationalThisYearTopPromotersApiData->date;
        }

        $refresh = (bool) $companyOwnerOperationalThisYearTopPromotersApiData->refresh;

        $locationId = (int) $companyOwnerOperationalThisYearTopPromotersApiData->location_id;
        $brandId = (int) $companyOwnerOperationalThisYearTopPromotersApiData->brand_id;

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

    public function revenueView(
        Request $request,
        CompanyOwnerRevenueViewApiData $companyOwnerRevenueViewApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        [
            $totalSalesByLocationChartData,
            $totalSalesByBrandChartData,
            $totalSalesByCategoryChartData,
            $totalSalesByStyleChartData,
            $totalSalesByDepartmentChartData,
            $totalSalesByLocations,
            $salesTotalData,
            $dateRange,
            $brands,
            $brandId,
        ] = $this->prepareRevenueView($companyOwnerRevenueViewApiData, $companyId);

        return [
            'totalSalesByLocation' => $totalSalesByLocationChartData,
            'totalSalesByBrand' => $totalSalesByBrandChartData,
            'totalSalesByCategory' => $totalSalesByCategoryChartData,
            'totalSalesByStyle' => $totalSalesByStyleChartData,
            'totalSalesByDepartment' => $totalSalesByDepartmentChartData,
            'totalSales' => $totalSalesByLocations->sum('total_sales'),
            'totalUnitsSold' => $totalSalesByLocations->sum('total_units_sold'),
            'salesData' => $totalSalesByLocations,
            'salesTotalData' => $salesTotalData,
            'start_date' => $dateRange[0],
            'end_date' => $dateRange[1],
            'brands' => $brands,
            'brandId' => $brandId,
            'lastUpdate' => Cache::remember(
                'index-refresh-date-time-for-revenue',
                900,
                fn (): string => now()->format('d-m-y h:i:s A')
            ),
        ];
    }

    public function storeRevenueView(
        Request $request,
        CompanyOwnerStoreRevenueViewApiData $companyOwnerStoreRevenueViewApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        [
            $brands,
            $locations,
            $locationId,
            $brandId,
            $totalCreditSalePendingAmount,
            $totalSalesByColorChartData,
            $totalSalesByBrandChartData,
            $totalSalesByCategoryChartData,
            $totalSalesByDepartmentChartData,
            $totalSalesByColorGroupChartData,
            $totalSalesBySizeChartData,
            $totalSalesByStyleChartData,
            $todayHourlySales,
            $yesterdayHourlySales,
            $todayHourlyTotalSales,
            $yesterdayHourlyTotalSales,
            $hourlyChartLabel,
            $totalSalesByLocations,
            $totalSalesByBrands,
            $grandTotalSalesByBrand,
            $totalSalesByColors,
            $grandTotalSalesByColor,
            $totalSalesByCategories,
            $grandTotalSalesByCategory,
            $totalSalesByDepartments,
            $grandTotalSalesByDepartment,
            $totalSalesByColorGroups,
            $grandTotalSalesByColorGroup,
            $totalSalesBySizes,
            $totalSalesByStyles,
            $grandTotalSalesBySize,
            $grandTotalSalesByStyle,
            $date,
        ] = $this->prepareStoreRevenueView($companyOwnerStoreRevenueViewApiData, $companyId);

        return [
            'brands' => $brands,
            'locations' => $locations,
            'locationId' => $locationId,
            'brandId' => $brandId,
            'totalCreditSalePendingAmount' => (float) $totalCreditSalePendingAmount,
            'totalSalesByColor' => $totalSalesByColorChartData,
            'totalSalesByBrand' => $totalSalesByBrandChartData,
            'totalSalesByCategory' => $totalSalesByCategoryChartData,
            'totalSalesByDepartment' => $totalSalesByDepartmentChartData,
            'totalSalesByColorGroup' => $totalSalesByColorGroupChartData,
            'totalSalesBySize' => $totalSalesBySizeChartData,
            'totalSalesByStyle' => $totalSalesByStyleChartData,
            'hourlySales' => [
                'today' => $todayHourlySales,
                'yesterday' => $yesterdayHourlySales,
                'label' => $hourlyChartLabel,
            ],
            'accumulatedHourlySales' => [
                'today' => $todayHourlyTotalSales,
                'yesterday' => $yesterdayHourlyTotalSales,
                'label' => $hourlyChartLabel,
            ],
            'totalSales' => (float) $totalSalesByLocations->sum('total_sales'),
            'totalUnitsSold' => (float) $totalSalesByLocations->sum('total_units_sold'),
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
        ];
    }

    public function businessView(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $brandQueries = resolve(BrandQueries::class);
        $brands = $brandQueries->getBrands($companyId);

        $brands->prepend([
            'id' => 0,
            'name' => 'All Brands',
        ]);

        return [
            'brands' => $brands,
        ];
    }

    public function getBusinessViewData(
        Request $request,
        CompanyOwnerBusinessViewApiData $companyOwnerBusinessViewApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);
        $companyQueries = resolve(CompanyQueries::class);

        $refresh = (bool) $companyOwnerBusinessViewApiData->refresh;

        if ($refresh) {
            Cache::forget('index-refresh-date-time-for-business');
        }

        $brandId = (int) $companyOwnerBusinessViewApiData->brand_id;

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

        $yearlyTargetPercentage = $companyQueries->getYearlyTarget($companyId);
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

        if ($companyOwnerBusinessViewApiData->month && $companyOwnerBusinessViewApiData->year) {
            /** @var Carbon $startOfMonthDate */
            $startOfMonthDate = Carbon::createFromFormat(
                'Y-m-d',
                $companyOwnerBusinessViewApiData->year . '-' . $companyOwnerBusinessViewApiData->month . '-01'
            );
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

    public function getStyleChartData(
        Request $request,
        CompanyOwnerStyleChartApiData $companyOwnerStyleChartApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $currentDate = Carbon::now();

        $startFormatted = $currentDate->startOfMonth()->format('Y-m-d');
        $endFormatted = $currentDate->endOfMonth()->format('Y-m-d');

        $brandId = (int) $companyOwnerStyleChartApiData->brand_id;

        if ($companyOwnerStyleChartApiData->quarter) {
            $year = (int) Carbon::now()->format('Y');
            $quarter = $companyOwnerStyleChartApiData->quarter;

            /** @var Carbon $startDate */
            $startDate = Carbon::create($year, ($quarter - 1) * 3 + 1, 1);
            $endDate = $startDate->copy()->endOfQuarter();

            $startFormatted = $startDate->format('Y-m-d');
            $endFormatted = $endDate->format('Y-m-d');
        }

        if ($companyOwnerStyleChartApiData->month && $companyOwnerStyleChartApiData->year) {
            /** @var Carbon $startOfMonthDate */
            $startOfMonthDate = Carbon::createFromFormat(
                'Y-m-d',
                $companyOwnerStyleChartApiData->year . '-' . $companyOwnerStyleChartApiData->month . '-01'
            );
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

    public function stockOverview(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

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

        return [
            'locations' => $locations,
            'brands' => $brands,
            'stockTypes' => [
                'no_stock' => Types::NO_STOCK->value,
                'low_stock_company' => Types::LOW_STOCK_COMPANY->value,
                'low_stock_location' => Types::LOW_STOCK_LOCATION->value,
                'low_stock_product' => Types::LOW_STOCK_PRODUCT->value,
            ],
            'transferTypes' => [
                'request_order' => StockTransferTypes::REQUEST_ORDER->value,
                'transfer_order' => StockTransferTypes::TRANSFER_ORDER->value,
            ],
            'orderTypes' => OrderTypes::getFormattedArrayForStaticUse(),
            'fulfillmentStatuses' => FulfillmentStatuses::generateStaticCasesArray(),
            'purchaseOrderStatuses' => Statuses::generateStaticCasesArray(),
            'stockTransferStatuses' => StatusTypes::generateStaticCasesArray(),
            'activeStatus' => ProductStatuses::ACTIVE->value,
        ];
    }

    public function getNoStockOverview(Request $request): array
    {
        $validatedData = $request->validate([
            'location_id' => ['sometimes', 'nullable', 'integer', 'exists:locations,id'],
            'refresh' => ['sometimes', 'nullable', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $locationId = (int) ($validatedData['location_id'] ?? 0);
        $refresh = (bool) ($validatedData['refresh'] ?? false);

        $filterData = [
            'location_id' => $locationId,
        ];

        $inventoryQueries = resolve(InventoryQueries::class);
        $noStockItemCount = $inventoryQueries->getNoStockItems($filterData, $companyId, $refresh);

        return [
            'noStockItemCount' => $noStockItemCount,
        ];
    }

    public function getLowStockOverview(Request $request): array
    {
        $validatedData = $request->validate([
            'location_id' => ['sometimes', 'nullable', 'integer', 'exists:locations,id'],
            'refresh' => ['sometimes', 'nullable', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $locationId = (int) ($validatedData['location_id'] ?? 0);
        $refresh = (bool) ($validatedData['refresh'] ?? false);

        $filterData = [
            'location_id' => $locationId,
        ];

        $inventoryQueries = resolve(InventoryQueries::class);

        $lowStockCompanyCount = $inventoryQueries->getCompanyLowStockItems($filterData, $companyId, $refresh);
        $lowStockLocationCount = $inventoryQueries->getLocationLowStockItems($filterData, $companyId, $refresh);
        $lowStockProductCount = $inventoryQueries->getProductLowStockItems($filterData, $companyId, $refresh);

        return [
            'lowStockItemCount' => $lowStockCompanyCount + $lowStockLocationCount + $lowStockProductCount,
            'lowStockCompanyCount' => $lowStockCompanyCount,
            'lowStockLocationCount' => $lowStockLocationCount,
            'lowStockProductCount' => $lowStockProductCount,
        ];
    }

    public function getNegativeStockOverview(Request $request): array
    {
        $validatedData = $request->validate([
            'location_id' => ['sometimes', 'nullable', 'integer', 'exists:locations,id'],
            'refresh' => ['sometimes', 'nullable', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $locationId = (int) ($validatedData['location_id'] ?? 0);
        $refresh = (bool) ($validatedData['refresh'] ?? false);

        $filterData = [
            'location_id' => $locationId,
        ];

        $inventoryQueries = resolve(InventoryQueries::class);
        $negativeStockItemCount = $inventoryQueries->getNegativeStockItems($filterData, $companyId, $refresh);

        return [
            'negativeStockItemCount' => $negativeStockItemCount,
        ];
    }

    public function getTransferOrder(Request $request, int $locationId): array
    {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $dashboardService = resolve(DashboardService::class);
        $transferOrders = $dashboardService->getTransferOrder($locationId, $companyId);

        return [
            'transferOrders' => $transferOrders,
        ];
    }

    public function getPurchaseRequest(Request $request, int $locationId): array
    {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $dashboardService = resolve(DashboardService::class);
        $purchaseRequests = $dashboardService->getPurchaseRequest($locationId, $companyId);

        return [
            'purchaseRequests' => array_values($purchaseRequests),
        ];
    }

    public function getTransferRequest(Request $request, int $locationId): array
    {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $dashboardService = resolve(DashboardService::class);
        $transferRequests = $dashboardService->getTransferRequest($locationId, $companyId);

        return [
            'transferRequests' => array_values($transferRequests),
        ];
    }

    public function getSalesOrder(Request $request, int $locationId): array
    {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $dashboardService = resolve(DashboardService::class);
        [$salesOrders, $salesDeliveryOrders] = $dashboardService->getSalesOrder($locationId, $companyId);

        return [
            'salesOrders' => array_values($salesOrders),
            'salesDeliveryOrders' => array_values($salesDeliveryOrders),
        ];
    }

    public function getPurchaseOrder(Request $request, int $locationId): array
    {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $dashboardService = resolve(DashboardService::class);
        [$purchaseOrders, $purchaseDeliveryOrders] = $dashboardService->getPurchaseOrder($locationId, $companyId);

        return [
            'purchaseOrders' => array_values($purchaseOrders),
            'purchaseDeliveryOrders' => array_values($purchaseDeliveryOrders),
        ];
    }

    public function getRequestOrder(Request $request, int $locationId): array
    {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $dashboardService = resolve(DashboardService::class);
        $requestOrders = $dashboardService->getRequestOrder($locationId, $companyId);

        return [
            'requestOrders' => $requestOrders,
        ];
    }

    public function getThisMonthTopSellingProducts(
        Request $request,
        CompanyOwnerThisMonthTopSellingProductsApiData $companyOwnerThisMonthTopSellingProductsApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $locationId = (int) $companyOwnerThisMonthTopSellingProductsApiData->location_id;
        $brandId = (int) $companyOwnerThisMonthTopSellingProductsApiData->brand_id;
        $refresh = (bool) $companyOwnerThisMonthTopSellingProductsApiData->refresh;

        if ($refresh) {
            Cache::forget('index-refresh-date-time-for-stock-overview');
        }

        $productQueries = resolve(ProductQueries::class);
        $thisMonthTopSellingProducts = $productQueries->getCachedTopSellingProduct(
            $companyId,
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

    public function getThisYearTopSellingProducts(
        Request $request,
        CompanyOwnerThisYearTopSellingProductsApiData $companyOwnerThisYearTopSellingProductsApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $locationId = (int) $companyOwnerThisYearTopSellingProductsApiData->location_id;
        $brandId = (int) $companyOwnerThisYearTopSellingProductsApiData->brand_id;
        $refresh = (bool) $companyOwnerThisYearTopSellingProductsApiData->refresh;

        $productQueries = resolve(ProductQueries::class);
        $thisYearTopSellingProducts = $productQueries->getCachedTopSellingProduct(
            $companyId,
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

    public function getThisMonthWorstSellingProducts(
        Request $request,
        CompanyOwnerThisMonthWorstSellingProductsApiData $companyOwnerThisMonthWorstSellingProductsApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $locationId = (int) $companyOwnerThisMonthWorstSellingProductsApiData->location_id;
        $brandId = (int) $companyOwnerThisMonthWorstSellingProductsApiData->brand_id;
        $refresh = (bool) $companyOwnerThisMonthWorstSellingProductsApiData->refresh;

        if ($refresh) {
            Cache::forget('index-refresh-date-time-for-stock-overview');
        }

        $productQueries = resolve(ProductQueries::class);
        $thisMonthWorstSellingProducts = $productQueries->getCachedWorstSellingProduct(
            $companyId,
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

    public function getThisYearWorstSellingProducts(
        Request $request,
        CompanyOwnerThisYearWorstSellingProductsApiData $companyOwnerThisYearWorstSellingProductsApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $locationId = (int) $companyOwnerThisYearWorstSellingProductsApiData->location_id;
        $brandId = (int) $companyOwnerThisYearWorstSellingProductsApiData->brand_id;
        $refresh = (bool) $companyOwnerThisYearWorstSellingProductsApiData->refresh;

        $productQueries = resolve(ProductQueries::class);
        $thisYearWorstSellingProducts = $productQueries->getCachedWorstSellingProduct(
            $companyId,
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

    public function getThisMonthTopSellingColors(
        Request $request,
        CompanyOwnerThisMonthTopSellingColorsApiData $companyOwnerThisMonthTopSellingColorsApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $locationId = (int) $companyOwnerThisMonthTopSellingColorsApiData->location_id;
        $brandId = (int) $companyOwnerThisMonthTopSellingColorsApiData->brand_id;
        $refresh = (bool) $companyOwnerThisMonthTopSellingColorsApiData->refresh;

        $colorQueries = resolve(ColorQueries::class);
        $thisMonthTopSellingColors = $colorQueries->getCachedTopSellingColor(
            $companyId,
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

    public function getThisYearTopSellingColors(
        Request $request,
        CompanyOwnerThisYearTopSellingColorsApiData $companyOwnerThisYearTopSellingColorsApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $locationId = (int) $companyOwnerThisYearTopSellingColorsApiData->location_id;
        $brandId = (int) $companyOwnerThisYearTopSellingColorsApiData->brand_id;
        $refresh = (bool) $companyOwnerThisYearTopSellingColorsApiData->refresh;

        $colorQueries = resolve(ColorQueries::class);
        $thisYearTopSellingColors = $colorQueries->getCachedTopSellingColor(
            $companyId,
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

    public function getTopRankingProducts(Request $request, int $locationId): array
    {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        if ($locationId <= 0) {
            $locationId = null;
        }

        $filterData = [
            'location_ids' => [$locationId],
            'filter_by' => SellThroughFilterTypes::ALL->value,
            'search_text' => null,
            'sort_by' => 'sell_through',
            'sort_direction' => 'desc',
            'date' => now()->format('Y-m-d'),
            'include_by' => [
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_IN->value,
                SellThroughIncludeTypes::GOODS_RECEIVE_NOTE_OUT->value,
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_IN->value,
                SellThroughIncludeTypes::STOCK_ADJUSTMENT_OUT->value,
                SellThroughIncludeTypes::STOCK_TRANSFER_IN->value,
                SellThroughIncludeTypes::DELIVERY_ORDER_IN->value,
            ],
        ];

        $sellThroughAggregateQueries = resolve(SellThroughAggregateQueries::class);
        $topRankingProducts = $sellThroughAggregateQueries->sellThroughAggregateByProductArticleNumberForDashboard(
            $filterData,
            $companyId
        );

        return [
            'topRankingProducts' => $topRankingProducts,
        ];
    }

    public function saleTarget(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $saleTargetQueries = resolve(SaleTargetQueries::class);
        $saleTargets = $saleTargetQueries->getSaleTargetWithAchieved($companyId);

        $preParedSaleTargetData = [];
        foreach ($saleTargets as $saleTarget) {
            $preParedSaleTargetData[] = [
                'saleTarget' => new SaleTargetDashboardListResource($saleTarget),
                'saleTargetCharts' => $this->prepareChartRecords($saleTarget->saleTargetTimeframes, $saleTarget),
                'saleTargetAccumulatedCharts' => $this->prepareAccumulatedChartRecords(
                    $saleTarget->saleTargetTimeframes,
                    $saleTarget
                ),
                'saleTargetTableRecords' => $this->prepareTableRecords($saleTarget->saleTargetTimeframes, $saleTarget),
                'getTotalTargetAndAchieved' => $this->prepareTotalTargetAndAchieved($saleTarget->saleTargetTimeframes),
            ];
        }

        return [
            'saleTargets' => $preParedSaleTargetData,
            'statusStaticType' => Statuses::generateStaticCasesArray(),
        ];
    }

    public function saleTargetByTimeInterval(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();

        $year = Carbon::now()->year;

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $saleTargetQueries = resolve(SaleTargetQueries::class);

        $currentYearlySaleTarget = $saleTargetQueries->getCurrentYearSalesTarget($year, $companyId, null);
        $previousYearlySaleTarget = $saleTargetQueries->getCurrentYearSalesTarget($year - 1, $companyId, null);
        $currentMonthlySaleTarget = $saleTargetQueries->getCurrentMonthSalesTarget($year, $companyId, null);
        $currentWeeklySaleTarget = $saleTargetQueries->getCurrentWeekSalesTarget($year, $companyId, null);
        $currentDailySaleTarget = $saleTargetQueries->getCurrentDailySalesTarget($year, $companyId, null);

        $previousMonths = [];
        $previousWeeks = [];
        $previousDays = [];

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

        $previousMonthlySaleTarget = $this->getPreviousMonthlySaleTarget($previousMonths, $companyId);
        $previousWeekSaleTarget = $this->getPreviousWeekSaleTarget($previousWeeks, $companyId);
        $previousDailySaleTarget = $this->getPreviousDailySaleTarget($previousDays, $companyId);

        $preParedSaleTargetData = $this->processSaleTargetData(
            $currentYearlySaleTarget,
            $previousYearlySaleTarget,
            $currentMonthlySaleTarget,
            $previousMonthlySaleTarget,
            $currentWeeklySaleTarget,
            $previousWeekSaleTarget,
            $currentDailySaleTarget,
            $previousDailySaleTarget
        );

        return [
            'saleTargets' => $preParedSaleTargetData,
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
    ): array {
        $saleTargetData = [];

        foreach (TargetType::getList() as $targetType) {
            $typeId = $targetType['id'];
            $saleTargetData[$targetType['key']] = [
                'target_type' => TargetType::getFormattedCaseName($typeId),
                'yearly' => $this->typeWiseSaleTargetData($currentYearlySaleTarget, $previousYearlySaleTarget, $typeId),
                'monthly' => $this->typeWiseSaleTargetData(
                    $currentMonthlySaleTarget,
                    $previousMonthlySaleTarget,
                    $typeId
                ),
                'weekly' => $this->typeWiseSaleTargetData($currentWeeklySaleTarget, $previousWeeklySaleTarget, $typeId),
                'daily' => $this->typeWiseSaleTargetData($currentDailySaleTarget, $previousDailySaleTarget, $typeId),
            ];
        }

        return $saleTargetData;
    }

    private function typeWiseSaleTargetData(
        Collection $currentSaleTarget,
        Collection $previousSaleTarget,
        int $typeId
    ): array {
        $current = $currentSaleTarget[$typeId] ?? collect();
        $previous = $previousSaleTarget[$typeId] ?? collect();
        $target = $current->sum('target_value');
        $achieved = $current->sum('achieved_value');
        $previousTarget = $previous->sum('target_value');
        $previousAchieved = $previous->sum('achieved_value');

        return [
            'target' => $target,
            'achieved' => $achieved,
            'percentage' => $target > 0 ? round($achieved * 100 / $target) : 0,
            'previous_target' => $previousTarget,
            'previous_achieved' => $previousAchieved,
            'previous_percentage' => $previousAchieved > 0 ? round(
                ($achieved - $previousAchieved) * 100 / $previousAchieved
            ) : 0,
        ];
    }

    public function getSaleTargetTimeIntervalType(): array
    {
        return [
            'time_interval_type' => TimeIntervalType::getList(),
        ];
    }

    public function seasonal(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $saleSeasons = $saleSeasonQueries->getWithBasicColumns($companyId);

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

        return [
            'locations' => $locations,
            'brands' => $brands,
            'saleSeasons' => $saleSeasons,
        ];
    }

    public function getSeasonalData(Request $request, CompanyOwnerSeasonalApiData $companyOwnerSeasonalApiData): array
    {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $saleSeason = $saleSeasonQueries->getById($companyOwnerSeasonalApiData->sale_season_id, $companyId);

        if (! $saleSeason instanceof SaleSeason) {
            abort(412, 'please Select Proper Sale Seasons');
        }

        $filterData = [
            'start_date' => $saleSeason->start_date,
            'end_date' => $saleSeason->end_date,
            'location_id' => $companyOwnerSeasonalApiData->location_id,
            'brand_id' => $companyOwnerSeasonalApiData->brand_id,
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
            'sales' => CommonFunctions::numberFormat($totalSalesAmount - $totalAmountReturn),
            'total_receipt' => (int) $totalSalesCount,
            'total_units_sold' => CommonFunctions::numberFormat($totalUnitsSold - $totalUnitsReturn),
            'upt' => 0 != $totalSalesCount ? CommonFunctions::numberFormat(
                ($totalUnitsSold - $totalUnitsReturn) / $totalSalesCount
            ) : 0,
            'atv' => CommonFunctions::numberFormat($totalAtv),
            'total_discounts' => CommonFunctions::numberFormat($saleDiscounts + $saleItemDiscounts),
        ];
    }

    public function getSeasonalChartData(
        Request $request,
        CompanyOwnerSeasonalChartApiData $companyOwnerSeasonalChartApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $saleSeason = $saleSeasonQueries->getById($companyOwnerSeasonalChartApiData->sale_season_id, $companyId);

        if (! $saleSeason instanceof SaleSeason) {
            abort(412, 'please Select Proper Sale Seasons');
        }

        $filterData = [
            'start_date' => $saleSeason->start_date,
            'end_date' => $saleSeason->end_date,
            'location_id' => $companyOwnerSeasonalChartApiData->location_id,
            'brand_id' => $companyOwnerSeasonalChartApiData->brand_id,
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

    public function getSeasonalTotalDiscounts(
        Request $request,
        CompanyOwnerSeasonalTotalDiscountApiData $companyOwnerSeasonalTotalDiscountApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $saleSeason = $saleSeasonQueries->getById(
            $companyOwnerSeasonalTotalDiscountApiData->sale_season_id,
            $companyId
        );

        if (! $saleSeason instanceof SaleSeason) {
            abort(412, 'please Select Proper Sale Seasons');
        }

        $filterData = [
            'start_date' => $saleSeason->start_date,
            'end_date' => $saleSeason->end_date,
            'location_id' => $companyOwnerSeasonalTotalDiscountApiData->location_id,
            'brand_id' => $companyOwnerSeasonalTotalDiscountApiData->brand_id,
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

    public function getSeasonalComparisonData(
        Request $request,
        CompanyOwnerSeasonalComparisonApiData $companyOwnerSeasonalComparisonApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $currencyQueries = resolve(CurrencyQueries::class);
        $currency = $currencyQueries->getByCompanyId($companyId);

        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $xSaleSeason = $saleSeasonQueries->getById(
            $companyOwnerSeasonalComparisonApiData->comparison_x_sale_season_id,
            $companyId
        );
        $ySaleSeason = $saleSeasonQueries->getById(
            $companyOwnerSeasonalComparisonApiData->comparison_y_sale_season_id,
            $companyId
        );

        $xStoreWiseTotals = $this->getSaleSeasonalChartData(
            $xSaleSeason,
            $companyOwnerSeasonalComparisonApiData,
            $companyId
        );
        $yStoreWiseTotals = $this->getSaleSeasonalChartData(
            $ySaleSeason,
            $companyOwnerSeasonalComparisonApiData,
            $companyId
        );

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

    public function getSeasonalMemberComparisonData(
        Request $request,
        CompanyOwnerSeasonalMemberComparisonApiData $companyOwnerSeasonalMemberComparisonApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $xSaleSeason = $saleSeasonQueries->getById(
            $companyOwnerSeasonalMemberComparisonApiData->comparison_x_sale_season_id,
            $companyId
        );
        $ySaleSeason = $saleSeasonQueries->getById(
            $companyOwnerSeasonalMemberComparisonApiData->comparison_y_sale_season_id,
            $companyId
        );

        $xMemberData = $this->getSeasonalMemberData(
            $xSaleSeason,
            $companyOwnerSeasonalMemberComparisonApiData,
            $companyId
        );
        $yMemberData = $this->getSeasonalMemberData(
            $ySaleSeason,
            $companyOwnerSeasonalMemberComparisonApiData,
            $companyId
        );

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

    public function getSeasonalSalesComparisonData(
        Request $request,
        CompanyOwnerSeasonalSalesComparisonApiData $companyOwnerSeasonalSalesComparisonApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $xSaleSeason = $saleSeasonQueries->getById(
            $companyOwnerSeasonalSalesComparisonApiData->comparison_x_sale_season_id,
            $companyId
        );
        $ySaleSeason = $saleSeasonQueries->getById(
            $companyOwnerSeasonalSalesComparisonApiData->comparison_y_sale_season_id,
            $companyId
        );

        $yStoreWiseTotals = $this->getAnalyticsBeforeTenDaysOfSeasonalData(
            $ySaleSeason,
            $companyOwnerSeasonalSalesComparisonApiData,
            $companyId
        );
        $xStoreWiseTotals = $this->getAnalyticsBeforeTenDaysOfSeasonalData(
            $xSaleSeason,
            $companyOwnerSeasonalSalesComparisonApiData,
            $companyId
        );

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

    public function getSeasonalSalesComparisonChartData(
        Request $request,
        CompanyOwnerSeasonalSalesComparisonChartApiData $companyOwnerSeasonalSalesComparisonChartApiData
    ): array {
        /** @var User $user */
        $user = $request->user();

        $employeeQueries = resolve(EmployeeQueries::class);
        $companyId = $employeeQueries->getEmployeeCompanyId($user->employee_id);

        $saleSeasonQueries = resolve(SaleSeasonQueries::class);
        $xSaleSeason = $saleSeasonQueries->getById(
            $companyOwnerSeasonalSalesComparisonChartApiData->comparison_x_sale_season_id,
            $companyId
        );
        $ySaleSeason = $saleSeasonQueries->getById(
            $companyOwnerSeasonalSalesComparisonChartApiData->comparison_y_sale_season_id,
            $companyId
        );

        $filterData = [
            'x_start_date' => $xSaleSeason->start_date,
            'x_end_date' => $xSaleSeason->end_date,
            'y_start_date' => $ySaleSeason->start_date,
            'y_end_date' => $ySaleSeason->end_date,
            'location_id' => $companyOwnerSeasonalSalesComparisonChartApiData->location_id,
            'brand_id' => $companyOwnerSeasonalSalesComparisonChartApiData->brand_id,
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

    private function prepareChartRecords(Collection $saleTargetTimeframes, SaleTarget $saleTarget): array
    {
        $chartRecords = [
            'labels' => [],
            'target_amount' => [],
            'achieved_amount' => [],
        ];

        foreach ($saleTargetTimeframes as $saleTargetTimeframe) {
            $achievedAmount = $saleTargetTimeframe->saleAchievedTargets->sum('achieved_value');

            $chartRecords['labels'][] = $this->getChartLabel($saleTargetTimeframe, $saleTarget->time_interval_type);
            $chartRecords['target_amount'][] = $saleTarget->amount;
            $chartRecords['achieved_amount'][] = $achievedAmount;
        }

        return $chartRecords;
    }

    private function getChartLabel(SaleTargetTimeframe $saleTargetTimeframe, int $timeIntervalType): string
    {
        /** @var Carbon $startDateInstance */
        $startDateInstance = Carbon::createFromFormat('Y-m-d', $saleTargetTimeframe->start_date);
        /** @var Carbon $endDateInstance */
        $endDateInstance = Carbon::createFromFormat('Y-m-d', $saleTargetTimeframe->end_date);

        $startDate = $startDateInstance->format('F');
        $endDate = $endDateInstance->format('F');

        if (TimeIntervalType::WEEKLY->value === $timeIntervalType) {
            return $this->getChartLabelForWeeklyBased(
                (string) $saleTargetTimeframe->target_label,
                $startDate,
                $endDate
            );
        }

        if (TimeIntervalType::CUSTOM_PERIOD->value === $timeIntervalType) {
            return $this->getChartLabelForCustomPeriodBased(
                (string) $saleTargetTimeframe->target_label,
                $startDateInstance->format('Y-m-d'),
                $endDateInstance->format('Y-m-d')
            );
        }

        if (TimeIntervalType::DAILY->value === $timeIntervalType) {
            return $startDateInstance->format('Y-m-d');
        }

        return (string) $saleTargetTimeframe->target_label;
    }

    private function getChartLabelForCustomPeriodBased(string $targetLabel, string $startDate, string $endDate): string
    {
        $specifiedDate = ' (' . $startDate . ' - ' . $endDate . ')';

        return $targetLabel . ' ' . $specifiedDate;
    }

    private function getChartLabelForWeeklyBased(
        string $targetLabel,
        string $startDateAsMonth,
        string $endDateAsMonth
    ): string {
        $specifiedMonth = ($startDateAsMonth === $endDateAsMonth) ? $startDateAsMonth : $startDateAsMonth . ' - ' . $endDateAsMonth;

        return $targetLabel . ' (' . $specifiedMonth . ')';
    }

    private function prepareAccumulatedChartRecords(Collection $saleTargetTimeframes, SaleTarget $saleTarget): array
    {
        $chartRecords = [
            'labels' => [],
            'achieved_amount' => [],
        ];
        $total = 0;
        foreach ($saleTargetTimeframes as $saleTargetTimeframe) {
            $achievedAmount = $saleTargetTimeframe->saleAchievedTargets->sum('achieved_value') + $total;
            $chartRecords['labels'][] = $this->getChartLabel($saleTargetTimeframe, $saleTarget->time_interval_type);
            $chartRecords['achieved_amount'][] = CommonFunctions::numberFormatString($achievedAmount, 2);
            $total = $achievedAmount;
        }

        return $chartRecords;
    }

    private function prepareTableRecords(Collection $saleTargetTimeframes, SaleTarget $saleTarget): array
    {
        $tableRecords = collect([]);

        foreach ($saleTargetTimeframes as $saleTargetTimeframe) {
            $achievedAmount = $saleTargetTimeframe->saleAchievedTargets->sum('achieved_value');

            $tableRecords->push([
                'timeframe' => $this->getChartLabel($saleTargetTimeframe, $saleTarget->time_interval_type),
                'target_amount' => $saleTarget->amount,
                'achieved_amount' => $achievedAmount,
                'other_details' => $this->getStoreOrPromoterWiseData(
                    $saleTargetTimeframe->saleAchievedTargets,
                    $saleTarget
                ),
            ]);
        }

        return $tableRecords->toArray();
    }

    private function getStoreOrPromoterWiseData(
        Collection $saleAchievedTargets,
        SaleTarget $saleTarget
    ): Collection {
        $targetableData = [];
        foreach (
            $saleAchievedTargets->groupBy(
                ['targetable_id', 'targetable_type']
            ) as $key => $saleAchievedTargetGrouped
        ) {
            foreach ($saleAchievedTargetGrouped as $saleAchievedTarget) {
                $targetableData[$key] = [
                    'targetable_name' => $this->getStoreOrPromoterName(
                        $saleTarget,
                        $saleAchievedTarget->first()->targetable_type,
                        $saleAchievedTarget->first()->targetable_id
                    ),
                    'target_amount' => $saleAchievedTarget->first()->target_value,
                    'achieved_amount' => $saleAchievedTarget->sum('achieved_value'),
                ];
            }
        }

        return collect($targetableData)->values();
    }

    private function getStoreOrPromoterName(SaleTarget $saleTarget, string $moduleName, int $moduleId): string
    {
        if ($moduleName === ModelMapping::PROMOTER->name) {
            $promoter = $saleTarget->promoters->where('id', $moduleId)->first();

            if (! $promoter instanceof Promoter) {
                return '';
            }

            /** @var Employee $promoterEmployee */
            $promoterEmployee = $promoter->employee;

            return $promoterEmployee->getFullName() . '(' . $promoter->code . ')';
        }

        if ($moduleName === ModelMapping::LOCATION->name) {
            $location = $saleTarget->locations->where('id', $moduleId)->first();

            if ($location instanceof Location) {
                return $location->getNameWithCode();
            }
        }

        if ($moduleName === ModelMapping::COMPANY->name) {
            $companyQueries = resolve(CompanyQueries::class);
            $company = $companyQueries->getNameAndCodeById($moduleId);

            if ($company instanceof Company) {
                return $company->getNameWithCode();
            }
        }

        return '';
    }

    private function prepareTotalTargetAndAchieved(Collection $saleTargetTimeframes): array
    {
        $totalTargetAmount = 0;
        $totalAchievedAmount = 0;
        foreach ($saleTargetTimeframes as $saleTargetTimeframe) {
            $totalTargetAmount += $saleTargetTimeframe->sum('amount');
            $totalAchievedAmount += $saleTargetTimeframe->saleAchievedTargets->sum('achieved_value');
        }

        return [
            'target_amount' => $totalTargetAmount,
            'achieved_amount' => $totalAchievedAmount,
        ];
    }

    private function getSaleSeasonalChartData(
        SaleSeason $saleSeason,
        CompanyOwnerSeasonalComparisonApiData $companyOwnerSaleSeasonalChartApiData,
        int $companyId
    ): Collection {
        $filterData = [
            'start_date' => $saleSeason->start_date,
            'end_date' => $saleSeason->end_date,
            'location_id' => $companyOwnerSaleSeasonalChartApiData->location_id,
            'brand_id' => $companyOwnerSaleSeasonalChartApiData->brand_id,
        ];

        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);

        return $storeWiseDailyTotalQueries->getSaleSeasonalData($filterData, $companyId);
    }

    private function getSeasonalMemberData(
        SaleSeason $saleSeason,
        CompanyOwnerSeasonalMemberComparisonApiData $CompanyOwnerSeasonalMemberComparisonApiData,
        int $companyId
    ): Collection {
        $filterData = [
            'start_date' => $saleSeason->start_date,
            'end_date' => $saleSeason->end_date,
            'location_id' => $CompanyOwnerSeasonalMemberComparisonApiData->location_id,
            'brand_id' => $CompanyOwnerSeasonalMemberComparisonApiData->brand_id,
        ];

        $memberQueries = resolve(MemberQueries::class);

        return $memberQueries->getSeasonalMemberData($filterData, $companyId);
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
        CompanyOwnerSeasonalSalesComparisonApiData $companyOwnerSeasonalSalesComparisonApiData,
        int $companyId
    ): Collection {
        /** @var Carbon $startDate */
        $startDate = Carbon::createFromFormat('Y-m-d', $saleSeason->start_date);

        $filterData = [
            'start_date' => $startDate->subDays(10)->format('Y-m-d'),
            'end_date' => $saleSeason->start_date,
            'location_id' => $companyOwnerSeasonalSalesComparisonApiData->location_id,
            'brand_id' => $companyOwnerSeasonalSalesComparisonApiData->brand_id,
        ];

        $storeWiseDailyTotalQueries = resolve(StoreWiseDailyTotalQueries::class);

        return $storeWiseDailyTotalQueries->getAnalyticsForLastTenDaysOfSeasonalData($filterData, $companyId);
    }

    private function getSalesDataForChart(int $brandId, array $date, int $companyId, bool $refresh): array
    {
        $locationQueries = resolve(LocationQueries::class);
        $totalSalesByLocations = $locationQueries->getCachedStoresSalesForChart(
            $companyId,
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
        $currency = $currencyQueries->getByCompanyId($companyId);

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

    private function prepareRevenueView(
        CompanyOwnerRevenueViewApiData $companyOwnerRevenueViewApiData,
        int $companyId
    ): array {
        $now = Carbon::now();

        $dateRange = [$now->format('Y-m-d'), $now->format('Y-m-d')];

        if ($companyOwnerRevenueViewApiData->start_date && $companyOwnerRevenueViewApiData->end_date) {
            $dateRange = [$companyOwnerRevenueViewApiData->start_date, $companyOwnerRevenueViewApiData->end_date];
        }

        $refresh = (bool) $companyOwnerRevenueViewApiData->refresh;

        if ($refresh) {
            Cache::forget('index-refresh-date-time-for-revenue');
        }

        $brandQueries = resolve(BrandQueries::class);

        $brands = $brandQueries->getBrands($companyId);

        $brands->prepend([
            'id' => 0,
            'name' => 'All Brands',
        ]);

        $brandId = (int) $companyOwnerRevenueViewApiData->brand_id;

        [$totalSalesByLocations, $salesTotalData] = $this->getSalesDataForChart(
            $brandId,
            $dateRange,
            $companyId,
            $refresh
        );

        $brandQueries = resolve(BrandQueries::class);
        $totalSalesByBrands = $brandQueries->getCachedBrandsSalesForChart(
            $companyId,
            null,
            $brandId,
            $dateRange,
            $refresh
        );

        $categoryQueries = resolve(CategoryQueries::class);
        $totalSalesByCategories = $categoryQueries->getCachedCategoriesSalesForChart(
            $companyId,
            null,
            $brandId,
            $dateRange,
            $refresh
        );

        $styleQueries = resolve(StyleQueries::class);
        $totalSalesByStyles = $styleQueries->getCachedStylesSalesForChart(
            $companyId,
            null,
            $brandId,
            $dateRange,
            $refresh
        );

        $departmentQueries = resolve(DepartmentQueries::class);
        $totalSalesByDepartments = $departmentQueries->getCachedDepartmentSaleForChart(
            $companyId,
            null,
            $brandId,
            $dateRange,
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

        return [
            $totalSalesByLocationChartData,
            $totalSalesByBrandChartData,
            $totalSalesByCategoryChartData,
            $totalSalesByStyleChartData,
            $totalSalesByDepartmentChartData,
            $totalSalesByLocations,
            $salesTotalData,
            $dateRange,
            $brands,
            $brandId,
        ];
    }

    private function prepareStoreRevenueView(
        CompanyOwnerStoreRevenueViewApiData $companyOwnerStoreRevenueViewApiData,
        int $companyId
    ): array {
        $date = Carbon::now()->format('Y-m-d');

        if ($companyOwnerStoreRevenueViewApiData->date) {
            $date = $companyOwnerStoreRevenueViewApiData->date;
        }

        $refresh = (bool) $companyOwnerStoreRevenueViewApiData->refresh;

        if ($refresh) {
            Cache::forget('index-refresh-date-time-for-location-revenue');
        }

        $locationQueries = resolve(LocationQueries::class);
        $brandQueries = resolve(BrandQueries::class);

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

        $locationId = (int) $companyOwnerStoreRevenueViewApiData->location_id;

        $brandId = (int) $companyOwnerStoreRevenueViewApiData->brand_id;

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

        return [
            $brands,
            $locations,
            $locationId,
            $brandId,
            $totalCreditSalePendingAmount,
            $totalSalesByColorChartData,
            $totalSalesByBrandChartData,
            $totalSalesByCategoryChartData,
            $totalSalesByDepartmentChartData,
            $totalSalesByColorGroupChartData,
            $totalSalesBySizeChartData,
            $totalSalesByStyleChartData,
            $todayHourlySales,
            $yesterdayHourlySales,
            $todayHourlyTotalSales,
            $yesterdayHourlyTotalSales,
            $hourlyChartLabel,
            $totalSalesByLocations,
            $totalSalesByBrands,
            $grandTotalSalesByBrand,
            $totalSalesByColors,
            $grandTotalSalesByColor,
            $totalSalesByCategories,
            $grandTotalSalesByCategory,
            $totalSalesByDepartments,
            $grandTotalSalesByDepartment,
            $totalSalesByColorGroups,
            $grandTotalSalesByColorGroup,
            $totalSalesBySizes,
            $totalSalesByStyles,
            $grandTotalSalesBySize,
            $grandTotalSalesByStyle,
            $date,
        ];
    }
}
