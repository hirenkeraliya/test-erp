<?php

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Services\DashboardService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Domains\SaleSeason\SaleSeasonQueries;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
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
use App\Domains\User\Enums\UserTypes;
use App\Http\Controllers\Api\User\DashboardController;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Member;
use App\Models\Region;
use App\Models\SaleSeason;
use App\Models\StoreWiseDailyTotal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $this->brand = Brand::factory()->make([
        'id' => 1,
    ]);

    $this->company->brands = collect($this->brand);

    $this->employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'designation_id' => 1,
    ]);

    $this->user = User::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
        'type_id' => UserTypes::COMPANY_OWNER->value,
    ]);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
});

test('calls the index method and returns record', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getStoreWithBasicColumns')
        ->once()
        ->with($this->company->id)
        ->andReturn(collect($this->location));
    });

    $this->mock(BrandQueries::class, function ($mock): void {
        $mock->shouldReceive('getBrands')
        ->once()
        ->with($this->company->id)
        ->andReturn(collect($this->brand));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->index($request);

    expect($response['locations'])
        ->toBeInstanceOf(Collection::class);

    expect($response['brands'])
        ->toBeInstanceOf(Collection::class);
});

test('calls the getOperationalAtvChartData method and returns proper response', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $data['date'] = null;
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerOperationalAtvChartApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getMonthWiseSalesDetails')
            ->once()
            ->andReturn(collect([]));
        $mock->shouldReceive('getATVChartData')
            ->once()
            ->andReturn([0, 10]);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getOperationalAtvChartData($request, $apiData);

    expect($response['atvChartData'])
       ->toHaveKey('0', 0)
       ->toHaveKey('1', 10);
});

test('calls the getOperationalUptChartData method and returns proper response', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $data['date'] = null;
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerOperationalUptChartApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getMonthWiseSalesDetails')
            ->once()
            ->andReturn(collect([]));
        $mock->shouldReceive('getUPTChartData')
            ->once()
            ->andReturn([0, 2]);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getOperationalUptChartData($request, $apiData);

    expect($response['uptChartData'])
        ->toHaveKey('0', 0)
        ->toHaveKey('1', 2);
});

test('calls the getOperationalRevenueChartData method and returns proper response', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $data['date'] = null;
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerOperationalRevenueChartApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getMonthWiseSalesDetails')
            ->twice()
            ->andReturn(collect([]));

        $mock->shouldReceive('getRevenueChartDataWithLastYear')
            ->once()
            ->andReturn([
                'current_year_data' => [],
                'last_year_data' => [],
                'labels' => [],
            ]);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getOperationalRevenueChartData($request, $apiData);

    expect($response['revenueChartData'])
        ->toHaveKeys(['current_year_data', 'last_year_data', 'labels']);
});

test('calls the getOperationalSalesCount method and returns proper response', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $data['date'] = null;
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerOperationalSalesApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getTodaySalesDetails')
            ->once()
            ->andReturn([
                'totalAmount' => 10,
                'todayTotalSalePercentage' => 10,
                'previousYearTodaySaleAmount' => 10,
                'previousYearTodaySalePercentage' => 10,
                'totalSalesCount' => 10,
                'totalUnitsSold' => 10,
                'todayUpt' => 10,
                'todayAtv' => 10,
            ]);
        $mock->shouldReceive('getThisMonthSalesDetails')
            ->once()
            ->andReturn([
                'totalAmount' => 10,
                'mtdTotalSalePercentage' => 10,
                'previousYearMonthSaleAmount' => 10,
                'previousYearMonthSalePercentage' => 10,
                'totalSalesCount' => 10,
                'totalUnitsSold' => 10,
                'mtdUpt' => 10,
                'mtdAtv' => 10,
            ]);
        $mock->shouldReceive('getThisYearSalesDetails')
            ->once()
            ->andReturn([
                'totalAmount' => 10,
                'ytdTotalSalePercentage' => 10,
                'previousYearTillTodaySaleAmount' => 10,
                'previousYearTillTodaySalePercentage' => 10,
                'totalSalesCount' => 10,
                'totalUnitsSold' => 10,
                'ytdUpt' => 10,
                'ytdAtv' => 10,
            ]);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getOperationalSalesCount($request, $apiData);

    expect($response['salesCount'])
        ->toHaveKey('todayTotalSaleAmount', 10)
        ->toHaveKey('todayTotalSaleAmount', 10)
        ->toHaveKey('todayTotalSalePercentage', 10)
        ->toHaveKey('previousYearTodaySaleAmount', 10)
        ->toHaveKey('previousYearTodaySalePercentage', 10)
        ->toHaveKey('mtdTotalSaleAmount', 10)
        ->toHaveKey('mtdTotalSalePercentage', 10)
        ->toHaveKey('previousYearMonthSaleAmount', 10)
        ->toHaveKey('previousYearMonthSalePercentage', 10)
        ->toHaveKey('ytdTotalSaleAmount', 10)
        ->toHaveKey('ytdTotalSalePercentage', 10)
        ->toHaveKey('previousYearTillTodaySaleAmount', 10)
        ->toHaveKey('previousYearTillTodaySalePercentage', 10);
});

test('calls the getOperationalTodaySales method and returns proper response', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $data['date'] = null;
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerOperationalTodaySalesApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getTodaySalesDetails')
            ->once()
            ->andReturn([
                'totalAmount' => 10,
                'todayTotalSalePercentage' => 10,
                'previousYearTodaySaleAmount' => 10,
                'previousYearTodaySalePercentage' => 10,
                'totalSalesCount' => 10,
                'totalUnitsSold' => 10,
                'todayUpt' => 10,
                'todayAtv' => 10,
            ]);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getOperationalTodaySales($request, $apiData);

    expect($response['today'])
        ->toHaveKey('totalSale', 10)
        ->toHaveKey('totalUnitsSold', 10)
        ->toHaveKey('upt', 10)
        ->toHaveKey('atv', 10);
});

test('calls the getOperationalThisMonthSales method and returns proper response', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $data['date'] = null;
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerOperationalThisMonthSalesApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getThisMonthSalesDetails')
            ->once()
            ->andReturn([
                'totalAmount' => 10,
                'todayTotalSalePercentage' => 10,
                'previousYearTodaySaleAmount' => 10,
                'previousYearTodaySalePercentage' => 10,
                'totalSalesCount' => 10,
                'totalUnitsSold' => 10,
                'mtdUpt' => 10,
                'mtdAtv' => 10,
            ]);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getOperationalThisMonthSales($request, $apiData);

    expect($response['thisMonth'])
        ->toHaveKey('totalSale', 10)
        ->toHaveKey('totalUnitsSold', 10)
        ->toHaveKey('upt', 10)
        ->toHaveKey('atv', 10);
});

test('calls the getOperationalThisYearSales method and returns proper response', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $data['date'] = null;
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerOperationalThisYearSalesApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getThisYearSalesDetails')
            ->once()
            ->andReturn([
                'totalAmount' => 10,
                'todayTotalSalePercentage' => 10,
                'previousYearTodaySaleAmount' => 10,
                'previousYearTodaySalePercentage' => 10,
                'totalSalesCount' => 10,
                'totalUnitsSold' => 10,
                'ytdUpt' => 10,
                'ytdAtv' => 10,
            ]);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getOperationalThisYearSales($request, $apiData);

    expect($response['thisYear'])
        ->toHaveKey('totalSale', 10)
        ->toHaveKey('totalUnitsSold', 10)
        ->toHaveKey('upt', 10)
        ->toHaveKey('atv', 10);
});

test('calls the getOperationalTopPromoters method and returns proper response', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $data['date'] = null;
    $data['location_id'] = 1;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerOperationalTopPromotersApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getTopPromoters')
            ->once()
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getOperationalTopPromoters($request, $apiData);

    expect($response['topPromoters'])->toBeInstanceOf(Collection::class);
});

test('calls the getOperationalThisYearTopPromoters method and returns proper response', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $data['date'] = null;
    $data['location_id'] = 1;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerOperationalThisYearTopPromotersApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getYearlyTopPromoters')
            ->once()
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getOperationalThisYearTopPromoters($request, $apiData);

    expect($response['thisYearTopPromoters'])->toBeInstanceOf(Collection::class);
});

test('calls the saleTarget method and returns proper response', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(SaleTargetQueries::class, function ($mock): void {
        $mock->shouldReceive('getSaleTargetWithAchieved')
            ->once()
            ->with($this->company->id)
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->saleTarget($request);

    expect($response['saleTargets'])
        ->toBeArray();

    expect($response['statusStaticType'])
        ->toBeArray();
});

test('calls the seasonal method and returns proper response', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(SaleSeasonQueries::class, function ($mock): void {
        $mock->shouldReceive('getWithBasicColumns')
            ->once()
            ->with($this->company->id)
            ->andReturn(collect([]));
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getStoreWithBasicColumns')
            ->once()
            ->with($this->company->id)
            ->andReturn(collect([]));
    });

    $this->mock(BrandQueries::class, function ($mock): void {
        $mock->shouldReceive('getBrands')
            ->once()
            ->with($this->company->id)
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->seasonal($request);

    expect($response['locations'])
        ->toBeInstanceOf(Collection::class);

    expect($response['brands'])
            ->toBeInstanceOf(Collection::class);

    expect($response['saleSeasons'])
            ->toBeInstanceOf(Collection::class);
});

test('calls the getSeasonalData method and returns proper response', function (): void {
    $date = Carbon::now();
    $saleSeason = SaleSeason::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'test',
        'start_date' => $date,
        'end_date' => $date,
    ]);

    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $data['sale_season_id'] = $saleSeason->id;
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $apiData = new CompanyOwnerSeasonalApiData(...$data);

    $storeWiseDailyTotals = StoreWiseDailyTotal::factory()->make([
        'date' => $date,
        'company_id' => 1,
        'location_id' => 1,
        'brand_id' => 1,
        'counter_update_id' => 1,
        'total_sales_count' => 1,
        'total_units_sold' => 1,
        'total_sales_amount' => 10,
    ]);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(SaleSeasonQueries::class, function ($mock) use ($saleSeason): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($saleSeason);
    });

    $this->mock(StoreWiseDailyTotalQueries::class, function ($mock) use ($storeWiseDailyTotals): void {
        $mock->shouldReceive('getSaleSeasonalData')
            ->once()
            ->andReturn(collect([$storeWiseDailyTotals]));
    });

    $this->mock(SaleDiscountQueries::class, function ($mock): void {
        $mock->shouldReceive('getSaleDiscountBasedOnFilterForSaleSeasonalSum')
            ->once()
            ->andReturn(10);
    });

    $this->mock(SaleItemDiscountQueries::class, function ($mock): void {
        $mock->shouldReceive('getSaleItemDiscountBasedOnFilterForSaleSeasonalSum')
            ->once()
            ->andReturn(10);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getSeasonalData($request, $apiData);

    expect($response)
        ->toHaveKey('sales', 10)
        ->toHaveKey('total_receipt', 1)
        ->toHaveKey('total_units_sold', 1)
        ->toHaveKey('total_discounts', 20);
});

test('calls the getSeasonalChartData method and returns proper response', function (): void {
    $date = Carbon::now();
    $saleSeason = SaleSeason::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'test',
        'start_date' => $date,
        'end_date' => $date,
    ]);

    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $data['sale_season_id'] = $saleSeason->id;
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $apiData = new CompanyOwnerSeasonalChartApiData(...$data);

    $storeWiseDailyTotals = StoreWiseDailyTotal::factory()->make([
        'date' => $date,
        'company_id' => 1,
        'location_id' => 1,
        'brand_id' => 1,
        'counter_update_id' => 1,
        'total_sales_count' => 1,
        'total_units_sold' => 1,
        'total_sales_amount' => 10,
    ]);

    $location = Location::factory()->make([
        'name' => 'Test',
        'company_id' => 1,
        'region_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $location->region = Region::factory()->make([
        'company_id' => 1,
    ]);

    $brand = Brand::factory()->make([
        'company_id' => 1,
    ]);

    $storeWiseDailyTotals->location = $location;
    $storeWiseDailyTotals->brand = $brand;

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(SaleSeasonQueries::class, function ($mock) use ($saleSeason): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($saleSeason);
    });

    $chartData = [
        'data' => [],
        'labels' => [],
    ];

    $this->mock(DashboardService::class, function ($mock) use ($chartData): void {
        $mock->shouldReceive('getCachedSeasonalTopFiveColorsSalesForChart')
            ->once()
            ->andReturn($chartData);
        $mock->shouldReceive('getCachedSeasonalTopFiveCategorySalesForChart')
            ->once()
            ->andReturn($chartData);
        $mock->shouldReceive('getCachedSeasonalTopFiveStyleSalesForChart')
            ->once()
            ->andReturn($chartData);
        $mock->shouldReceive('getCachedSeasonalTopFiveDepartmentSalesForChart')
            ->once()
            ->andReturn($chartData);
        $mock->shouldReceive('getCachedSeasonalTopFiveColorGroupSalesForChart')
            ->once()
            ->andReturn($chartData);
        $mock->shouldReceive('getCachedSeasonalTopFiveSizeSalesForChart')
            ->once()
            ->andReturn($chartData);
        $mock->shouldReceive('getCachedWeekDistributionColorForChart')
            ->once()
            ->andReturn($chartData);
        $mock->shouldReceive('getCachedStockWithSizeForChart')
            ->once()
            ->andReturn($chartData);
    });

    $this->mock(StoreWiseDailyTotalQueries::class, function ($mock) use ($storeWiseDailyTotals): void {
        $mock->shouldReceive('getSaleSeasonalData')
            ->once()
            ->andReturn(collect([$storeWiseDailyTotals]));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getSeasonalChartData($request, $apiData);

    $saleWeekWiseChartData = [
        'data' => [
            [
                'data' => [$storeWiseDailyTotals->total_sales_amount],
                'name' => 'Sales',
                'type' => 'bar',
            ],
            [
                'data' => [$storeWiseDailyTotals->total_sales_count],
                'name' => 'Orders',
                'type' => 'bar',
            ],
        ],
        'labels' => ['Week 1'],
        'legendData' => ['Sales', 'Orders'],
    ];

    $saleStoreWiseChartData = [
        'data' => [
            [
                'data' => [$storeWiseDailyTotals->total_sales_amount],
                'name' => 'Sales',
                'type' => 'bar',
            ],
            [
                'data' => [$storeWiseDailyTotals->total_sales_count],
                'name' => 'Orders',
                'type' => 'bar',
            ],
        ],
        'labels' => ['Test'],
        'code_based_labels' => [$location->code],
        'legendData' => ['Sales', 'Orders'],
    ];

    $responseDataShouldBe = [
        'brand_wise_chart_data' => [
            'labels' => [$brand->name],
            'data' => [10],
        ],
        'region_wise_chart_data' => [
            'labels' => [$location->region->name],
            'data' => [10],
        ],
        'color_top_five_chart' => $chartData,
        'category_top_five_chart' => $chartData,
        'style_top_five_chart' => $chartData,
        'department_top_five_chart' => $chartData,
        'color_group_top_five_chart' => $chartData,
        'size_top_five_chart' => $chartData,
        'week_based_color_chart' => $chartData,
        'stock_with_size_chart' => $chartData,
        'sale_week_wise_chart_data' => $saleWeekWiseChartData,
        'sale_store_wise_chart_data' => $saleStoreWiseChartData,
    ];

    expect($response)
        ->toBe($responseDataShouldBe);
});

test('calls the getSeasonalTotalDiscounts method and returns proper response', function (): void {
    $date = Carbon::now();
    $saleSeason = SaleSeason::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'name' => 'test',
        'start_date' => $date,
        'end_date' => $date,
    ]);

    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $data['sale_season_id'] = $saleSeason->id;
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $apiData = new CompanyOwnerSeasonalTotalDiscountApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(SaleSeasonQueries::class, function ($mock) use ($saleSeason): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($saleSeason);
    });

    $this->mock(SaleDiscountQueries::class, function ($mock): void {
        $mock->shouldReceive('getSaleDiscountBasedOnFilterForSaleSeasonal')
            ->once()
            ->andReturn(collect([]));
    });

    $this->mock(SaleItemDiscountQueries::class, function ($mock): void {
        $mock->shouldReceive('getSaleItemDiscountBasedOnFilterForSaleSeasonal')
            ->once()
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getSeasonalTotalDiscounts($request, $apiData);

    expect($response)
        ->toHaveKey('discounts');
});

test('calls the getSeasonalComparisonData method and returns proper response', function (): void {
    $date = Carbon::now();
    $saleSeason = SaleSeason::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'name' => 'test',
        'start_date' => $date,
        'end_date' => $date,
    ]);

    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $data['sale_season_id'] = $saleSeason->id;
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $data['comparison_x_sale_season_id'] = 1;
    $data['comparison_y_sale_season_id'] = 2;
    $apiData = new CompanyOwnerSeasonalComparisonApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(SaleSeasonQueries::class, function ($mock) use ($saleSeason): void {
        $mock->shouldReceive('getById')
            ->times(2)
            ->andReturn($saleSeason);
    });

    $this->mock(StoreWiseDailyTotalQueries::class, function ($mock): void {
        $mock->shouldReceive('getSaleSeasonalData')
            ->twice()
            ->andReturn(collect([]));
    });

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getSeasonalComparisonData($request, $apiData);

    expect($response)
        ->toHaveKeys(['comparisonChartData', 'comparisonData']);
});

test('calls the getSeasonalMemberComparisonData method and returns proper response', function (): void {
    $date = Carbon::now();

    $saleSeason1 = SaleSeason::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'name' => 'test',
        'start_date' => $date,
        'end_date' => $date,
    ]);

    $saleSeason2 = SaleSeason::factory()->make([
        'id' => 2,
        'company_id' => $this->company->id,
        'name' => 'test2',
        'start_date' => $date,
        'end_date' => $date,
    ]);

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'created_location_id' => 1,
    ]);

    $member['date'] = Carbon::parse($date)->format('Y-m-d');
    $member['members_count'] = 1;

    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $data['sale_season_id'] = $saleSeason1->id;
    $data['location_id'] = 0;
    $data['brand_id'] = 0;
    $data['comparison_x_sale_season_id'] = 1;
    $data['comparison_y_sale_season_id'] = 2;
    $apiData = new CompanyOwnerSeasonalMemberComparisonApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(SaleSeasonQueries::class, function ($mock) use ($saleSeason1, $saleSeason2): void {
        $mock->shouldReceive('getById')
            ->twice()
            ->andReturn($saleSeason1, $saleSeason2);
    });

    $this->mock(MemberQueries::class, function ($mock) use ($member): void {
        $mock->shouldReceive('getSeasonalMemberData')
            ->twice()
            ->andReturn(collect([$member]));
    });

    $data = [];

    $data[0]['name'] = $saleSeason1->name;
    $data[0]['type'] = 'line';
    $data[0]['data'] = [$member['members_count']];

    $data[1]['name'] = $saleSeason2->name;
    $data[1]['type'] = 'line';
    $data[1]['data'] = [$member['members_count']];

    $responseDataShouldBe = [
        'comparisonSeasonalMemberChartData' => [
            'data' => $data,
            'legendData' => [$saleSeason1->name, $saleSeason2->name],
            'labels' => ['Week 1'],
        ],
    ];

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getSeasonalMemberComparisonData($request, $apiData);

    expect($response)
        ->toBe($responseDataShouldBe);
});

test('calls the getSeasonalSalesComparisonData method and returns proper response', function (): void {
    $date = Carbon::now()->format('Y-m-d');
    $saleSeason1 = SaleSeason::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'name' => 'test',
        'start_date' => $date,
        'end_date' => $date,
    ]);

    $saleSeason2 = SaleSeason::factory()->make([
        'id' => 2,
        'company_id' => $this->company->id,
        'name' => 'test2',
        'start_date' => $date,
        'end_date' => $date,
    ]);

    $storeWiseDailyTotals = StoreWiseDailyTotal::factory()->make([
        'date' => $date,
        'company_id' => 1,
        'location_id' => 1,
        'brand_id' => 1,
        'counter_update_id' => 1,
        'total_sales_count' => 1,
        'total_units_sold' => 1,
        'total_sales_amount' => 10,
    ]);

    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $data['sale_season_id'] = $saleSeason1->id;
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $data['comparison_x_sale_season_id'] = 1;
    $data['comparison_y_sale_season_id'] = 2;
    $apiData = new CompanyOwnerSeasonalSalesComparisonApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(SaleSeasonQueries::class, function ($mock) use ($saleSeason1, $saleSeason2): void {
        $mock->shouldReceive('getById')
            ->twice()
            ->andReturn($saleSeason1, $saleSeason2);
    });

    $this->mock(StoreWiseDailyTotalQueries::class, function ($mock) use ($storeWiseDailyTotals): void {
        $mock->shouldReceive('getAnalyticsForLastTenDaysOfSeasonalData')
            ->twice()
            ->andReturn(collect([$storeWiseDailyTotals]));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getSeasonalSalesComparisonData($request, $apiData);

    expect($response)->toHaveKey('comparisonSeasonalSalesChartData');

    expect($response['comparisonSeasonalSalesChartData']['data'][0])->toHaveKeys(['name', 'type', 'data']);

    expect($response['comparisonSeasonalSalesChartData']['data'][1])->toHaveKeys(['name', 'type', 'data']);

    expect($response['comparisonSeasonalSalesChartData']['legendData'])->toBe(['test', 'test2']);

    expect($response['comparisonSeasonalSalesChartData']['labels'])->toBe([
        'D-1', 'D-2', 'D-3', 'D-4', 'D-5', 'D-6', 'D-7', 'D-8', 'D-9', 'D-10',
    ]);
});

test('calls the getSeasonalSalesComparisonChartData method and returns proper response', function (): void {
    $date = Carbon::now()->format('Y-m-d');
    $saleSeason1 = SaleSeason::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'name' => 'test',
        'start_date' => $date,
        'end_date' => $date,
    ]);

    $saleSeason2 = SaleSeason::factory()->make([
        'id' => 2,
        'company_id' => $this->company->id,
        'name' => 'test2',
        'start_date' => $date,
        'end_date' => $date,
    ]);

    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $data['sale_season_id'] = $saleSeason1->id;
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $data['comparison_x_sale_season_id'] = 1;
    $data['comparison_y_sale_season_id'] = 2;
    $apiData = new CompanyOwnerSeasonalSalesComparisonChartApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(SaleSeasonQueries::class, function ($mock) use ($saleSeason1, $saleSeason2): void {
        $mock->shouldReceive('getById')
            ->twice()
            ->andReturn($saleSeason1, $saleSeason2);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getCachedSeasonalTopFiveColorsSalesForChartComparison')
            ->once()
            ->andReturn([]);
        $mock->shouldReceive('getCachedSeasonalTopFiveCategorySalesForChartComparison')
            ->once()
            ->andReturn([]);
        $mock->shouldReceive('getCachedSeasonalTopFiveStyleSalesForChartComparison')
            ->once()
            ->andReturn([]);
        $mock->shouldReceive('getCachedSeasonalTopFiveDepartmentSalesForChartComparison')
            ->once()
            ->andReturn([]);
        $mock->shouldReceive('getCachedSeasonalTopFiveColorGroupSalesForChartComparison')
            ->once()
            ->andReturn([]);
        $mock->shouldReceive('getCachedSeasonalTopFiveSizeSalesForChartComparison')
            ->once()
            ->andReturn([]);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getSeasonalSalesComparisonChartData($request, $apiData);

    expect($response)->toHaveKeys([
        'comparison_color_top_five_chart',
        'comparison_category_top_five_chart',
        'comparison_style_top_five_chart',
        'comparison_department_top_five_chart',
        'comparison_color_group_top_five_chart',
        'comparison_size_top_five_chart',
    ]);
});

test('calls the revenueView method and returns record', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $data['start_date'] = null;
    $data['end_date'] = null;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerRevenueViewApiData(...$data);

    $brand = Brand::factory()->make([
        'id' => 1,
        'name' => 'ABCD',
    ]);

    $data = (object) [
        'id' => 1,
        'name' => 'Location A',
        'code' => '123',
        'sales_count' => 500,
        'total_sales' => 500,
        'total_units_sold' => 100,
    ];

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($data): void {
        $mock->shouldReceive('getCachedStoresSalesForChart')
            ->once()
            ->andReturn(collect([$data]));
    });

    $this->mock(BrandQueries::class, function ($mock) use ($data, $brand): void {
        $mock->shouldReceive('getCachedBrandsSalesForChart')
            ->once()
            ->andReturn(collect([$data]));
        $mock->shouldReceive('getBrands')
            ->once()
            ->andReturn(collect([$brand]));
    });

    $this->mock(CategoryQueries::class, function ($mock) use ($data): void {
        $mock->shouldReceive('getCachedCategoriesSalesForChart')
            ->once()
            ->andReturn(collect([$data]));
    });

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
        ->once()
        ->andReturn(new Currency([
            'symbol' => 'RS',
        ]));
    });

    $this->mock(StyleQueries::class, function ($mock) use ($data): void {
        $mock->shouldReceive('getCachedStylesSalesForChart')
            ->once()
            ->andReturn(collect([$data]));
    });

    $this->mock(DepartmentQueries::class, function ($mock) use ($data): void {
        $mock->shouldReceive('getCachedDepartmentSaleForChart')
            ->once()
            ->andReturn(collect([$data]));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->revenueView($request, $apiData);

    expect($response)->toHaveKeys([
        'totalSalesByLocation',
        'totalSalesByBrand',
        'totalSalesByCategory',
        'totalSalesByStyle',
        'totalSalesByDepartment',
        'totalSales',
        'totalUnitsSold',
        'salesData',
        'salesTotalData',
        'start_date',
        'end_date',
        'brands',
        'brandId',
        'lastUpdate',
    ]);
});

test('calls the storeRevenueView method and returns record', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $data['date'] = null;
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerStoreRevenueViewApiData(...$data);

    Brand::factory()->make([
        'id' => 1,
        'name' => 'ABCD',
    ]);

    $data = (object) [
        'id' => 1,
        'name' => 'Location A',
        'code' => '123',
        'sales_count' => 500,
        'total_sales' => 500,
        'total_units_sold' => 100,
    ];

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($data): void {
        $mock->shouldReceive('getStoreWithBasicColumns')
            ->once()
            ->with($this->company->id)
            ->andReturn(collect([]));

        $mock->shouldReceive('getCachedStoresSalesForChart')
            ->once()
            ->andReturn(collect([$data]));
    });

    $this->mock(BrandQueries::class, function ($mock): void {
        $mock->shouldReceive('getBrands')
        ->once()
        ->with($this->company->id)
        ->andReturn(collect($this->brand));
    });

    $this->mock(SaleQueries::class, function ($mock): void {
        $mock->shouldReceive('totalCreditSalePendingAmount')
        ->once()
        ->andReturn(10.5);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getCachedBrandsSalesForChart')
            ->once()
            ->andReturn([collect([]), collect([]), collect([])]);
        $mock->shouldReceive('getCachedColorsSalesForChart')
            ->once()
            ->andReturn([collect([]), collect([]), collect([])]);
        $mock->shouldReceive('getCachedCategoriesSalesForChart')
            ->once()
            ->andReturn([collect([]), collect([]), collect([])]);
        $mock->shouldReceive('getCachedDepartmentSaleForChart')
            ->once()
            ->andReturn([collect([]), collect([]), collect([])]);
        $mock->shouldReceive('getCachedColorGroupSalesForChart')
            ->once()
            ->andReturn([collect([]), collect([]), collect([])]);
        $mock->shouldReceive('getCachedSizeSalesForChart')
            ->once()
            ->andReturn([collect([]), collect([]), collect([])]);
        $mock->shouldReceive('getCachedStyleSalesForChart')
            ->once()
            ->andReturn([collect([]), collect([]), collect([])]);
        $mock->shouldReceive('getHourlyBasedData')
            ->once()
            ->andReturn([1, 1, 1, 1, 1]);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->storeRevenueView($request, $apiData);

    expect($response)->toHaveKeys([
        'brands',
        'locations',
        'locationId',
        'brandId',
        'totalCreditSalePendingAmount',
        'totalSalesByColor',
        'totalSalesByBrand',
        'totalSalesByCategory',
        'totalSalesByDepartment',
        'totalSalesByColorGroup',
        'totalSalesBySize',
        'totalSalesByStyle',
        'hourlySales',
        'accumulatedHourlySales',
        'totalSales',
        'totalUnitsSold',
        'brandsData',
        'brandFooterData',
        'colorsData',
        'colorFooterData',
        'categoriesData',
        'categoryFooterData',
        'departmentsData',
        'departmentFooterData',
        'colorGroupsData',
        'colorGroupFooterData',
        'sizesData',
        'stylesData',
        'sizeFooterData',
        'styleFooterData',
        'date',
        'lastUpdate',
        'storeRevenueDashboardTableFilterTypes',
    ]);
});

test('calls the businessView method and returns record', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(BrandQueries::class, function ($mock): void {
        $mock->shouldReceive('getBrands')
        ->once()
        ->with($this->company->id)
        ->andReturn(collect($this->brand));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->businessView($request);

    expect($response['brands'])
        ->toBeInstanceOf(Collection::class);
});

test('calls the getBusinessViewData method and returns proper response', function (): void {
    $date = Carbon::now()->format('Y-m-d');
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $data['month'] = null;
    $data['year'] = null;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerBusinessViewApiData(...$data);

    $storeWiseDailyTotals = StoreWiseDailyTotal::factory()->make([
        'date' => $date,
        'company_id' => 1,
        'location_id' => 1,
        'brand_id' => 1,
        'counter_update_id' => 1,
        'total_sales_count' => 1,
        'total_units_sold' => 1,
        'total_sales_amount' => 10,
    ]);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(StoreWiseDailyTotalQueries::class, function ($mock) use ($storeWiseDailyTotals): void {
        $mock->shouldReceive('getTotalSalesAmountByDate')
            ->once()
            ->andReturn($storeWiseDailyTotals);
    });

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getYearlyTarget')
            ->once()
            ->andReturn(10.5);
    });
    $data = [];

    $data['totalAmount'] = 10;
    $data['todayTotalSalePercentage'] = 10;

    $this->mock(DashboardService::class, function ($mock) use ($storeWiseDailyTotals, $data): void {
        $mock->shouldReceive('getLastYearSaleData')
            ->times(3)
            ->andReturn($storeWiseDailyTotals);
        $mock->shouldReceive('getYearlySalesDetails')
            ->once()
            ->andReturn([]);
        $mock->shouldReceive('getTodaySalesDetails')
            ->once()
            ->andReturn($data);
        $mock->shouldReceive('getBrandWiseData')
            ->once()
            ->andReturn([]);
        $mock->shouldReceive('getStyleWiseData')
            ->once()
            ->andReturn([]);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getBusinessViewData($request, $apiData);

    expect($response)->toHaveKeys([
        'salesCount',
        'yearlySalesData',
        'brandWiseData',
        'styleWiseData',
        'lastUpdate',
    ]);
});

test('calls the getStyleChartData method and returns proper response', function (): void {
    $date = Carbon::now()->format('Y-m-d');
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $data['month'] = null;
    $data['year'] = null;
    $data['quarter'] = null;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerStyleChartApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getStyleWiseData')
            ->once()
            ->andReturn([]);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getStyleChartData($request, $apiData);

    expect($response)->toHaveKeys(['styleWiseData']);
});

test('calls the stockOverview method and returns record', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getStoreWithBasicColumns')
        ->once()
        ->with($this->company->id)
        ->andReturn(collect($this->location));
    });

    $this->mock(BrandQueries::class, function ($mock): void {
        $mock->shouldReceive('getBrands')
        ->once()
        ->with($this->company->id)
        ->andReturn(collect($this->brand));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->stockOverview($request);

    expect($response['locations'])
        ->toBeInstanceOf(Collection::class);

    expect($response['brands'])
        ->toBeInstanceOf(Collection::class);
});

test('calls the getNoStockOverview method and returns record', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $request->shouldReceive('validate')->andReturn([
        'location_id' => null,
        'refresh' => false,
    ]);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(InventoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getNoStockItems')
        ->once()
        ->andReturn(10);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getNoStockOverview($request);

    expect($response)->toHaveKey('noStockItemCount', 10);
});

test('calls the getLowStockOverview method and returns record', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $request->shouldReceive('validate')->andReturn([
        'location_id' => null,
        'refresh' => false,
    ]);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(InventoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyLowStockItems')
        ->once()
        ->andReturn(10);

        $mock->shouldReceive('getLocationLowStockItems')
        ->once()
        ->andReturn(10);

        $mock->shouldReceive('getProductLowStockItems')
        ->once()
        ->andReturn(10);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getLowStockOverview($request);

    expect($response)->toHaveKey('lowStockItemCount', 30);
});

test('calls the getTransferOrder method and returns record', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getTransferOrder')
        ->once()
        ->andReturn([]);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getTransferOrder($request, $this->location->id);

    expect($response)->toHaveKey('transferOrders');
});

test('calls the getPurchaseRequest method and returns record', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getPurchaseRequest')
        ->once()
        ->andReturn([]);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getPurchaseRequest($request, $this->location->id);

    expect($response)->toHaveKey('purchaseRequests');
});

test('calls the getTransferRequest method and returns record', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getTransferRequest')
        ->once()
        ->andReturn([]);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getTransferRequest($request, $this->location->id);

    expect($response)->toHaveKey('transferRequests');
});

test('calls the getSalesOrder method and returns record', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getSalesOrder')
        ->once()
        ->andReturn([[], []]);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getSalesOrder($request, $this->location->id);

    expect($response)->toHaveKeys(['salesOrders', 'salesDeliveryOrders']);
});

test('calls the getPurchaseOrder method and returns record', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getPurchaseOrder')
        ->once()
        ->andReturn([[], []]);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getPurchaseOrder($request, $this->location->id);

    expect($response)->toHaveKeys(['purchaseOrders', 'purchaseDeliveryOrders']);
});

test('calls the getRequestOrder method and returns record', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getRequestOrder')
        ->once()
        ->andReturn([]);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getRequestOrder($request, $this->location->id);

    expect($response)->toHaveKey('requestOrders');
});

test('calls the getThisMonthTopSellingProducts method and returns proper response', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerThisMonthTopSellingProductsApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedTopSellingProduct')
            ->once()
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getThisMonthTopSellingProducts($request, $apiData);

    expect($response)->toHaveKeys(['thisMonthTopSellingProducts', 'lastUpdate']);
});

test('calls the getThisYearTopSellingProducts method and returns proper response', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerThisYearTopSellingProductsApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedTopSellingProduct')
            ->once()
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getThisYearTopSellingProducts($request, $apiData);

    expect($response)->toHaveKey('thisYearTopSellingProducts');
});

test('calls the getThisMonthTopSellingColors method and returns proper response', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerThisMonthTopSellingColorsApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(ColorQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedTopSellingColor')
            ->once()
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getThisMonthTopSellingColors($request, $apiData);

    expect($response)->toHaveKey('thisMonthTopSellingColors');
});

test('calls the getThisYearTopSellingColors method and returns proper response', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerThisYearTopSellingColorsApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(ColorQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedTopSellingColor')
            ->once()
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getThisYearTopSellingColors($request, $apiData);

    expect($response)->toHaveKey('thisYearTopSellingColors');
});

test('calls the getTopRankingProducts method and returns record', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->with($this->employee->id)
            ->andReturn($this->company->id);
    });

    $this->mock(SellThroughAggregateQueries::class, function ($mock): void {
        $mock->shouldReceive('sellThroughAggregateByProductArticleNumberForDashboard')
            ->once()
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getTopRankingProducts($request, $this->location->id);

    expect($response)->toHaveKey('topRankingProducts');
});

test('calls the getThisMonthWorstSellingProducts method and returns proper response', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerThisMonthWorstSellingProductsApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedWorstSellingProduct')
            ->once()
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getThisMonthWorstSellingProducts($request, $apiData);

    expect($response)->toHaveKeys(['thisMonthWorstSellingProducts', 'lastUpdate']);
});

test('calls the getThisYearWorstSellingProducts method and returns proper response', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $data['location_id'] = null;
    $data['brand_id'] = null;
    $data['refresh'] = null;
    $apiData = new CompanyOwnerThisYearWorstSellingProductsApiData(...$data);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedWorstSellingProduct')
            ->once()
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getThisYearWorstSellingProducts($request, $apiData);

    expect($response)->toHaveKey('thisYearWorstSellingProducts');
});

test('calls the getNegativeStockOverview method and returns record', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->user);
    $request->shouldReceive('validate')->andReturn([
        'location_id' => null,
        'refresh' => false,
    ]);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
        ->once()
        ->with($this->employee->id)
        ->andReturn($this->company->id);
    });

    $this->mock(InventoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getNegativeStockItems')
        ->once()
        ->andReturn(10);
    });

    $dashboardController = new DashboardController($request);
    $response = $dashboardController->getNegativeStockOverview($request);

    expect($response)->toHaveKey('negativeStockItemCount', 10);
});
