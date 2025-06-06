<?php

declare(strict_types=1);

namespace App\Http\Controllers\StoreManager;

use App\Domains\Brand\BrandQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Color\Resources\DashboardStockOverviewTopSellingColorResource;
use App\Domains\Common\Services\DashboardService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Company\RoundOffConfiguration;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Dashboard\Exports\ProductStoresSalesDetailsExport;
use App\Domains\Inventory\Enums\Types;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\Enums\ProductStatuses;
use App\Domains\Product\Enums\SellingTypes;
use App\Domains\Product\ProductQueries;
use App\Domains\Product\Resources\DashboardStockOverviewTopSellingProductResource;
use App\Domains\Product\Resources\DashboardStockOverviewWorstSellingProductResource;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrderFulfillment\Enums\FulfillmentStatuses;
use App\Domains\Sale\SaleQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughFilterTypes;
use App\Domains\SellThroughAggregate\Enums\SellThroughIncludeTypes;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use App\Domains\StockTransfer\Enums\StatusTypes;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\Enums\TransferTypes;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
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

        $locationId = session('store_manager_selected_location_id');
        $companyId = session('store_manager_selected_location_company_id');

        $brandQueries = resolve(BrandQueries::class);
        $brands = $brandQueries->getBrands($companyId);

        $brands->prepend([
            'id' => 0,
            'name' => 'All Brands',
        ]);

        $brandId = (int) $request->input('brand_id');

        return Inertia::render('OperationalView', [
            'locationId' => $locationId,
            'date' => $date,
            'brandId' => $brandId,
            'brands' => $brands,
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

        $refresh = (bool) $request->input('refresh');

        if ($refresh) {
            Cache::forget('index-refresh-date-time-store-manager');
        }

        $companyId = session('store_manager_selected_location_company_id');
        $locationId = session('store_manager_selected_location_id');
        $brandId = (int) $request->input('brand_id');

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
                'index-refresh-date-time-store-manager',
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

        $refresh = (bool) $request->input('refresh');
        $companyId = session('store_manager_selected_location_company_id');
        $locationId = session('store_manager_selected_location_id');
        $brandId = (int) $request->input('brand_id');

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

        $refresh = (bool) $request->input('refresh');
        $companyId = session('store_manager_selected_location_company_id');
        $locationId = session('store_manager_selected_location_id');
        $brandId = (int) $request->input('brand_id');

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
        $companyId = session('store_manager_selected_location_company_id');
        $locationId = session('store_manager_selected_location_id');
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
        $companyId = session('store_manager_selected_location_company_id');
        $locationId = session('store_manager_selected_location_id');
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

        $companyId = session('store_manager_selected_location_company_id');
        $locationId = session('store_manager_selected_location_id');
        $brandId = (int) $request->input('brand_id');
        $refresh = (bool) $request->input('refresh');

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

        $companyId = session('store_manager_selected_location_company_id');
        $locationId = session('store_manager_selected_location_id');
        $brandId = (int) $request->input('brand_id');
        $refresh = (bool) $request->input('refresh');

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

        $companyId = session('store_manager_selected_location_company_id');
        $locationId = session('store_manager_selected_location_id');
        $brandId = (int) $request->input('brand_id');
        $refresh = (bool) $request->input('refresh');

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

        $companyId = session('store_manager_selected_location_company_id');
        $locationId = session('store_manager_selected_location_id');
        $brandId = (int) $request->input('brand_id');
        $refresh = (bool) $request->input('refresh');

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
            Cache::forget('index-refresh-date-time-for-store-revenue-for-store-manager');
        }

        $locationQueries = resolve(LocationQueries::class);
        $brandQueries = resolve(BrandQueries::class);

        $companyId = session('store_manager_selected_location_company_id');
        $locationId = session('store_manager_selected_location_id');

        $brands = $brandQueries->getBrands($companyId);

        $brands->prepend([
            'id' => 0,
            'name' => 'All Brands',
        ]);

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
            $refresh
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

        [$todayHourlySales, $yesterdayHourlySales,$todayHourlyTotalSales, $yesterdayHourlyTotalSales, $hourlyChartLabel] = $dashboardService->getHourlyBasedData(
            $companyId,
            $locationId,
            $brandId,
            $date,
            $refresh
        );

        $saleQueries = resolve(SaleQueries::class);
        $totalCreditSalePendingAmount = $saleQueries->totalCreditSalePendingAmount($companyId, $locationId);

        $totalSales = $totalSalesByLocations->sum('total_sales');
        $totalSales += RoundOffConfiguration::roundOffCalculationFor((string) $totalSales);

        return Inertia::render('StoreRevenueView', [
            'locationId' => $locationId,
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
            'totalSales' => $totalSales,
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
            'brands' => $brands,
            'brandId' => $brandId,
            'lastUpdate' => Cache::remember(
                'index-refresh-date-time-for-store-revenue-for-store-manager',
                900,
                fn (): string => now()->format('d-m-y h:i:s A')
            ),
            'storeRevenueDashboardTableFilterTypes' => StoreRevenueDashboardTableFilterTypes::getFormattedArrayForStaticUse(),
        ]);
    }

    public function printStoreRevenue(Request $request): string
    {
        $date = $request->input('date');
        [$data, $totalData, $brandName, $company, $filterType, $locationName, $currencySymbol] = $this->getDataForExportAndPdf(
            $request
        );

        return view('prints.product_dashboard_store_sales_details', [
            'salesData' => $data,
            'selectedDate' => $date,
            'totalData' => $totalData,
            'filterType' => $filterType,
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
        $companyId = session('store_manager_selected_location_company_id');
        $locationId = session('store_manager_selected_location_id');
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

    public function stockOverview(Request $request): Response
    {
        $companyId = session('store_manager_selected_location_company_id');
        $brandQueries = resolve(BrandQueries::class);
        $brands = $brandQueries->getBrands($companyId);

        $brands->prepend([
            'id' => 0,
            'name' => 'All Brands',
        ]);

        $brandId = (int) $request->input('brand_id');

        return Inertia::render('StockOverview', [
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
            'brands' => $brands,
            'brandId' => $brandId,
            'fulfillmentStatuses' => FulfillmentStatuses::generateStaticCasesArray(),
            'purchaseOrderStatuses' => Statuses::generateStaticCasesArray(),
            'orderTypes' => OrderTypes::getFormattedArrayForStaticUse(),
            'stockTransferStatuses' => StatusTypes::generateStaticCasesArray(),
            'activeStatus' => ProductStatuses::ACTIVE->value,
            'sellingType' => SellingTypes::SELLING->value,
        ]);
    }

    public function getLowStockOverview(Request $request): array
    {
        $refresh = (bool) $request->get('refresh');

        $filterData = [
            'location_id' => session('store_manager_selected_location_id'),
        ];

        $inventoryQueries = resolve(InventoryQueries::class);
        $lowStockCompanyCount = $inventoryQueries->getCompanyLowStockItems(
            $filterData,
            session('store_manager_selected_location_company_id'),
            $refresh
        );
        $lowStockLocationCount = $inventoryQueries->getLocationLowStockItems(
            $filterData,
            session('store_manager_selected_location_company_id'),
            $refresh
        );
        $lowStockProductCount = $inventoryQueries->getProductLowStockItems(
            $filterData,
            session('store_manager_selected_location_company_id'),
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
        $filterData = [
            'location_id' => session('store_manager_selected_location_id'),
        ];

        $refresh = (bool) $request->get('refresh');

        $inventoryQueries = resolve(InventoryQueries::class);
        $noStockItemCount = $inventoryQueries->getNoStockItems(
            $filterData,
            session('store_manager_selected_location_company_id'),
            $refresh
        );

        return [
            'noStockItemCount' => $noStockItemCount,
        ];
    }

    public function getNegativeStockStockOverview(Request $request): array
    {
        $filterData = [
            'location_id' => session('store_manager_selected_location_id'),
        ];

        $refresh = (bool) $request->get('refresh');

        $inventoryQueries = resolve(InventoryQueries::class);
        $noStockItemCount = $inventoryQueries->getNegativeStockItems(
            $filterData,
            session('store_manager_selected_location_company_id'),
            $refresh
        );

        return [
            'negativeStockItemCount' => $noStockItemCount,
        ];
    }

    public function getPurchaseRequest(): array
    {
        $filterData = [
            'location_id' => session('store_manager_selected_location_id'),
            'order_type' => OrderTypes::PURCHASE_REQUEST->value,
        ];

        $dashboardService = resolve(DashboardService::class);
        $purchaseRequests = $dashboardService->getPurchaseRequestCount(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        $closedStatus = Statuses::getFormattedCaseName(Statuses::CLOSED->value);

        if (isset($purchaseRequests[$closedStatus])) {
            unset($purchaseRequests[$closedStatus]);
        }

        return [
            'purchaseRequests' => $purchaseRequests,
        ];
    }

    public function getTransferRequest(): array
    {
        $filterData = [
            'location_id' => session('store_manager_selected_location_id'),
            'order_type' => OrderTypes::TRANSFER_REQUEST->value,
        ];

        $dashboardService = resolve(DashboardService::class);
        $transferRequests = $dashboardService->getPurchaseRequestCount(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        $closedStatus = Statuses::getFormattedCaseName(Statuses::CLOSED->value);

        if (isset($transferRequests[$closedStatus])) {
            unset($transferRequests[$closedStatus]);
        }

        return [
            'transferRequests' => $transferRequests,
        ];
    }

    public function getSalesOrder(): array
    {
        $filterData = [
            'location_id' => session('store_manager_selected_location_id'),
            'order_type' => OrderTypes::SALES_ORDER->value,
        ];

        $dashboardService = resolve(DashboardService::class);
        $salesOrders = $dashboardService->getPurchaseOrderCount(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        $salesDeliveryOrders = $dashboardService->getPurchaseOrderFulfillmentCount(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return [
            'salesOrders' => $salesOrders,
            'salesDeliveryOrders' => $salesDeliveryOrders,
        ];
    }

    public function getPurchaseOrder(): array
    {
        $filterData = [
            'location_id' => session('store_manager_selected_location_id'),
            'order_type' => OrderTypes::PURCHASE_ORDER->value,
        ];

        $dashboardService = resolve(DashboardService::class);
        $purchaseOrders = $dashboardService->getPurchaseOrderCount(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        $purchaseDeliveryOrders = $dashboardService->getPurchaseOrderFulfillmentCount(
            $filterData,
            session('store_manager_selected_location_company_id')
        );

        return [
            'purchaseOrders' => $purchaseOrders,
            'purchaseDeliveryOrders' => $purchaseDeliveryOrders,
        ];
    }

    public function getTransferOrder(): array
    {
        $transferOrders = [];
        $stockTransferQueries = resolve(StockTransferQueries::class);

        $filterData = [
            'location_id' => session('store_manager_selected_location_id'),
            'transfer_type' => null,
        ];

        $transferOrderStatusCounts = $stockTransferQueries->storeManagerTransferOrderStatusCount(
            [StockTransferTypes::TRANSFER_ORDER->value],
            $filterData,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );

        $filterData['transfer_type'] = TransferTypes::TRANSFER_IN->value;
        $transferInOrderStatusCounts = $stockTransferQueries->storeManagerTransferOrderStatusCount(
            [StockTransferTypes::TRANSFER_ORDER->value],
            $filterData,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );

        $filterData['transfer_type'] = TransferTypes::TRANSFER_OUT->value;
        $transferOutOrderStatusCounts = $stockTransferQueries->storeManagerTransferOrderStatusCount(
            [StockTransferTypes::TRANSFER_ORDER->value],
            $filterData,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );

        foreach ($transferOrderStatusCounts as $transferOrderStatusCount) {
            $statusName = StatusTypes::getFormattedCaseName($transferOrderStatusCount->status);
            $transferOrders[] = [
                'id' => $transferOrderStatusCount->status,
                'name' => $statusName,
                'count' => $transferOrderStatusCount->count,
                'transfer_in_count' => $transferInOrderStatusCounts->where(
                    'status',
                    $transferOrderStatusCount->status
                )->first()?->count,
                'transfer_out_count' => $transferOutOrderStatusCounts->where(
                    'status',
                    $transferOrderStatusCount->status
                )->first()?->count,
            ];
        }

        return [
            'transferOrders' => $transferOrders,
        ];
    }

    public function getRequestOrder(): array
    {
        $requestOrders = [];
        $stockTransferQueries = resolve(StockTransferQueries::class);

        $filterData = [
            'location_id' => session('store_manager_selected_location_id'),
            'transfer_type' => null,
        ];

        $requestOrderStatusCounts = $stockTransferQueries->storeManagerRequestOrderStatusCount(
            [StockTransferTypes::REQUEST_ORDER->value],
            $filterData,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );

        $filterData['transfer_type'] = TransferTypes::TRANSFER_IN->value;
        $transferInOrderStatusCounts = $stockTransferQueries->storeManagerTransferOrderStatusCount(
            [StockTransferTypes::REQUEST_ORDER->value],
            $filterData,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );

        $filterData['transfer_type'] = TransferTypes::TRANSFER_OUT->value;
        $transferOutOrderStatusCounts = $stockTransferQueries->storeManagerTransferOrderStatusCount(
            [StockTransferTypes::REQUEST_ORDER->value],
            $filterData,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );

        foreach ($requestOrderStatusCounts as $requestOrderStatusCount) {
            $statusName = StatusTypes::getFormattedCaseName($requestOrderStatusCount->status);
            $requestOrders[] = [
                'id' => $requestOrderStatusCount->status,
                'name' => $statusName,
                'count' => $requestOrderStatusCount->count,
                'transfer_in_count' => $transferInOrderStatusCounts->where(
                    'status',
                    $requestOrderStatusCount->status
                )->first()?->count,
                'transfer_out_count' => $transferOutOrderStatusCounts->where(
                    'status',
                    $requestOrderStatusCount->status
                )->first()?->count,
            ];
        }

        return [
            'requestOrders' => $requestOrders,
        ];
    }

    public function getTransferOut(): array
    {
        return [
            'transferOuts' => $this->preparedStockTransferCounts(TransferTypes::TRANSFER_OUT->value),
        ];
    }

    public function getTransferIn(): array
    {
        return [
            'transferIns' => $this->preparedStockTransferCounts(TransferTypes::TRANSFER_IN->value),
        ];
    }

    public function getThisMonthTopSellingProducts(Request $request): array
    {
        $refresh = (bool) $request->input('refresh');

        if ($refresh) {
            Cache::forget('index-refresh-date-time-for-stock-overview-for-store-manager');
        }

        $brandId = (int) $request->input('brand_id');
        $productQueries = resolve(ProductQueries::class);
        $thisMonthTopSellingProducts = $productQueries->getCachedTopSellingProduct(
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id'),
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
                'index-refresh-date-time-for-stock-overview-for-store-manager',
                900,
                fn (): string => now()->format('d-m-y h:i:s A')
            ),
        ];
    }

    public function getThisYearTopSellingProducts(Request $request): array
    {
        $brandId = (int) $request->input('brand_id');
        $refresh = (bool) $request->input('refresh');

        $productQueries = resolve(ProductQueries::class);
        $thisYearTopSellingProducts = $productQueries->getCachedTopSellingProduct(
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id'),
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
        $refresh = (bool) $request->input('refresh');

        if ($refresh) {
            Cache::forget('index-refresh-date-time-for-stock-overview-for-store-manager');
        }

        $brandId = (int) $request->input('brand_id');
        $productQueries = resolve(ProductQueries::class);
        $thisMonthWorstSellingProducts = $productQueries->getCachedWorstSellingProduct(
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id'),
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
                'index-refresh-date-time-for-stock-overview-for-store-manager',
                900,
                fn (): string => now()->format('d-m-y h:i:s A')
            ),
        ];
    }

    public function getThisYearWorstSellingProducts(Request $request): array
    {
        $brandId = (int) $request->input('brand_id');
        $refresh = (bool) $request->input('refresh');

        $productQueries = resolve(ProductQueries::class);
        $thisYearWorstSellingProducts = $productQueries->getCachedWorstSellingProduct(
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id'),
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
        $brandId = (int) $request->input('brand_id');
        $refresh = (bool) $request->input('refresh');

        $colorQueries = resolve(ColorQueries::class);
        $thisMonthTopSellingColors = $colorQueries->getCachedTopSellingColor(
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id'),
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
        $brandId = (int) $request->input('brand_id');
        $refresh = (bool) $request->input('refresh');

        $colorQueries = resolve(ColorQueries::class);
        $thisYearTopSellingColors = $colorQueries->getCachedTopSellingColor(
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id'),
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

    public function getTopRankingProducts(): array
    {
        $filterData = [
            'search_text' => null,
            'filter_by' => SellThroughFilterTypes::ALL->value,
            'location_ids' => [session('store_manager_selected_location_id')],
            'sort_by' => 'sell_through',
            'sort_direction' => 'desc',
            'date' => now()->format('Y-m-d'),
            'date_range' => null,
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
            session('store_manager_selected_location_company_id')
        );

        return [
            'topRankingProducts' => $topRankingProducts,
        ];
    }

    private function preparedStockTransferCounts(int $transferType): array
    {
        $filterData = [
            'location_id' => session('store_manager_selected_location_id'),
            'transfer_type' => $transferType,
        ];

        $stockTransferQueries = resolve(StockTransferQueries::class);

        $transferOrderStatusCounts = $stockTransferQueries->storeManagerTransferInAndOutStatusCount(
            $filterData,
            session('store_manager_selected_location_company_id'),
            session('store_manager_selected_location_id')
        );

        $transferIns = [];

        foreach ($transferOrderStatusCounts as $transferOrderStatusCount) {
            $statusName = StatusTypes::getFormattedCaseName($transferOrderStatusCount->status);
            $transferIns[] = [
                'id' => $transferOrderStatusCount->status,
                'name' => $statusName,
                'count' => $transferOrderStatusCount->count,
            ];
        }

        return $transferIns;
    }
}
