<?php

declare(strict_types=1);

use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\ColorGroup\ColorGroupQueries;
use App\Domains\Common\Services\DashboardService;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Location\Enums\LocationTypes as EnumsLocationTypes;
use App\Domains\PastYearData\PastYearDataQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Domains\Size\SizeQueries;
use App\Domains\StoreWiseDailyTotal\StoreWiseDailyTotalQueries;
use App\Domains\Style\StyleQueries;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Promoter;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StoreWiseDailyTotal;
use Carbon\Carbon;

test('Retrieve Today Sales Details', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');
    setCompanyIdInSession();
    $date = Carbon::now()->format('Y-m-d');

    $storeWiseDailyTotal = StoreWiseDailyTotal::factory()->make([
        'id' => 1,
        'date' => $date,
        'company_id' => 1,
        'location_id' => 1,
        'brand_id' => 1,
        'counter_update_id' => 1,
        'total_sales_count' => 1,
        'total_units_sold' => 1,
        'total_sales_amount' => 1,
    ]);

    $this->mock(StoreWiseDailyTotalQueries::class, function ($mock) use ($storeWiseDailyTotal): void {
        $mock->shouldReceive('getTotalSalesAmountByDate')
            ->once()
            ->andReturn($storeWiseDailyTotal);
    });

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $saleItem = SaleItem::factory()->make([
        'sale_id' => $sale->id,
        'product_id' => 1,
        'derivative_id' => 1,
    ]);

    $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('getCachedTodaySalesForDashboard')
            ->once()
            ->andReturn($saleItem);
    });

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'original_sale_id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
    ]);

    $saleReturnItem = SaleReturnItem::factory()->make([
        'id' => 1,
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => 1,
        'product_id' => 1,
        'sale_return_reason_id' => 1,
    ]);

    $this->mock(SaleReturnItemQueries::class, function ($mock) use ($saleReturnItem): void {
        $mock->shouldReceive('getCachedTodaySaleReturnsForDashboard')
            ->once()
            ->andReturn($saleReturnItem);
    });

    $this->mock(PastYearDataQueries::class, function ($mock): void {
        $mock->shouldReceive('getTotalSalesAmountByDate')
            ->once()
            ->andReturn(null);
    });

    $dashboardService = new DashboardService();
    $dashboardService->getTodaySalesDetails(1, 1, 1, $date, false);
    Carbon::setTestNow();
});

test('Retrieve Today Sales Details when date not today', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');
    setCompanyIdInSession();
    $date = Carbon::now()->subDay()->format('Y-m-d');

    $storeWiseDailyTotal = StoreWiseDailyTotal::factory()->make([
        'id' => 1,
        'date' => $date,
        'company_id' => 1,
        'location_id' => 1,
        'brand_id' => 1,
        'counter_update_id' => 1,
        'total_sales_count' => 1,
        'total_units_sold' => 1,
        'total_sales_amount' => 1,
    ]);

    $this->mock(StoreWiseDailyTotalQueries::class, function ($mock) use ($storeWiseDailyTotal): void {
        $mock->shouldReceive('getTotalSalesAmountByDate')
            ->twice()
            ->andReturn($storeWiseDailyTotal);
    });

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getCachedTodaySalesForDashboard')
            ->times(0)
            ->andReturn($sale);
    });

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'original_sale_id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
    ]);

    $this->mock(SaleReturnQueries::class, function ($mock) use ($saleReturn): void {
        $mock->shouldReceive('getCachedTodaySalesForDashboard')
            ->times(0)
            ->andReturn($saleReturn);
    });

    $this->mock(PastYearDataQueries::class, function ($mock): void {
        $mock->shouldReceive('getTotalSalesAmountByDate')
            ->once()
            ->andReturn(null);
    });

    $dashboardService = new DashboardService();
    $dashboardService->getTodaySalesDetails(1, 1, 1, $date, false);
    Carbon::setTestNow();
});

test('Retrieve This Months Sales Details', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');
    setCompanyIdInSession();
    $date = Carbon::now()->format('Y-m-d');

    $storeWiseDailyTotal = StoreWiseDailyTotal::factory()->make([
        'id' => 1,
        'date' => $date,
        'company_id' => 1,
        'location_id' => 1,
        'brand_id' => 1,
        'counter_update_id' => 1,
        'total_sales_count' => 10,
        'total_units_sold' => 20,
        'total_sales_amount' => 1,
    ]);

    $this->mock(StoreWiseDailyTotalQueries::class, function ($mock) use ($storeWiseDailyTotal): void {
        $mock->shouldReceive('getTotalSalesAmountByDate')
            ->once()
            ->andReturn($storeWiseDailyTotal);
        $mock->shouldReceive('getTotalSalesAmountByDate')
            ->once()
            ->andReturn($storeWiseDailyTotal);
    });

    $this->mock(PastYearDataQueries::class, function ($mock): void {
        $mock->shouldReceive('getTotalSalesAmountByDate')
            ->once()
            ->andReturn(null);
    });

    $dashboardService = new DashboardService();
    $dashboardService->getThisMonthSalesDetails(1, 1, 1, $date, false);
    Carbon::setTestNow();
});

test('Retrieve This Year Sales Details', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');
    setCompanyIdInSession();
    $date = Carbon::now()->format('Y-m-d');

    $storeWiseDailyTotal = StoreWiseDailyTotal::factory()->make([
        'id' => 1,
        'date' => $date,
        'company_id' => 1,
        'location_id' => 1,
        'brand_id' => 1,
        'counter_update_id' => 1,
        'total_amount' => 100,
        'total_sales_count' => 10,
        'total_units_sold' => 20,
    ]);

    $this->mock(StoreWiseDailyTotalQueries::class, function ($mock) use ($storeWiseDailyTotal): void {
        $mock->shouldReceive('getTotalSalesAmountByDate')
            ->once()
            ->andReturn($storeWiseDailyTotal);
    });

    $this->mock(PastYearDataQueries::class, function ($mock): void {
        $mock->shouldReceive('getTotalSalesAmountByDate')
            ->twice()
            ->andReturn(null);
    });

    $dashboardService = new DashboardService();
    $dashboardService->getThisYearSalesDetails(1, 1, 1, $date, false);
    Carbon::setTestNow();
});

test('Retrieve month wise Sales Details1', function (): void {
    setCompanyIdInSession();
    $date = Carbon::now()->format('Y-m-d');

    $storeWiseDailyTotal = StoreWiseDailyTotal::factory()->make([
        'id' => 1,
        'date' => $date,
        'company_id' => 1,
        'location_id' => 1,
        'brand_id' => 1,
        'counter_update_id' => 1,
        'total_amount' => 100,
        'total_sales_count' => 10,
        'total_units_sold' => 20,
    ]);

    $this->mock(StoreWiseDailyTotalQueries::class, function ($mock) use ($storeWiseDailyTotal): void {
        $mock->shouldReceive('getMonthWiseTotalSalesAmountByDate')
            ->once()
            ->andReturn(collect([$storeWiseDailyTotal]));
    });

    $dashboardService = new DashboardService();
    $dashboardService->getMonthWiseSalesDetails(1, 1, 1, $date, false);
});

test('getOnlyFourSales function returns expected result', function (): void {
    $labels = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];
    $totalSales = [100, 200, 300, 400, 500, 600, 650, 700, 750, 800, 850, 900];
    $dashboardService = new DashboardService();
    $result = $dashboardService->getOnlyFourSales($labels, $totalSales);
    $newLabels = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'Other'];
    $newTotalSales = [100.0, 200.0, 300.0, 400.0, 500.0, 600.0, 650.0, 700.0, 750.0, 800.0, 1750.0];
    expect($result['labels'])->toBe($newLabels);
    expect($result['total_sales'])->toBe($newTotalSales);
});

test('addTodaySalesDetails method returns the response as expected', function (): void {
    $date = Carbon::now()->format('Y-m-d');
    $storeWiseDailyTotal = StoreWiseDailyTotal::factory()->make([
        'id' => 1,
        'date' => $date,
        'company_id' => 1,
        'location_id' => 1,
        'brand_id' => 1,
        'counter_update_id' => 1,
        'total_amount' => 100,
        'total_sales_count' => 10,
        'total_units_sold' => 20,
    ]);
    $dashboardService = new DashboardService();
    $response = $dashboardService->addTodaySalesDetails($storeWiseDailyTotal);
    expect($response)
        ->toHaveKey('totalAmount', 100)
        ->toHaveKey('totalUnitsSold', 20)
        ->toHaveKey('totalSalesCount', 10);
});

test('getRevenueChartData method returns the response as expected', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');
    $storeWiseDailyTotal = collect([
        [
            'month' => 1,
            'month_string' => 'A',
            'total_amount' => 100,
        ],
        [
            'month' => 2,
            'month_string' => 'B',
            'total_amount' => 50,
        ],
        [
            'month' => 3,
            'month_string' => 'C',
            'total_amount' => 200,
        ],
    ]);
    $dashboardService = new DashboardService();
    $response = $dashboardService->getRevenueChartData($storeWiseDailyTotal);
    expect($response['data'])
        ->toHaveKey(0, 100)
        ->toHaveKey(1, 50)
        ->toHaveKey(2, 200);
    expect($response['labels'])
        ->toHaveKey(0, 'A')
        ->toHaveKey(1, 'B')
        ->toHaveKey(2, 'C');
    Carbon::setTestNow();
});

test('getRevenueChartDataWithLastYear method returns the response as expected', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');
    $storeWiseDailyTotal = collect([
        [
            'month' => 1,
            'month_string' => 'January',
            'total_amount' => 100,
        ],
        [
            'month' => 2,
            'month_string' => 'February',
            'total_amount' => 50,
        ],
        [
            'month' => 3,
            'month_string' => 'March',
            'total_amount' => 200,
        ],
    ]);

    $lastYearStoreWiseDailyTotal = collect([
        [
            'month' => 1,
            'month_string' => 'January',
            'total_amount' => 100,
        ],
        [
            'month' => 2,
            'month_string' => 'February',
            'total_amount' => 50,
        ],
        [
            'month' => 3,
            'month_string' => 'March',
            'total_amount' => 200,
        ],
    ]);
    $dashboardService = new DashboardService();
    $response = $dashboardService->getRevenueChartDataWithLastYear($storeWiseDailyTotal, $lastYearStoreWiseDailyTotal);

    expect($response['current_year_data'])
        ->toHaveKey(0, 100)
        ->toHaveKey(1, 50)
        ->toHaveKey(2, 200);
    expect($response['last_year_data'])
        ->toHaveKey(0, 100)
        ->toHaveKey(1, 50)
        ->toHaveKey(2, 200);
    expect($response['labels'])
        ->toHaveKey(0, 'January')
        ->toHaveKey(1, 'February')
        ->toHaveKey(2, 'March');
    Carbon::setTestNow();
});

test('getATVChartData method returns the response as expected', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');
    $storeWiseDailyTotal = collect([
        [
            'month' => 1,
            'month_string' => 'A',
            'total_amount' => 100,
            'total_sales_count' => 10,
        ],
        [
            'month' => 2,
            'month_string' => 'B',
            'total_amount' => 100,
            'total_sales_count' => 10,
        ],
        [
            'month' => 3,
            'month_string' => 'C',
            'total_amount' => 200,
            'total_sales_count' => 10,
        ],
    ]);
    $dashboardService = new DashboardService();
    $response = $dashboardService->getATVChartData($storeWiseDailyTotal);
    expect($response['data'])
        ->toHaveKey(0, 10)
        ->toHaveKey(1, 10)
        ->toHaveKey(2, 20);
    expect($response['labels'])
        ->toHaveKey(0, 'A')
        ->toHaveKey(1, 'B')
        ->toHaveKey(2, 'C');
    Carbon::setTestNow();
});

test('getUPTChartData method returns the response as expected', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');
    $storeWiseDailyTotal = collect([
        [
            'month' => 1,
            'month_string' => 'A',
            'total_units_sold' => 100,
            'total_sales_count' => 10,
        ],
        [
            'month' => 2,
            'month_string' => 'B',
            'total_units_sold' => 100,
            'total_sales_count' => 10,
        ],
        [
            'month' => 3,
            'month_string' => 'C',
            'total_units_sold' => 200,
            'total_sales_count' => 10,
        ],
    ]);
    $dashboardService = new DashboardService();
    $response = $dashboardService->getUPTChartData($storeWiseDailyTotal);
    expect($response['data'])
        ->toHaveKey(0, 10)
        ->toHaveKey(1, 10)
        ->toHaveKey(2, 20);
    expect($response['labels'])
        ->toHaveKey(0, 'A')
        ->toHaveKey(1, 'B')
        ->toHaveKey(2, 'C');
    Carbon::setTestNow();
});

test('getHourlyBasedData method returns the response as expected', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');

    $sale = new Sale([
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $this->mock(SaleQueries::class, function ($mock) use ($sale): void {
        $mock->shouldReceive('getHourlyBasedData')
            ->times(2)
            ->andReturn(collect([$sale]));
    });

    $this->mock(SaleReturnQueries::class, function ($mock): void {
        $mock->shouldReceive('getHourlyBasedData')
            ->times(2)
            ->andReturn(collect([]));
    });

    $dashboardService = new DashboardService();
    $response = $dashboardService->getHourlyBasedData(1, 1, 1, now()->format('Y-m-d'), false);

    expect($response[0]->toArray())
        ->toHaveKey(0, 0.0);

    Carbon::setTestNow();
});

test('getTopPromoters method returns the response as expected', function (): void {
    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'units_sold' => 10,
        'amount_sold' => 20,
    ]);

    $promoter->employee = Employee::factory()->make([
        'company_id' => 1,
        'designation_id' => 1,
        'first_name' => 'Test',
        'last_name' => 'Test',
        'staff_id' => '123',
    ]);

    $this->mock(PromoterQueries::class, function ($mock) use ($promoter): void {
        $mock->shouldReceive('getSalesByPromotersForDashboard')
            ->times(1)
            ->andReturn(collect([$promoter]));
    });

    $dashboardService = new DashboardService();
    $response = $dashboardService->getTopPromoters(1, 1, 1, now()->format('Y-m-d'), false);
    expect($response->first())
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'Test Test(123)')
        ->toHaveKey('units_sold', 10)
        ->toHaveKey('amount_sold', 20);
});

test('getYearlyTopPromoters method returns the response as expected', function (): void {
    $promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => 1,
        'units_sold' => 10,
        'amount_sold' => 20,
    ]);

    $promoter->employee = Employee::factory()->make([
        'company_id' => 1,
        'designation_id' => 1,
        'first_name' => 'Test',
        'last_name' => 'Test',
        'staff_id' => '123',
    ]);

    $this->mock(PromoterQueries::class, function ($mock) use ($promoter): void {
        $mock->shouldReceive('getSalesByPromotersForDashboard')
            ->times(1)
            ->andReturn(collect([$promoter]));
    });

    $dashboardService = new DashboardService();
    $response = $dashboardService->getYearlyTopPromoters(1, 1, 1, now()->format('Y-m-d'), false);
    expect($response->first())
        ->toHaveKey('id', 1)
        ->toHaveKey('name', 'Test Test(123)')
        ->toHaveKey('units_sold', 10)
        ->toHaveKey('amount_sold', 20);
});

test('getPurchaseOrderCount method returns the response as expected', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'ABC',
        'type_id' => EnumsLocationTypes::STORE->value,
    ]);

    $purchaseOrder = PurchaseOrder::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'location_id' => $location->id,
        'created_by_company_id' => 1,
        'status' => Statuses::OPENED->value,
        'external_purchase_order_id' => null,
        'parent_purchase_order_id' => null,
        'external_company_id' => null,
        'external_location_id' => null,
        'order_type' => OrderTypes::SALES_ORDER->value,
    ]);

    $purchaseOrder->count = 1;

    $data = [
        'location_id' => $location->id,
        'order_type' => OrderTypes::SALES_ORDER->value,
    ];

    $this->mock(PurchaseOrderQueries::class, function ($mock) use ($data, $purchaseOrder): void {
        $mock->shouldReceive('getDashboardStatusCount')
            ->once()
            ->with($data, 1)
            ->andReturn(collect([$purchaseOrder]));
    });

    $dashboardService = new DashboardService();
    $response = $dashboardService->getPurchaseOrderCount($data, 1);
    expect($response)->not->toBeEmpty();
});

test('getPurchaseOrderFulfillmentCount method returns the response as expected', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'ABC',
        'type_id' => EnumsLocationTypes::STORE->value,
    ]);

    $purchaseOrder = PurchaseOrder::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'location_id' => $location->id,
        'created_by_company_id' => 1,
        'status' => Statuses::OPENED->value,
        'external_purchase_order_id' => null,
        'parent_purchase_order_id' => null,
        'external_company_id' => null,
        'external_location_id' => null,
        'order_type' => OrderTypes::SALES_ORDER->value,
    ]);

    $fulfillments = PurchaseOrderFulfillment::factory()->make([
        'id' => 1,
        'purchase_order_id' => $purchaseOrder->id,
        'purchase_order_invoice_id' => 1,
        'delivery_order_number' => '1234567',
    ]);

    $fulfillments->count = 1;

    $data = [
        'location_id' => $location->id,
        'order_type' => OrderTypes::SALES_ORDER->value,
    ];

    $this->mock(PurchaseOrderFulfillmentQueries::class, function ($mock) use ($data, $fulfillments): void {
        $mock->shouldReceive('getDashboardStatusCount')
            ->once()
            ->with($data, 1)
            ->andReturn(collect([$fulfillments]));
    });

    $dashboardService = new DashboardService();
    $response = $dashboardService->getPurchaseOrderFulfillmentCount($data, 1);
    expect($response)->not->toBeEmpty();
});

test(
    'getCachedSeasonalTopFiveColorsSalesForChart method is called colorQueries to get desired data',
    function (): void {
        $colors = collect([
            [
                'color_id' => 1,
                'color_name' => 'red',
                'total_sales' => 1,
                'total_units_sold' => 1,
            ],
        ]);

        $this->mock(ColorQueries::class, function ($mock) use ($colors): void {
            $mock->shouldReceive('getCachedSeasonalTopFiveColorsSalesForChart')
                ->once()
                ->andReturn($colors);
        });

        $dashboardService = new DashboardService();
        $response = $dashboardService->getCachedSeasonalTopFiveColorsSalesForChart([], 1, [], false);

        expect($response)->toHaveKeys(['data', 'labels', 'legendData']);
    }
);

test(
    'getCachedSeasonalTopFiveColorsSalesForChart method is called colorQueries to get desired data for comparison',
    function (): void {
        $colors = collect([
            [
                'color_id' => 1,
                'color_name' => 'red',
                'total_sales' => 1,
                'total_units_sold' => 1,
            ],
        ]);

        $filterData = [
            'x_start_date' => null,
            'x_end_date' => null,
            'y_start_date' => null,
            'y_end_date' => null,
            'location_id' => null,
            'brand_id' => null,
            'x_sale_season_name' => null,
            'y_sale_season_name' => null,
        ];

        $this->mock(ColorQueries::class, function ($mock) use ($colors): void {
            $mock->shouldReceive('getCachedSeasonalTopFiveColorsSalesForChart')
                ->twice()
                ->andReturn($colors);
        });

        $dashboardService = new DashboardService();
        $response = $dashboardService->getCachedSeasonalTopFiveColorsSalesForChartComparison($filterData, 1, false);

        expect($response)->toHaveKeys(['data', 'labels', 'legendData']);
    }
);

test('getCachedWeekDistributionColorForChart method is called colorQueries to get desired data', function (): void {
    $colors = collect([
        [
            'color_id' => 1,
            'color_name' => 'red',
            'total_sales' => 1,
            'total_units_sold' => 1,
        ],
    ]);

    $this->mock(ColorQueries::class, function ($mock) use ($colors): void {
        $mock->shouldReceive('getCachedWeekDistributionColorForChart')
            ->once()
            ->andReturn($colors);
    });

    $dashboardService = new DashboardService();
    $response = $dashboardService->getCachedWeekDistributionColorForChart([], 1, false);

    expect($response)->toHaveKeys(['data', 'labels']);
});

test(
    'getCachedSeasonalTopFiveCategorySalesForChart method is called categoryQueries to get desired data',
    function (): void {
        $categories = collect([
            [
                'category_id' => 1,
                'category_name' => 'red',
                'total_sales' => 1,
                'total_units_sold' => 1,
            ],
        ]);

        $this->mock(CategoryQueries::class, function ($mock) use ($categories): void {
            $mock->shouldReceive('getCachedSeasonalTopFiveCategoriesSalesForChart')
                ->once()
                ->andReturn($categories);
        });

        $dashboardService = new DashboardService();
        $response = $dashboardService->getCachedSeasonalTopFiveCategorySalesForChart([], 1, false);

        expect($response)->toHaveKeys(['data', 'labels']);
    }
);

test(
    'getCachedSeasonalTopFiveCategorySalesForChartComparison method is called categoriesQueries to get desired data for comparison',
    function (): void {
        $category = collect([
            [
                'category_id' => 1,
                'category_name' => 'red',
                'total_sales' => 1,
                'total_units_sold' => 1,
            ],
        ]);

        $filterData = [
            'x_start_date' => null,
            'x_end_date' => null,
            'y_start_date' => null,
            'y_end_date' => null,
            'location_id' => null,
            'brand_id' => null,
            'x_sale_season_name' => null,
            'y_sale_season_name' => null,
        ];

        $this->mock(CategoryQueries::class, function ($mock) use ($category): void {
            $mock->shouldReceive('getCachedSeasonalTopFiveCategoriesSalesForChart')
                ->twice()
                ->andReturn($category);
        });

        $dashboardService = new DashboardService();
        $response = $dashboardService->getCachedSeasonalTopFiveCategorySalesForChartComparison($filterData, 1, false);

        expect($response)->toHaveKeys(['data', 'labels', 'legendData']);
    }
);

test('getCachedSeasonalTopFiveStyleSalesForChart method is called styleQueries to get desired data', function (): void {
    $data = collect([
        [
            'style_id' => 1,
            'style_name' => 'red',
            'total_sales' => 1,
            'total_units_sold' => 1,
        ],
    ]);

    $this->mock(StyleQueries::class, function ($mock) use ($data): void {
        $mock->shouldReceive('getCachedSeasonalTopFiveStyleSalesForChart')
            ->once()
            ->andReturn($data);
    });

    $dashboardService = new DashboardService();
    $response = $dashboardService->getCachedSeasonalTopFiveStyleSalesForChart([], 1, false);

    expect($response)->toHaveKeys(['data', 'labels']);
});

test(
    'getCachedSeasonalTopFiveStyleSalesForChartComparison method is called StyleQueries to get desired data for comparison',
    function (): void {
        $data = collect([
            [
                'style_id' => 1,
                'style_name' => 'red',
                'total_sales' => 1,
                'total_units_sold' => 1,
            ],
        ]);

        $filterData = [
            'x_start_date' => null,
            'x_end_date' => null,
            'y_start_date' => null,
            'y_end_date' => null,
            'location_id' => null,
            'brand_id' => null,
            'x_sale_season_name' => null,
            'y_sale_season_name' => null,
        ];

        $this->mock(StyleQueries::class, function ($mock) use ($data): void {
            $mock->shouldReceive('getCachedSeasonalTopFiveStyleSalesForChart')
                ->twice()
                ->andReturn($data);
        });

        $dashboardService = new DashboardService();
        $response = $dashboardService->getCachedSeasonalTopFiveStyleSalesForChartComparison($filterData, 1, false);

        expect($response)->toHaveKeys(['data', 'labels', 'legendData']);
    }
);

test(
    'getCachedSeasonalTopFiveDepartmentSalesForChart method is called departmentQueries to get desired data',
    function (): void {
        $data = collect([
            [
                'department_id' => 1,
                'department_name' => 'red',
                'total_sales' => 1,
                'total_units_sold' => 1,
            ],
        ]);

        $this->mock(DepartmentQueries::class, function ($mock) use ($data): void {
            $mock->shouldReceive('getCachedSeasonalTopFiveDepartmentSalesForChart')
                ->once()
                ->andReturn($data);
        });

        $dashboardService = new DashboardService();
        $response = $dashboardService->getCachedSeasonalTopFiveDepartmentSalesForChart([], 1, false);

        expect($response)->toHaveKeys(['data', 'labels']);
    }
);

test(
    'getCachedSeasonalTopFiveDepartmentSalesForChartComparison method is called DepartmentQueries to get desired data for comparison',
    function (): void {
        $data = collect([
            [
                'department_id' => 1,
                'department_name' => 'red',
                'total_sales' => 1,
                'total_units_sold' => 1,
            ],
        ]);

        $filterData = [
            'x_start_date' => null,
            'x_end_date' => null,
            'y_start_date' => null,
            'y_end_date' => null,
            'location_id' => null,
            'brand_id' => null,
            'x_sale_season_name' => null,
            'y_sale_season_name' => null,
        ];

        $this->mock(DepartmentQueries::class, function ($mock) use ($data): void {
            $mock->shouldReceive('getCachedSeasonalTopFiveDepartmentSalesForChart')
                ->twice()
                ->andReturn($data);
        });

        $dashboardService = new DashboardService();
        $response = $dashboardService->getCachedSeasonalTopFiveDepartmentSalesForChartComparison($filterData, 1, false);

        expect($response)->toHaveKeys(['data', 'labels', 'legendData']);
    }
);

test(
    'getCachedSeasonalTopFiveColorGroupSalesForChart method is called ColorGroupQueries to get desired data',
    function (): void {
        $data = collect([
            [
                'color_group_id' => 1,
                'color_group_name' => 'red',
                'total_sales' => 1,
                'total_units_sold' => 1,
            ],
        ]);

        $this->mock(ColorGroupQueries::class, function ($mock) use ($data): void {
            $mock->shouldReceive('getCachedSeasonalTopFiveColorGroupSalesForChart')
                ->once()
                ->andReturn($data);
        });

        $dashboardService = new DashboardService();
        $response = $dashboardService->getCachedSeasonalTopFiveColorGroupSalesForChart([], 1, [], false);

        expect($response)->toHaveKeys(['data', 'labels']);
    }
);

test(
    'getCachedSeasonalTopFiveColorGroupSalesForChartComparison method is called ColorGroupQueries to get desired data for comparison',
    function (): void {
        $data = collect([
            [
                'color_group_id' => 1,
                'color_group_name' => 'red',
                'total_sales' => 1,
                'total_units_sold' => 1,
            ],
        ]);

        $filterData = [
            'x_start_date' => null,
            'x_end_date' => null,
            'y_start_date' => null,
            'y_end_date' => null,
            'location_id' => null,
            'brand_id' => null,
            'x_sale_season_name' => null,
            'y_sale_season_name' => null,
        ];

        $this->mock(ColorGroupQueries::class, function ($mock) use ($data): void {
            $mock->shouldReceive('getCachedSeasonalTopFiveColorGroupSalesForChart')
                ->twice()
                ->andReturn($data);
        });

        $dashboardService = new DashboardService();
        $response = $dashboardService->getCachedSeasonalTopFiveColorGroupSalesForChartComparison($filterData, 1, false);

        expect($response)->toHaveKeys(['data', 'labels', 'legendData']);
    }
);

test('getCachedSeasonalTopFiveSizeSalesForChart method is called SizeQueries to get desired data', function (): void {
    $data = collect([
        [
            'size_id' => 1,
            'size_name' => 'red',
            'total_sales' => 1,
            'total_units_sold' => 1,
        ],
    ]);

    $this->mock(SizeQueries::class, function ($mock) use ($data): void {
        $mock->shouldReceive('getCachedSeasonalTopFiveSizeSalesForChart')
            ->once()
            ->andReturn($data);
    });

    $dashboardService = new DashboardService();
    $response = $dashboardService->getCachedSeasonalTopFiveSizeSalesForChart([], 1, false);

    expect($response)->toHaveKeys(['data', 'labels']);
});

test(
    'getCachedSeasonalTopFiveSizeSalesForChartComparison method is called SizeQueries to get desired data for comparison',
    function (): void {
        $data = collect([
            [
                'color_group_id' => 1,
                'color_group_name' => 'red',
                'total_sales' => 1,
                'total_units_sold' => 1,
            ],
        ]);

        $filterData = [
            'x_start_date' => null,
            'x_end_date' => null,
            'y_start_date' => null,
            'y_end_date' => null,
            'location_id' => null,
            'brand_id' => null,
            'x_sale_season_name' => null,
            'y_sale_season_name' => null,
        ];

        $this->mock(SizeQueries::class, function ($mock) use ($data): void {
            $mock->shouldReceive('getCachedSeasonalTopFiveSizeSalesForChart')
                ->twice()
                ->andReturn($data);
        });

        $dashboardService = new DashboardService();
        $response = $dashboardService->getCachedSeasonalTopFiveSizeSalesForChartComparison($filterData, 1, false);

        expect($response)->toHaveKeys(['data', 'labels', 'legendData']);
    }
);

test('Retrieve All Sales Details', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');
    setCompanyIdInSession();
    $date = Carbon::now()->format('Y-m-d');

    $sale = Sale::factory()->make([
        'id' => 1,
        'member_id' => 1,
        'counter_update_id' => 1,
    ]);

    $saleItem = SaleItem::factory()->make([
        'sale_id' => $sale->id,
        'product_id' => 1,
        'derivative_id' => 1,
    ]);

    $this->mock(SaleItemQueries::class, function ($mock) use ($saleItem): void {
        $mock->shouldReceive('getSalesForDashboardByDate')
            ->once()
            ->andReturn(collect([$saleItem]));
    });

    $saleReturn = SaleReturn::factory()->make([
        'id' => 1,
        'original_sale_id' => 1,
        'counter_update_id' => 1,
        'member_id' => 1,
    ]);

    $saleReturnItem = SaleReturnItem::factory()->make([
        'id' => 1,
        'sale_return_id' => $saleReturn->id,
        'original_sale_item_id' => 1,
        'product_id' => 1,
        'sale_return_reason_id' => 1,
    ]);

    $this->mock(SaleReturnItemQueries::class, function ($mock) use ($saleReturnItem): void {
        $mock->shouldReceive('getSalesReturnForDashboardByDate')
            ->once()
            ->andReturn(collect([$saleReturnItem]));
    });

    $dashboardService = new DashboardService();
    $dashboardService->getAllSalesDetailsByCompanyId(1, $date, $date);
    Carbon::setTestNow();
});
