<?php

declare(strict_types=1);

use App\Domains\Counter\CounterQueries;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\Member\MemberQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\SaleItem\SaleItemQueries;
use App\Domains\SaleReturnItem\SaleReturnItemQueries;
use App\Domains\StockTransfer\Enums\StockTransferTypes;
use App\Domains\StockTransfer\StockTransferQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Domains\StoreWiseDailyTotal\StoreWiseDailyTotalQueries;
use App\Http\Controllers\Api\StoreManager\DashboardController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use App\Models\StockTransfer;
use App\Models\StoreManager;
use App\Models\StoreWiseDailyTotal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->company = Company::factory()->make([
        'id' => 1,
        'default_country_id' => 1,
    ]);

    $this->employee = Employee::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'designation_id' => 1,
    ]);

    $this->storeManager = StoreManager::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
    ]);

    $this->location = Location::factory()->make([
        'id' => 1,
        'company_id' => $this->company->id,
        'type_id' => LocationTypes::STORE->value,
    ]);
});

test('calls the getTransferStatusesData method and returns record', function (): void {
    $stockTransfer = StockTransfer::factory()->make([
        'company_id' => 1,
        'transfer_type' => 2,
        'status' => 6,
        'stock_transfer_reason_id' => 2,
        'source_location_id' => 1,
        'destination_location_id' => 1,
        'requested_by_type' => 'name',
        'requested_by_id' => 1,
    ]);

    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->storeManager);
    $request->shouldReceive('validate')->andReturn([
        'store_id' => $this->location->id,
    ]);

    $this->storeManager->location = $this->location->id;

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndStoreId')
        ->once()
            ->with((int) $this->storeManager->id, (int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdOfStore')
            ->once()
            ->with((int) $this->location->id)
            ->andReturn(true);
    });

    $filterData = [
        'location_id' => $this->location->id,
        'transfer_type' => null,
    ];

    $this->mock(StockTransferQueries::class, function ($mock) use ($filterData, $stockTransfer): void {
        $mock->shouldReceive('storeManagerTransferOrderStatusCount')
            ->once()
            ->with([StockTransferTypes::TRANSFER_ORDER->value], $filterData, $this->company->id, $this->location->id)
            ->andReturn(new Collection([$stockTransfer]));

        $mock->shouldReceive('storeManagerRequestOrderStatusCount')
            ->once()
            ->with([StockTransferTypes::REQUEST_ORDER->value], $filterData, $this->company->id, $this->location->id)
            ->andReturn(new Collection([$stockTransfer]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getTransferStatusesData($request);

    expect($response['transfer_orders'][0])
        ->toHaveKeys(['id', 'name', 'count']);

    expect($response['request_orders'][0])
        ->toHaveKeys(['id', 'name', 'count']);
});

test(
    'getTransferStatusesData method throws an Exception when the store manager specify a different location',
    function (): void {
        $request = $this->mock(Request::class);
        $request->shouldReceive('user')->andReturn($this->storeManager);
        $request->shouldReceive('validate')->andReturn([
            'store_id' => $this->location->id,
        ]);

        $this->storeManager->location = $this->location->id;

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
            ->once()
            ->with((int) $this->storeManager->id, (int) $this->location->id)
            ->andReturn(false);
        });

        $dashboardController = new DashboardController();
        $dashboardController->getTransferStatusesData($request);
    }
)->throws(HttpException::class);

test('getTopTenPromoter method returns required data ', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->storeManager);
    $request->shouldReceive('validate')->andReturn([
        'store_id' => $this->location->id,
    ]);

    $this->storeManager->location = $this->location->id;

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndStoreId')
            ->once()
            ->with((int) $this->storeManager->id, (int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdOfStore')
            ->once()
            ->with((int) $this->location->id)
            ->andReturn(1);
    });

    $this->mock(PromoterQueries::class, function ($mock): void {
        $mock->shouldReceive('getSalesByPromotersForDashboard')
            ->once()
            ->andReturn(collect([]));
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getTopTenPromoter($request);
    expect($response['top_ten_promoter'])->toBeInstanceOf(SupportCollection::class);
});

test('getDashboardAllDetails method returns required data ', function (): void {
    $request = $this->mock(Request::class);
    $request->shouldReceive('user')->andReturn($this->storeManager);
    $request->shouldReceive('validate')->andReturn([
        'store_id' => $this->location->id,
    ]);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->storeManager->location = $this->location->id;

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndStoreId')
            ->once()
            ->with((int) $this->storeManager->id, (int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(CounterQueries::class, function ($mock): void {
        $mock->shouldReceive('getCountByLocation')
            ->once()
            ->with((int) $this->location->id)
            ->andReturn(1);

        $mock->shouldReceive('getCountByOpenCounterForLocation')
            ->once()
            ->with((int) $this->location->id)
            ->andReturn(1);
    });

    $this->mock(PromoterQueries::class, function ($mock): void {
        $mock->shouldReceive('getPromoterCount')
            ->once()
            ->with((int) $this->location->id)
            ->andReturn(1);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getCompanyIdOfStore')
            ->times(2)
            ->with((int) $this->location->id)
            ->andReturn(1);
    });

    $this->mock(MemberQueries::class, function ($mock): void {
        $mock->shouldReceive('getTotalRegisteredMembersForStoreManagerDashboard')
            ->once();
        $mock->shouldReceive('getTotalMembersRegisteredThisMonthForStoreManagerDashboard')
            ->once();
    });

    $this->mock(SaleItemQueries::class, function ($mock): void {
        $mock->shouldReceive('getSaleItemsForTheStoreManagerApplicationDashboard')
            ->times(1)
            ->andReturn(new SaleItem());
    });

    $this->mock(SaleReturnItemQueries::class, function ($mock): void {
        $mock->shouldReceive('getSaleReturnItemForTheStoreManagerApplicationDashboard')
            ->times(1)
            ->andReturn(new SaleReturnItem());
    });

    $this->mock(StoreWiseDailyTotalQueries::class, function ($mock): void {
        $mock->shouldReceive('getTotalSalesDetailsByDateForStoreManagerApplication')
            ->times(2)
            ->andReturn(new StoreWiseDailyTotal());
    });

    $dashboardController = new DashboardController();
    $response = $dashboardController->getDashboardAllDetails($request);

    expect($response)
       ->toHaveKey('total_counters')
       ->toHaveKey('open_counters')
       ->toHaveKey('total_promoters');
});
