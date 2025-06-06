<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Common\Services\DashboardService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use App\Http\Controllers\StoreManager\DashboardController;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('operationalView method returns required data', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');
    $date = Carbon::now()->format('Y-m-d');

    $brand = Brand::factory()->make([
        'id' => 1,
        'name' => 'ABCD',
    ]);

    $this->mock(BrandQueries::class, function ($mock) use ($brand): void {
        $mock->shouldReceive('getBrands')
            ->once()
            ->andReturn(collect([$brand]));
    });

    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();

    $dashboardController = new DashboardController();
    $response = $dashboardController->index(new Request([
        'date' => $date,
    ]));
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));
    $newResponse->assertInertia(fn (Assert $inertia): Assert => $inertia->has('locationId')->has('date'));

    Carbon::setTestNow();
});

it('displays the correct total sales for a single location in storeRevenueView', function (): void {
    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();
    $date = Carbon::now()->format('Y-m-d');
    $data = [
        'name' => 'Location A',
        'total_sales' => 500,
        'total_units_sold' => 100,
    ];

    $controller = new DashboardController();

    $this->mock(LocationQueries::class, function ($mock) use ($data): void {
        $mock->shouldReceive('getCachedStoresSalesForChart')
            ->once()
            ->andReturn(collect([$data]));
    });

    $brand = Brand::factory()->make([
        'id' => 1,
        'name' => 'ABCD',
    ]);

    $this->mock(BrandQueries::class, function ($mock) use ($brand): void {
        $mock->shouldReceive('getBrands')
            ->once()
            ->andReturn(collect([$brand]));
    });

    $this->mock(SaleQueries::class, function ($mock): void {
        $mock->shouldReceive('totalCreditSalePendingAmount')
            ->once();
    });

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getHourlyBasedData')
            ->once()
            ->andReturn([[10, 20], [10, 20], [10, 20], [10, 20], [10, 20]]);
        $mock->shouldReceive('getCachedBrandsSalesForChart')
            ->times(1)
            ->andReturn([collect([]), [], []]);
        $mock->shouldReceive('getCachedColorsSalesForChart')
            ->times(1)
            ->andReturn([collect([]), [], []]);
        $mock->shouldReceive('getCachedCategoriesSalesForChart')
            ->times(1)
            ->andReturn([collect([]), [], []]);
        $mock->shouldReceive('getCachedDepartmentSaleForChart')
            ->times(1)
            ->andReturn([collect([]), [], []]);
        $mock->shouldReceive('getCachedColorGroupSalesForChart')
            ->times(1)
            ->andReturn([collect([]), [], []]);
        $mock->shouldReceive('getCachedSizeSalesForChart')
            ->times(1)
            ->andReturn([collect([]), [], []]);
        $mock->shouldReceive('getCachedStyleSalesForChart')
            ->times(1)
            ->andReturn([collect([]), [], []]);
    });

    $response = $controller->storeRevenueView(new Request([
        'date' => $date,
    ]));
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
        ->has('locationId')
        ->has('totalSalesByColor')
        ->has('totalSalesByBrand')
        ->has('totalSalesByCategory')
        ->has('totalSalesByDepartment')
        ->has('todayHourlySales')
        ->has('yesterdayHourlySales')
        ->has('totalSales')
        ->has('totalUnitsSold')
        ->has('brandsData')
        ->has('colorsData')
        ->has('categoriesData')
        ->has('departmentsData')
        ->has('totalSalesByStyle')
        ->has('stylesData')
        ->has('styleFooterData')
    );
});

test('getOperationalSalesCount method returns required data', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');

    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();

    $request = new Request();

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

    $dashboardController = new DashboardController();
    $response = $dashboardController->getOperationalSalesCount($request);

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

    Carbon::setTestNow();
});

test('getOperationalTodaySales method returns required data', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');

    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();

    $request = new Request();

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

    $dashboardController = new DashboardController();
    $response = $dashboardController->getOperationalTodaySales($request);
    expect($response['today'])
        ->toHaveKey('totalSale', 10)
        ->toHaveKey('totalUnitsSold', 10)
        ->toHaveKey('upt', 10)
        ->toHaveKey('atv', 10);

    Carbon::setTestNow();
});

test('getOperationalThisMonthSales method returns required data', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');

    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();

    $request = new Request();

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

    $dashboardController = new DashboardController();
    $response = $dashboardController->getOperationalThisMonthSales($request);
    expect($response['thisMonth'])
        ->toHaveKey('totalSale', 10)
        ->toHaveKey('totalUnitsSold', 10)
        ->toHaveKey('upt', 10)
        ->toHaveKey('atv', 10);

    Carbon::setTestNow();
});

test('getOperationalThisYearSales method returns required data', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');

    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();

    $request = new Request();

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

    $dashboardController = new DashboardController();
    $response = $dashboardController->getOperationalThisYearSales($request);
    expect($response['thisYear'])
        ->toHaveKey('totalSale', 10)
        ->toHaveKey('totalUnitsSold', 10)
        ->toHaveKey('upt', 10)
        ->toHaveKey('atv', 10);

    Carbon::setTestNow();
});

test('getOperationalRevenueChartData method returns required data', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');

    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();

    $request = new Request();

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getMonthWiseSalesDetails')
            ->twice()
            ->andReturn(collect([]));
        $mock->shouldReceive('getRevenueChartDataWithLastYear')
            ->once()
            ->andReturn([0, 100]);
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getOperationalRevenueChartData($request);
    expect($response['revenueChartData'])
        ->toHaveKey('0', 0)
        ->toHaveKey('1', 100);

    Carbon::setTestNow();
});

test('getOperationalAtvChartData method returns required data b', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');

    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();

    $request = new Request();

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getMonthWiseSalesDetails')
            ->once()
            ->andReturn(collect([]));
        $mock->shouldReceive('getATVChartData')
            ->once()
            ->andReturn([0, 10]);
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getOperationalAtvChartData($request);

    expect($response['atvChartData'])
        ->toHaveKey('0', 0)
        ->toHaveKey('1', 10);

    Carbon::setTestNow();
});

test('getOperationalUptChartData method returns required data b', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');

    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();

    $request = new Request();

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getMonthWiseSalesDetails')
            ->once()
            ->andReturn(collect([]));
        $mock->shouldReceive('getUPTChartData')
            ->once()
            ->andReturn([0, 2]);
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getOperationalUptChartData($request);

    expect($response['uptChartData'])
        ->toHaveKey('0', 0)
        ->toHaveKey('1', 2);

    Carbon::setTestNow();
});

test('getOperationalTopPromoters method returns required data b', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');

    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();

    $request = new Request();

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getTopPromoters')
            ->once()
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getOperationalTopPromoters($request);

    expect($response['topPromoters'])->toBeInstanceOf(Collection::class);

    Carbon::setTestNow();
});

test('getOperationalThisYearTopPromoters method returns required data', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');

    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();

    $request = new Request();

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getYearlyTopPromoters')
            ->once()
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getOperationalThisYearTopPromoters($request);
    expect($response['thisYearTopPromoters'])->toBeInstanceOf(Collection::class);
    Carbon::setTestNow();
});

test(
    'getTopRankingProducts calls commonQueryAccumulatedSaleThroughSalesAndReturnsDataByProductArticleNumberForDashboard of  productQueries class and response',
    function (): void {
        setStoreManagerStoreCompanyIdInSession();
        setStoreIdInSession();
        $locationId = 1;

        $data = [
            'name' => 'abc',
            'article_number' => 100,
            'sell_through' => 10,
        ];

        $this->mock(SellThroughAggregateQueries::class, function ($mock) use ($data): void {
            $mock->shouldReceive('sellThroughAggregateByProductArticleNumberForDashboard')
                ->times(1)
                ->andReturn(collect($data));
        });

        $dashboardController = new DashboardController();
        $response = $dashboardController->getTopRankingProducts();

        expect($response)->toHaveCount(1);
        expect($response)->toHaveKey('topRankingProducts');
        expect($response['topRankingProducts'])->toBeInstanceOf(Collection::class);
    }
);

test('getPurchaseRequest method returns required data', function (): void {
    $locationId = 1;

    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();

    $purchaseOrder = PurchaseOrder::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'location_id' => $locationId,
        'created_by_company_id' => 1,
        'status' => Statuses::OPENED->value,
        'external_purchase_order_id' => null,
        'parent_purchase_order_id' => null,
        'external_company_id' => null,
        'external_location_id' => null,
        'order_type' => OrderTypes::PURCHASE_REQUEST->value,
    ]);

    $purchaseOrder->count = 1;

    $data = [
        'location_id' => $locationId,
        'order_type' => OrderTypes::PURCHASE_REQUEST->value,
    ];

    $this->mock(PurchaseOrderQueries::class, function ($mock) use ($data, $purchaseOrder): void {
        $mock->shouldReceive('getDashboardStatusCount')
            ->once()
            ->with($data, 1)
            ->andReturn(collect([$purchaseOrder]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getPurchaseRequest();
    expect($response['purchaseRequests'])->not->toBeEmpty();
});

test('getTransferRequest method returns required data', function (): void {
    $locationId = 1;

    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();

    $purchaseOrder = PurchaseOrder::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'location_id' => $locationId,
        'created_by_company_id' => 1,
        'status' => Statuses::OPENED->value,
        'external_purchase_order_id' => null,
        'parent_purchase_order_id' => null,
        'external_company_id' => null,
        'external_location_id' => null,
        'order_type' => OrderTypes::TRANSFER_REQUEST->value,
    ]);

    $purchaseOrder->count = 1;

    $data = [
        'location_id' => $locationId,
        'order_type' => OrderTypes::TRANSFER_REQUEST->value,
    ];

    $this->mock(PurchaseOrderQueries::class, function ($mock) use ($data, $purchaseOrder): void {
        $mock->shouldReceive('getDashboardStatusCount')
            ->once()
            ->with($data, 1)
            ->andReturn(collect([$purchaseOrder]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getTransferRequest();
    expect($response['transferRequests'])->not->toBeEmpty();
});

test('getSalesOrder method returns required data', function (): void {
    $locationId = 1;

    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();

    $purchaseOrder = PurchaseOrder::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'location_id' => $locationId,
        'created_by_company_id' => 1,
        'status' => Statuses::OPENED->value,
        'external_purchase_order_id' => null,
        'parent_purchase_order_id' => null,
        'external_company_id' => null,
        'external_location_id' => null,
        'order_type' => OrderTypes::SALES_ORDER->value,
    ]);

    $purchaseOrder->count = 1;

    $fulfillments = PurchaseOrderFulfillment::factory()->make([
        'id' => 1,
        'purchase_order_id' => $purchaseOrder->id,
        'purchase_order_invoice_id' => 1,
        'delivery_order_number' => '1234567',
    ]);

    $fulfillments->count = 1;

    $data = [
        'location_id' => $locationId,
        'order_type' => OrderTypes::SALES_ORDER->value,
    ];

    $this->mock(PurchaseOrderQueries::class, function ($mock) use ($data, $purchaseOrder): void {
        $mock->shouldReceive('getDashboardStatusCount')
            ->once()
            ->with($data, 1)
            ->andReturn(collect([$purchaseOrder]));
    });

    $this->mock(PurchaseOrderFulfillmentQueries::class, function ($mock) use ($data, $fulfillments): void {
        $mock->shouldReceive('getDashboardStatusCount')
            ->once()
            ->with($data, 1)
            ->andReturn(collect([$fulfillments]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getSalesOrder();

    expect($response['salesOrders'])->not->toBeEmpty();
    expect($response['salesDeliveryOrders'])->not->toBeEmpty();
});

test('getPurchaseOrder method returns required data', function (): void {
    $locationId = 1;

    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();

    $purchaseOrder = PurchaseOrder::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'location_id' => $locationId,
        'created_by_company_id' => 1,
        'status' => Statuses::OPENED->value,
        'external_purchase_order_id' => null,
        'parent_purchase_order_id' => null,
        'external_company_id' => null,
        'external_location_id' => null,
        'order_type' => OrderTypes::PURCHASE_ORDER->value,
    ]);

    $purchaseOrder->count = 1;

    $fulfillments = PurchaseOrderFulfillment::factory()->make([
        'id' => 1,
        'purchase_order_id' => $purchaseOrder->id,
        'purchase_order_invoice_id' => 1,
        'delivery_order_number' => '1234567',
    ]);

    $fulfillments->count = 1;

    $data = [
        'location_id' => $locationId,
        'order_type' => OrderTypes::PURCHASE_ORDER->value,
    ];

    $this->mock(PurchaseOrderQueries::class, function ($mock) use ($data, $purchaseOrder): void {
        $mock->shouldReceive('getDashboardStatusCount')
            ->once()
            ->with($data, 1)
            ->andReturn(collect([$purchaseOrder]));
    });

    $this->mock(PurchaseOrderFulfillmentQueries::class, function ($mock) use ($data, $fulfillments): void {
        $mock->shouldReceive('getDashboardStatusCount')
            ->once()
            ->with($data, 1)
            ->andReturn(collect([$fulfillments]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getPurchaseOrder();
    expect($response['purchaseOrders'])->not->toBeEmpty();
    expect($response['purchaseDeliveryOrders'])->not->toBeEmpty();
});

test('printStoreRevenue method returns required data', function (): void {
    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();
    $request = new Request([
        'date' => '',
        'brand_id' => 1,
        'location_id' => 1,
        'type' => StoreRevenueDashboardTableFilterTypes::CATEGORIES->value,
    ]);

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'ABCD',
        'default_country_id' => 1,
    ]);

    $brand = Brand::factory()->make([
        'id' => 1,
        'name' => 'ABCD',
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $this->mock(BrandQueries::class, function ($mock) use ($brand): void {
        $mock->shouldReceive('getById')
            ->andReturn($brand);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getById')
        ->andReturn($location);
        $mock->shouldReceive('getCachedStoresSalesForChart')
        ->andReturn(collect());
    });

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->andReturn($company);
    });

    $this->mock(CategoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedCategoriesSalesForChart')
        ->andReturn(collect());
    });

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
        ->times(2)
        ->andReturn(new Currency([
            'symbol' => 'RS',
        ]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->printStoreRevenue($request);

    expect($response)->toBeString();
});

test('exportStoreRevenue method returns required data', function (): void {
    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();
    $request = new Request([
        'date' => '',
        'brand_id' => 1,
        'location_id' => 1,
        'type' => StoreRevenueDashboardTableFilterTypes::CATEGORIES->value,
    ]);

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'ABCD',
        'default_country_id' => 1,
    ]);

    $brand = Brand::factory()->make([
        'id' => 1,
        'name' => 'ABCD',
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
    ]);

    $this->mock(BrandQueries::class, function ($mock) use ($brand): void {
        $mock->shouldReceive('getById')
            ->andReturn($brand);
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location): void {
        $mock->shouldReceive('getById')
        ->andReturn($location);
        $mock->shouldReceive('getCachedStoresSalesForChart')
        ->andReturn(collect());
    });

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->andReturn($company);
    });

    $this->mock(CategoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedCategoriesSalesForChart')
        ->andReturn(collect());
    });

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
        ->times(2)
        ->andReturn(new Currency([
            'symbol' => 'RS',
        ]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->exportStoreRevenue('demo.csv', $request);

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

it('getNegativeStockStockOverview it returns the negative stocks', function (): void {
    setStoreManagerStoreCompanyIdInSession();
    setStoreIdInSession();

    $this->mock(InventoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getNegativeStockItems')
            ->andReturn(10);
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getNegativeStockStockOverview(new Request());

    expect($response['negativeStockItemCount'])->toBe(10);
});

test(
    'it calls getCachedWorstSellingProduct productQueries class to get worst selling products by year',
    function (): void {
        $companyId = 1;
        $locationId = 1;
        setStoreManagerStoreCompanyIdInSession();
        setStoreIdInSession();

        $request = new Request([
            'location_id' => $locationId,
        ]);

        $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getCachedWorstSellingProduct')
                ->times(1)
                ->andReturn(collect([]));
        });

        $dashboardController = new DashboardController();
        $response = $dashboardController->getThisYearWorstSellingProducts($request);

        expect($response)->toHaveCount(1);
        expect($response)->toHaveKey('thisYearWorstSellingProducts');
        expect($response['thisYearWorstSellingProducts']->resource)->toBeInstanceOf(Collection::class);
    }
);

test(
    'it calls getCachedWorstSellingProduct productQueries class to get worst selling products by month',
    function (): void {
        setStoreManagerStoreCompanyIdInSession();
        setStoreIdInSession();
        $locationId = 1;

        $request = new Request([
            'location_id' => $locationId,
        ]);

        $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getCachedWorstSellingProduct')
            ->times(1)
            ->andReturn(collect([]));
        });

        $dashboardController = new DashboardController();
        $response = $dashboardController->getThisMonthWorstSellingProducts($request);

        expect($response)->toHaveCount(2);
        expect($response)->toHaveKey('thisMonthWorstSellingProducts');
        expect($response['thisMonthWorstSellingProducts']->resource)->toBeInstanceOf(Collection::class);
    }
);
