<?php

declare(strict_types=1);

use App\Domains\Brand\BrandQueries;
use App\Domains\Category\CategoryQueries;
use App\Domains\Color\ColorQueries;
use App\Domains\Common\Services\DashboardService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Dashboard\Enums\StoreRevenueDashboardTableFilterTypes;
use App\Domains\Department\DepartmentQueries;
use App\Domains\Inventory\InventoryQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PurchaseOrder\Enums\OrderTypes;
use App\Domains\PurchaseOrder\Enums\Statuses;
use App\Domains\PurchaseOrder\PurchaseOrderQueries;
use App\Domains\PurchaseOrderFulfillment\PurchaseOrderFulfillmentQueries;
use App\Domains\Sale\SaleQueries;
use App\Domains\SaleDiscount\SaleDiscountQueries;
use App\Domains\SaleItemDiscount\SaleItemDiscountQueries;
use App\Domains\SaleSeason\SaleSeasonQueries;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use App\Domains\StoreWiseDailyTotal\StoreWiseDailyTotalQueries;
use App\Domains\Style\StyleQueries;
use App\Http\Controllers\Admin\DashboardController;
use App\Models\Brand;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Location;
use App\Models\Member;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderFulfillment;
use App\Models\Region;
use App\Models\SaleSeason;
use App\Models\StoreWiseDailyTotal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Testing\TestResponse;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('operationalView method returns required data', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');

    $companyId = 1;
    $locationId = 1;
    setCompanyIdInSession();

    $request = new Request([
        'location_id' => $locationId,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'ABC',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $brand = Brand::factory()->make([
        'id' => 1,
        'name' => 'ABCD',
    ]);

    $this->mock(BrandQueries::class, function ($mock) use ($brand): void {
        $mock->shouldReceive('getBrands')
            ->once()
            ->andReturn(collect([$brand]));
    });

    $this->mock(LocationQueries::class, function ($mock) use ($location, $companyId): void {
        $mock->shouldReceive('getStoreWithBasicColumns')
            ->once()
            ->with($companyId)
            ->andReturn(new Collection([$location]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->index($request);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));
    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
            ->has(
                'locations',
                fn (Assert $locations): Assert => $locations
                    ->has(
                        '0',
                        fn (Assert $location): Assert => $location->where('id', 0)->where(
                            'name',
                            'All Locations'
                        )->etc()
                    )
                    ->has('1', fn (Assert $location): Assert => $location->where('id', 1)->where('name', 'ABC')->etc())
            )
    );

    Carbon::setTestNow();
});

test('getPurchaseRequest method returns required data', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'ABC',
    ]);
    setCompanyIdInSession(1);

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
        'order_type' => OrderTypes::PURCHASE_REQUEST->value,
    ]);

    $purchaseOrder->count = 1;

    $data = [
        'location_id' => $location->id,
        'order_type' => OrderTypes::PURCHASE_REQUEST->value,
    ];

    $this->mock(PurchaseOrderQueries::class, function ($mock) use ($data, $purchaseOrder): void {
        $mock->shouldReceive('getDashboardStatusCount')
            ->once()
            ->with($data, 1)
            ->andReturn(collect([$purchaseOrder]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getPurchaseRequest($location->id);
    expect($response['purchaseRequests'])->not->toBeEmpty();
});

test('getTransferRequest method returns required data', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'ABC',
    ]);

    setCompanyIdInSession(1);

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
        'order_type' => OrderTypes::TRANSFER_REQUEST->value,
    ]);

    $purchaseOrder->count = 1;

    $data = [
        'location_id' => $location->id,
        'order_type' => OrderTypes::TRANSFER_REQUEST->value,
    ];

    $this->mock(PurchaseOrderQueries::class, function ($mock) use ($data, $purchaseOrder): void {
        $mock->shouldReceive('getDashboardStatusCount')
            ->once()
            ->with($data, 1)
            ->andReturn(collect([$purchaseOrder]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getTransferRequest($location->id);
    expect($response['transferRequests'])->not->toBeEmpty();
});

test('getSalesOrder method returns required data', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'ABC',
    ]);

    setCompanyIdInSession(1);

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
    $response = $dashboardController->getSalesOrder($location->id);

    expect($response['salesOrders'])->not->toBeEmpty();
    expect($response['salesDeliveryOrders'])->not->toBeEmpty();
});

test('getPurchaseOrder method returns required data', function (): void {
    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'ABC',
        'type_id' => LocationTypes::STORE->value,
    ]);

    setCompanyIdInSession(1);

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
        'location_id' => $location->id,
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
    $response = $dashboardController->getPurchaseOrder($location->id);
    expect($response['purchaseOrders'])->not->toBeEmpty();
    expect($response['purchaseDeliveryOrders'])->not->toBeEmpty();
});

it('displays the correct total sales for a single location', function (): void {
    $data = (object) [
        'id' => 1,
        'name' => 'Location A',
        'code' => '123',
        'sales_count' => 500,
        'total_sales' => 500,
        'total_units_sold' => 100,
    ];
    $date = Carbon::now()->format('Y-m-d');
    $controller = new DashboardController();
    setCompanyIdInSession();

    $brand = Brand::factory()->make([
        'id' => 1,
        'name' => 'ABCD',
    ]);

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

    $response = $controller->revenueView(new Request([
        'date' => $date,
    ]));
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
            ->has('totalSalesByLocation')
            ->has('totalSalesByBrand')
            ->has('totalSalesByCategory')
            ->has('totalSalesByStyle')
            ->has('totalSalesByDepartment')
            ->has('salesData')
    );
});

it('displays the correct total sales for a single location in storeRevenueView', function (): void {
    $data = [
        'name' => 'Location A',
        'total_sales' => 500,
        'total_units_sold' => 100,
    ];

    $request = new Request([
        'location_id' => 1,
    ]);

    $controller = new DashboardController();
    setCompanyIdInSession();

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'ABC',
        'type_id' => LocationTypes::STORE->value,
    ]);

    $brand = Brand::factory()->make([
        'id' => 1,
        'name' => 'ABCD',
    ]);

    $this->mock(LocationQueries::class, function ($mock) use ($data, $location): void {
        $mock->shouldReceive('getStoreWithBasicColumns')
            ->once()
            ->andReturn(collect([$location]));
        $mock->shouldReceive('getCachedStoresSalesForChart')
            ->once()
            ->andReturn(collect([$data]));
    });

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
            ->andReturn([[10, 20], [10, 20], [0, 10], [0, 10], [0, 10]]);
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

    $response = $controller->storeRevenueView($request);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
            ->has('locations')
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

test('it calls getCachedTopSellingProduct productQueries class to get top selling products by year', function (): void {
    $companyId = 1;
    $locationId = 1;
    setCompanyIdInSession($companyId);

    $request = new Request([
        'location_id' => $locationId,
    ]);

    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedTopSellingProduct')
            ->times(1)
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getThisYearTopSellingProducts($request);

    expect($response)->toHaveCount(1);
    expect($response)->toHaveKey('thisYearTopSellingProducts');
    expect($response['thisYearTopSellingProducts']->resource)->toBeInstanceOf(SupportCollection::class);
});

test(
    'it calls getCachedTopSellingProduct productQueries class to get top selling products by month',
    function (): void {
        setCompanyIdInSession();
        $locationId = 1;

        $request = new Request([
            'location_id' => $locationId,
        ]);

        $this->mock(ProductQueries::class, function ($mock): void {
            $mock->shouldReceive('getCachedTopSellingProduct')
                ->times(1)
                ->andReturn(collect([]));
        });

        $dashboardController = new DashboardController();
        $response = $dashboardController->getThisMonthTopSellingProducts($request);

        expect($response)->toHaveCount(2);
        expect($response)->toHaveKey('thisMonthTopSellingProducts');
        expect($response['thisMonthTopSellingProducts']->resource)->toBeInstanceOf(SupportCollection::class);
    }
);

test('it calls getCachedTopSellingColor colorQueries class to get top selling products by year', function (): void {
    setCompanyIdInSession();
    $locationId = 1;

    $request = new Request([
        'location_id' => $locationId,
        'brand_id' => null,
        'refresh' => false,
    ]);

    $this->mock(ColorQueries::class, function ($mock) use ($locationId): void {
        $mock->shouldReceive('getCachedTopSellingColor')
            ->times(1)
            ->with(
                session('admin_company_id'),
                $locationId,
                null,
                now()->startOfYear()->format('Y-m-d'),
                now()->format('Y-m-d'),
                false
            )
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getThisYearTopSellingColors($request);

    expect($response)->toHaveCount(1);
    expect($response)->toHaveKey('thisYearTopSellingColors');
    expect($response['thisYearTopSellingColors'])->toBeInstanceOf(AnonymousResourceCollection::class);
});

test('it calls getCachedTopSellingColor colorQueries class to get top selling products by month', function (): void {
    setCompanyIdInSession();
    $locationId = 1;
    $brandId = 1;

    $request = new Request([
        'location_id' => $locationId,
        'brand_id' => $brandId,
        'refresh' => false,
    ]);

    $this->mock(ColorQueries::class, function ($mock) use ($locationId, $brandId): void {
        $mock->shouldReceive('getCachedTopSellingColor')
            ->times(1)
            ->with(
                session('admin_company_id'),
                $locationId,
                $brandId,
                now()->startOfMonth()->format('Y-m-d'),
                now()->format('Y-m-d'),
                false
            )
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getThisMonthTopSellingColors($request);

    expect($response)->toHaveCount(1);
    expect($response)->toHaveKey('thisMonthTopSellingColors');
    expect($response['thisMonthTopSellingColors'])->toBeInstanceOf(AnonymousResourceCollection::class);
});

test('getOperationalSalesCount method returns required data', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');

    $locationId = 1;
    setCompanyIdInSession();

    $request = new Request([
        'location_id' => $locationId,
    ]);

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

    setCompanyIdInSession();

    $request = new Request([
        'location_id' => 1,
    ]);

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

    setCompanyIdInSession();

    $request = new Request([
        'location_id' => 1,
    ]);

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

    setCompanyIdInSession();

    $request = new Request([
        'location_id' => 1,
    ]);

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

    $locationId = 1;
    setCompanyIdInSession();

    $request = new Request([
        'location_id' => $locationId,
    ]);

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

    $locationId = 1;
    setCompanyIdInSession();

    $request = new Request([
        'location_id' => $locationId,
    ]);

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

    $locationId = 1;
    setCompanyIdInSession();

    $request = new Request([
        'location_id' => $locationId,
    ]);

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

    $locationId = 1;
    setCompanyIdInSession();

    $request = new Request([
        'location_id' => $locationId,
    ]);

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getTopPromoters')
            ->once()
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getOperationalTopPromoters($request);

    expect($response['topPromoters'])->toBeInstanceOf(SupportCollection::class);

    Carbon::setTestNow();
});

test('getOperationalThisYearTopPromoters method returns required data', function (): void {
    Carbon::setTestNow('2022-02-10 00:00:00');

    $locationId = 1;
    setCompanyIdInSession();

    $request = new Request([
        'location_id' => $locationId,
    ]);

    $this->mock(DashboardService::class, function ($mock): void {
        $mock->shouldReceive('getYearlyTopPromoters')
            ->once()
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getOperationalThisYearTopPromoters($request);
    expect($response['thisYearTopPromoters'])->toBeInstanceOf(SupportCollection::class);
    Carbon::setTestNow();
});

test(
    'getTopRankingProducts calls sellThroughAggregateByProductArticleNumberForDashboard of SellThroughAggregateQueries class and response',
    function (): void {
        setCompanyIdInSession();
        $locationId = 1;

        $data = [
            'id' => 'abc',
            'name' => 'abc',
            'article_number' => '123456',
            'sell_through' => 10,
        ];

        $this->mock(SellThroughAggregateQueries::class, function ($mock) use ($data): void {
            $mock->shouldReceive('sellThroughAggregateByProductArticleNumberForDashboard')
                ->times(1)
                ->andReturn(collect($data));
        });

        $dashboardController = new DashboardController();
        $response = $dashboardController->getTopRankingProducts($locationId);

        expect($response)->toHaveCount(1);
        expect($response)->toHaveKey('topRankingProducts');
        expect($response['topRankingProducts'])->toBeInstanceOf(SupportCollection::class);
    }
);

test('getSeasonalData method returns required data.', function (): void {
    $date = Carbon::now();

    $request = new Request([
        'sale_season_id' => 1,
        'location_id' => 0,
        'brand_id' => 0,
    ]);

    setCompanyIdInSession();

    $saleSeason = SaleSeason::factory()->make([
        'company_id' => 1,
        'name' => 'test',
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

    $dashboardController = new DashboardController();
    $response = $dashboardController->getSeasonalData($request);

    expect($response)
        ->toHaveKey('sales', 10)
        ->toHaveKey('total_receipt', 1)
        ->toHaveKey('total_units_sold', 1)
        ->toHaveKey('total_discounts', 20);
});

test('getSeasonalChartData method returns required data', function (): void {
    $date = Carbon::now();

    $request = new Request([
        'sale_season_id' => 1,
        'location_id' => 0,
        'brand_id' => 0,
    ]);

    setCompanyIdInSession();

    $saleSeason = SaleSeason::factory()->make([
        'company_id' => 1,
        'name' => 'test',
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

    $dashboardController = new DashboardController();
    $response = $dashboardController->getSeasonalChartData($request);

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

test('getSeasonalMemberComparisonData method returns required data', function (): void {
    $date = Carbon::now();
    setCompanyIdInSession();
    $saleSeason1 = SaleSeason::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'name' => 'test',
        'start_date' => $date,
        'end_date' => $date,
    ]);

    $saleSeason2 = SaleSeason::factory()->make([
        'id' => 2,
        'company_id' => 1,
        'name' => 'test2',
        'start_date' => $date,
        'end_date' => $date,
    ]);

    $member = Member::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'created_location_id' => 1,
    ]);

    $member['date'] = Carbon::parse($date)->format('Y-m-d');
    $member['members_count'] = 1;

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

    $request = new Request([
        'sale_season_id' => 1,
        'location_id' => 0,
        'brand_id' => 0,
        'comparison_x_sale_season_id' => 1,
        'comparison_y_sale_season_id' => 2,
    ]);

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
    $dashboardController = new DashboardController();
    $response = $dashboardController->getSeasonalMemberComparisonData($request);

    expect($response)
        ->toBe($responseDataShouldBe);
});

test('getSeasonalTotalDiscounts method returns required data', function (): void {
    $date = Carbon::now();

    $request = new Request([
        'sale_season_id' => 1,
        'location_id' => 0,
        'brand_id' => 0,
    ]);

    setCompanyIdInSession();

    $saleSeason = SaleSeason::factory()->make([
        'company_id' => 1,
        'name' => 'test',
        'start_date' => $date,
        'end_date' => $date,
    ]);

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

    $dashboardController = new DashboardController();
    $response = $dashboardController->getSeasonalTotalDiscounts($request);

    expect($response)
        ->toHaveKey('discounts');
});

test('getSeasonalComparisonData method returns required data', function (): void {
    $date = Carbon::now();

    $request = new Request([
        'sale_season_id' => 1,
        'comparison_x_sale_season_id' => 1,
        'comparison_y_sale_season_id' => 2,
        'location_id' => 0,
        'brand_id' => 0,
    ]);

    setCompanyIdInSession();

    $saleSeason = SaleSeason::factory()->make([
        'company_id' => 1,
        'name' => 'test',
        'start_date' => $date,
        'end_date' => $date,
    ]);

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

    $dashboardController = new DashboardController();
    $response = $dashboardController->getSeasonalComparisonData($request);

    expect($response)
        ->toHaveKeys(['comparisonChartData', 'comparisonData']);
});

test('printRevenueViewStoresSales method returns required data', function (): void {
    setCompanyIdInSession();
    $request = new Request([
        'date' => [Carbon::now()->format('Y-m-d'), Carbon::now()->addDay()->format('Y-m-d')],
        'brand_id' => 1,
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

    $this->mock(BrandQueries::class, function ($mock) use ($brand): void {
        $mock->shouldReceive('getById')
            ->andReturn($brand);
    });

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->andReturn($company);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedStoresSalesForChart')
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
    $response = $dashboardController->printRevenueViewStoresSales($request);

    expect($response)->toBeString();
});

test('exportRevenueStoresSales method returns required data', function (): void {
    setCompanyIdInSession();
    $request = new Request([
        'date' => [Carbon::now()->format('Y-m-d'), Carbon::now()->addDay()->format('Y-m-d')],
        'brand_id' => 1,
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

    $this->mock(BrandQueries::class, function ($mock) use ($brand): void {
        $mock->shouldReceive('getById')
            ->andReturn($brand);
    });

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->andReturn($company);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedStoresSalesForChart')
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
    $response = $dashboardController->exportRevenueStoresSales('demo.csv', $request);

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('printStoreRevenue method returns required data', function (): void {
    setCompanyIdInSession();
    $request = new Request([
        'date' => '',
        'brand_id' => 1,
        'location_id' => 0,
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

    $this->mock(BrandQueries::class, function ($mock) use ($brand): void {
        $mock->shouldReceive('getById')
            ->andReturn($brand);
    });

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->andReturn($company);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedStoresSalesForChart')
            ->andReturn(collect());
    });

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->times(2)
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
    });

    $this->mock(CategoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedCategoriesSalesForChart')
            ->andReturn(collect());
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->printStoreRevenue($request);

    expect($response)->toBeString();
});

test('exportStoreRevenue method returns required data', function (): void {
    setCompanyIdInSession();
    $request = new Request([
        'date' => '',
        'brand_id' => 1,
        'location_id' => 0,
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

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->times(2)
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
    });

    $this->mock(BrandQueries::class, function ($mock) use ($brand): void {
        $mock->shouldReceive('getById')
            ->andReturn($brand);
    });

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->andReturn($company);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedStoresSalesForChart')
            ->andReturn(collect());
    });

    $this->mock(CategoryQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedCategoriesSalesForChart')
            ->andReturn(collect());
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->exportStoreRevenue('demo.csv', $request);

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

it('renders sale target page with correct data', function (): void {
    $year = Carbon::now()->year;
    setCompanyIdInSession();
    $companyId = 1;

    $this->mock(SaleTargetQueries::class, function ($mock): void {
        $mock->shouldReceive('getListForSaleTargetChart')
            ->andReturn([]);
    });

    Inertia::shouldReceive('render')
        ->once();

    $dashboardController = new DashboardController();
    $response = $dashboardController->saleTarget();

    expect($response)->toBeInstanceOf(Response::class);
});

it('getNegativeStockStockOverview it returns the negative stocks', function (): void {
    setCompanyIdInSession();

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
        setCompanyIdInSession($companyId);

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
        expect($response['thisYearWorstSellingProducts']->resource)->toBeInstanceOf(SupportCollection::class);
    }
);

test(
    'it calls getCachedWorstSellingProduct productQueries class to get worst selling products by month',
    function (): void {
        setCompanyIdInSession();
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
        expect($response['thisMonthWorstSellingProducts']->resource)->toBeInstanceOf(SupportCollection::class);
    }
);

test(
    'it calls getMemberCountDetails to get member count details',
    function (): void {
        setCompanyIdInSession();
        $locationId = 1;

        $request = new Request([
            'location_id' => $locationId,
        ]);

        $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('getTodayMemberCount')
                ->times(1)
                ->andReturn(10);

            $mock->shouldReceive('getThisWeekMemberCount')
                ->times(1)
                ->andReturn(10);

            $mock->shouldReceive('getThisMonthMemberCount')
                ->times(1)
                ->andReturn(10);

            $mock->shouldReceive('getThisYearMemberCount')
                ->times(1)
                ->andReturn(10);

            $mock->shouldReceive('getLastYearMemberCount')
                ->times(1)
                ->andReturn(10);
        });

        $dashboardController = new DashboardController();
        $response = $dashboardController->getMemberCountDetails($request);

        $data = [
            'member_details' => [
                'today_member_counts' => [
                    'label' => 'Today',
                    'value' => 10,
                ],
                'this_week_member_counts' => [
                    'label' => 'This Week',
                    'value' => 10,
                ],
                'this_month_member_counts' => [
                    'label' => 'This Month',
                    'value' => 10,
                ],
                'this_year_member_counts' => [
                    'label' => 'This Year',
                    'value' => 10,
                ],
                'last_year_member_counts' => [
                    'label' => 'Last Year',
                    'value' => 10,
                ],
            ],
        ];

        expect($response)->toBe($data);
    }
);

test(
    'it calls getNewAndExistingMemberInChartData to prepare member chart data details',
    function (): void {
        setCompanyIdInSession();
        $locationId = 1;

        $request = new Request([
            'location_id' => $locationId,
        ]);

        $this->mock(MemberQueries::class, function ($mock): void {
            $mock->shouldReceive('getNewAndExistingMembers')
                ->times(1)
                ->andReturn(collect([]));
        });

        $dashboardController = new DashboardController();
        $response = $dashboardController->getNewAndExistingMemberInChartData($request);

        $data = [
            'member_details' => [
                'labels' => [],
                'new_member_month_wise_data' => [],
                'existing_member_month_wise_data' => [],
            ],
        ];

        expect($response)->toBe($data);
    }
);

test(
    'it calls getMemberAgeGroupDetails to prepare member age group details',
    function (): void {
        setCompanyIdInSession();
        $locationId = 1;

        $request = new Request([
            'location_id' => $locationId,
        ]);

        $this->mock(SaleQueries::class, function ($mock): void {
            $mock->shouldReceive('getMemberAgeGroupCounts')
                ->times(1)
                ->andReturn(collect([]));
        });

        $dashboardController = new DashboardController();
        $response = $dashboardController->getMemberAgeGroupDetails($request);

        $data = [
            'age_group_details' => [],
        ];

        expect($response)->toBe($data);
    }
);

test(
    'it calls getMemberGenderDetails to prepare member gender details',
    function (): void {
        setCompanyIdInSession();
        $locationId = 1;

        $request = new Request([
            'location_id' => $locationId,
        ]);

        $data = collect([]);

        $this->mock(SaleQueries::class, function ($mock) use ($data): void {
            $mock->shouldReceive('getMemberGender')
                ->times(1)
                ->andReturn($data);
        });

        $dashboardController = new DashboardController();
        $response = $dashboardController->getMemberGenderDetails($request);

        $data = [
            'gender_details' => $data,
        ];

        expect($response)->toBe($data);
    }
);

test('it calls getTopTenMembersByYear method and returns the members records.', function (): void {
    setCompanyIdInSession();
    $locationId = 1;

    $request = new Request([
        'location_id' => $locationId,
    ]);

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('topTenSellingMembers')
            ->once()
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getTopTenMembersByYear($request);
    $this->assertEquals(collect([]), $response['top_ten_members_by_year']->resource);
});

test('it calls getTopTenMembersByMonth method and returns the members records.', function (): void {
    setCompanyIdInSession();
    $locationId = 1;

    $request = new Request([
        'location_id' => $locationId,
    ]);

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('topTenSellingMembers')
            ->once()
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getTopTenMembersByMonth($request);
    $this->assertEquals(collect([]), $response['top_ten_members_by_month']->resource);
});

test('it calls getInactiveMembersCounts method and returns the inactive 90 & 180 members counts.', function (): void {
    setCompanyIdInSession();
    $locationId = 1;

    $request = new Request([
        'location_id' => $locationId,
    ]);

    $this->mock(SaleQueries::class, function ($mock): void {
        $mock->shouldReceive('getInactiveMembers')
            ->times(2)
            ->andReturn(1);
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getInactiveMembersCounts($request);
    $this->assertEquals(1, $response['inactive_members_90_days_count']);
    $this->assertEquals(1, $response['inactive_members_180_days_count']);
});

test('called getTopTenLocation', function (): void {
    $locationCollection = collect([
        'id' => 1,
        'name' => 'Test Location',
        'total_sales' => 2500,
    ]);

    $this->mock(LocationQueries::class, function ($mock) use ($locationCollection): void {
        $mock->shouldReceive('topTenSellingLocation')
            ->andReturn($locationCollection);
    });

    $request = new Request();
    $dashboardController = new DashboardController();
    $response = $dashboardController->getTopTenLocation($request);

    expect($response)->toHaveKeys(['top_ten_location']);
});

test('called getWorstTenLocation', function (): void {
    $locationCollection = collect([
        'id' => 1,
        'name' => 'Test Location',
        'total_sales' => 2500,
    ]);

    $this->mock(LocationQueries::class, function ($mock) use ($locationCollection): void {
        $mock->shouldReceive('worstTenSellingLocation')
            ->andReturn($locationCollection);
    });

    $request = new Request();
    $dashboardController = new DashboardController();
    $response = $dashboardController->getWorstTenLocation($request);

    expect($response)->toHaveKeys(['worst_ten_location']);
});

test('called getTopTenPromoter', function (): void {
    setCompanyIdInSession();

    $promoterCollection = collect([
        'id' => 1,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'total_amount_sold' => 2500,
    ]);

    $this->mock(PromoterQueries::class, function ($mock) use ($promoterCollection): void {
        $mock->shouldReceive('getTopSellingPromoter')
            ->andReturn($promoterCollection);
    });

    $request = new Request();
    $dashboardController = new DashboardController();
    $response = $dashboardController->getTopTenPromoter($request);

    expect($response)->toHaveKeys(['top_ten_promoter']);
});

test('called getWorstTenPromoter', function (): void {
    setCompanyIdInSession();

    $promoterCollection = collect([
        'id' => 1,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'total_amount_sold' => 2500,
    ]);

    $this->mock(PromoterQueries::class, function ($mock) use ($promoterCollection): void {
        $mock->shouldReceive('getWorstSellingPromoter')
            ->andReturn($promoterCollection);
    });

    $request = new Request();
    $dashboardController = new DashboardController();
    $response = $dashboardController->getWorstTenPromoter($request);

    expect($response)->toHaveKeys(['worst_ten_promoter']);
});
