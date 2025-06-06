<?php

declare(strict_types=1);

use App\Domains\Counter\DataObjects\StoreManagerApiCloseCounterData;
use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CounterUpdate\Resources\StoreManagerAppClosedCounterDetailsResource;
use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Api\StoreManager\ClosedCounterReportController;
use App\Models\Company;
use App\Models\CounterUpdate;
use App\Models\Employee;
use App\Models\Location;
use App\Models\StoreManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
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

test('calls the getClosedCounters method and returns close counter records', function (): void {
    $filterData = [
        'store_id' => $this->location->id,
        'per_page' => 10,
        'page' => 1,
        'start_date' => Carbon::now()->subMonth()->format('Y-m-d'),
        'end_date' => Carbon::now()->subDay()->format('Y-m-d'),
        'location_id' => $this->location->id,
    ];

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndStoreId')
            ->once()
            ->with((int) $this->storeManager->id, (int) $this->location->id)
            ->andReturn(true);
    });

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn(1);
    });

    $this->mock(CounterUpdateQueries::class, function ($mock): void {
        $mock->shouldReceive('getPaginatedClosedCounterListForStoreManager')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        $mock->shouldReceive('closedCounterTotalSalesCollectionForStoreManager')
            ->once()
            ->andReturn(0);
    });

    $request = new Request($filterData);
    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $storeManagerApiCloseCounterData = new StoreManagerApiCloseCounterData(...$filterData);

    $closeCounterReport = new ClosedCounterReportController();
    $response = $closeCounterReport->getClosedCounters($request, $storeManagerApiCloseCounterData);

    expect($response['data']->collection)->toBeInstanceOf(Collection::class);
});

test(
    'getClosedCounters method throws an Exception when the store manager specify a different location',
    function (): void {
        $filterData = [
            'store_id' => $this->location->id,
            'per_page' => 10,
            'page' => 1,
            'start_date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->subDay()->format('Y-m-d'),
            'location_id' => $this->location->id,
        ];

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
            ->once()
                ->with((int) $this->storeManager->id, (int) $this->location->id)
                ->andReturn(false);
        });

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $storeManagerApiCloseCounterData = new StoreManagerApiCloseCounterData(...$filterData);

        $dayCloseController = new ClosedCounterReportController();
        $dayCloseController->getClosedCounters($request, $storeManagerApiCloseCounterData);
    }
)->throws(HttpException::class);

test(
    'getClosedCounters method throws an Exception when the end date is less than start date',
    function (): void {
        $filterData = [
            'store_id' => $this->location->id,
            'per_page' => 10,
            'page' => 1,
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date' => Carbon::now()->subMonth()->format('Y-m-d'),
        ];

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);
        $request->validate(StoreManagerApiCloseCounterData::rules());

        $storeManagerApiCloseCounterData = new StoreManagerApiCloseCounterData(...$filterData);

        $dayCloseController = new ClosedCounterReportController();
        $dayCloseController->getClosedCounters($request, $storeManagerApiCloseCounterData);
    }
)->throws(ValidationException::class);

test(
    'calls the getClosedCounterDetails method and returns closed counter details',
    function (): void {
        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->with((int) $this->storeManager->id, (int) $this->location->id)
                ->andReturn(true);
        });

        $this->mock(EmployeeQueries::class, function ($mock): void {
            $mock->shouldReceive('getEmployeeCompanyId')
                ->once()
                ->andReturn(1);
        });

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('findByIdFilterByCompanyAndStore')
                ->once()
                ->with(1, $this->company->id, $this->location->id)
                ->andReturn(new CounterUpdate());
        });

        $request = $this->mock(Request::class);
        $request->shouldReceive('user')->andReturn($this->storeManager);
        $request->shouldReceive('validate')->once()->andReturn([
            'location_id' => $this->location->id,
            'id' => 1,
        ]);

        $closedCounterReportController = new ClosedCounterReportController();
        $response = $closedCounterReportController->getClosedCounterDetails($request);

        expect($response['closed_counter_update_details'])->toBeInstanceOf(
            StoreManagerAppClosedCounterDetailsResource::class
        );
    }
);

test(
    'calls the getClosedCounterDetails method throws an Exception when the store manager specify a different location',
    function (): void {
        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->with((int) $this->storeManager->id, (int) $this->location->id)
                ->andReturn(false);
        });

        $request = $this->mock(Request::class);
        $request->shouldReceive('user')->andReturn($this->storeManager);
        $request->shouldReceive('validate')->once()->andReturn([
            'location_id' => $this->location->id,
            'id' => 1,
        ]);

        $this->mock(EmployeeQueries::class, function ($mock): void {
            $mock->shouldReceive('getEmployeeCompanyId')
                ->once()
                ->andReturn(1);
        });

        $closedCounterReportController = new ClosedCounterReportController();
        $closedCounterReportController->getClosedCounterDetails($request);
    }
)->throws(HttpException::class);

test(
    'calls the getClosedCounterDetails method throws an Exception when the location specifies a different counter ',
    function (): void {
        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->with((int) $this->storeManager->id, (int) $this->location->id)
                ->andReturn(true);
        });

        $this->mock(CounterUpdateQueries::class, function ($mock): void {
            $mock->shouldReceive('findByIdFilterByCompanyAndStore')
            ->once();
        });

        $request = $this->mock(Request::class);
        $request->shouldReceive('user')->andReturn($this->storeManager);
        $request->shouldReceive('validate')->once()->andReturn([
            'location_id' => $this->location->id,
            'id' => 1,
        ]);

        $this->mock(EmployeeQueries::class, function ($mock): void {
            $mock->shouldReceive('getEmployeeCompanyId')
                ->once()
                ->andReturn(1);
        });

        $closedCounterReportController = new ClosedCounterReportController();
        $closedCounterReportController->getClosedCounterDetails($request);
    }
)->throws(HttpException::class);
