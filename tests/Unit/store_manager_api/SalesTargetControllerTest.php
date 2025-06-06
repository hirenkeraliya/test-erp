<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\SaleTarget\DataObjects\SalesTargetListDataForStoreManagerApp;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Domains\StoreManager\StoreManagerQueries;
use App\Http\Controllers\Api\StoreManager\SalesTargetController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Promoter;
use App\Models\SaleTarget;
use App\Models\StoreManager;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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

function getSalesTargetListDataForStoreManagerApp(): SalesTargetListDataForStoreManagerApp
{
    $salesTargetData = [
        'time_interval_type_id' => null,
        'store_id' => 1,
        'page' => 1,
        'per_page' => 10,
        'sort_by' => 'id',
        'search_text' => null,
        'sort_direction' => 'desc',
        'location_id' => 1,
    ];

    return new SalesTargetListDataForStoreManagerApp(...$salesTargetData);
}

test('calls the getSalesTargets method and returns sales targets list', function (): void {
    $salesTargetDataForStoreManagerApp = getSalesTargetListDataForStoreManagerApp();

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndStoreId')
            ->once()
            ->andReturn(true);
    });

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn($this->company->id);
    });

    $this->mock(SaleTargetQueries::class, function ($mock): void {
        $mock->shouldReceive('getPaginatedListForStoreManager')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $salesTargetController = new SalesTargetController();
    $response = $salesTargetController->getSalesTargets($request, $salesTargetDataForStoreManagerApp);

    expect($response['sales_targets']->collection)->toBeInstanceOf(Collection::class);
});

test('getSalesTargets method throw exception when the store manager specify a different location', function (): void {
    $salesTargetDataForStoreManagerApp = getSalesTargetListDataForStoreManagerApp();

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndStoreId')
            ->once()
            ->andReturn(false);
    });

    $salesTargetController = new SalesTargetController();
    $salesTargetController->getSalesTargets($request, $salesTargetDataForStoreManagerApp);
})->throws(HttpException::class);

test('calls the getTimeIntervalTypes and returns time interval types ', function (): void {
    $salesTargetController = new SalesTargetController();
    $response = $salesTargetController->getTimeIntervalTypes();
    expect($response['time_interval_types'][0])
        ->toHaveKeys(['id', 'name', 'key']);
});

test(
    'calls the getSalesTargetDetails method and returns sales target details',
    function (): void {
        $salesTarget = SaleTarget::factory()->make([
            'id' => 1,
            'company_id' => $this->company->id,
            'target_type' => TargetType::STORE_WISE->value,
            'time_interval_type' => TimeIntervalType::DAILY->value,
            'status' => true,
        ]);

        $salesTarget->locations = $this->location;

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->andReturn(true);
        });

        $this->mock(EmployeeQueries::class, function ($mock): void {
            $mock->shouldReceive('getEmployeeCompanyId')
                ->once()
                ->andReturn($this->company->id);
        });

        $this->mock(SaleTargetQueries::class, function ($mock) use ($salesTarget): void {
            $mock->shouldReceive('getByIdForStoreManagerApp')
                ->once()
                ->andReturn($salesTarget);
        });

        $salesTargetController = new SalesTargetController();
        $response = $salesTargetController->getSalesTargetDetails($request, $salesTarget->id, $this->location->id);

        expect($response['sales_target']->resource->toArray())
            ->toHaveKeys(['name', 'amount']);
    }
);

test(
    'getSalesTargetDetails method throw exception when the store manager specify a different location',
    function (): void {
        $salesTarget = SaleTarget::factory()->make([
            'id' => 1,
            'company_id' => $this->company->id,
            'target_type' => TargetType::STORE_WISE->value,
            'time_interval_type' => TimeIntervalType::DAILY->value,
            'status' => true,
        ]);

        $salesTarget->locations = $this->location;

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->andReturn(false);
        });

        $salesTargetController = new SalesTargetController();
        $salesTargetController->getSalesTargetDetails($request, $salesTarget->id, $this->location->id);
    }
)->throws(HttpException::class);

test('calls the getSalesTargetsByPromoter method and returns sales targets list', function (): void {
    $salesTargetDataForStoreManagerApp = getSalesTargetListDataForStoreManagerApp();

    $request = new Request();
    $request->setUserResolver(fn (): StoreManager => $this->storeManager);

    $this->mock(StoreManagerQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByIdAndStoreId')
            ->once()
            ->andReturn(true);
    });

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn($this->company->id);
    });

    $this->mock(SaleTargetQueries::class, function ($mock): void {
        $mock->shouldReceive('getPaginatedListByPromoter')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $salesTargetController = new SalesTargetController();
    $response = $salesTargetController->getSalesTargetsByPromoter($request, $salesTargetDataForStoreManagerApp);

    expect($response['sales_targets']->collection)->toBeInstanceOf(Collection::class);
});

test(
    'getSalesTargetsByPromoter method throw exception when the store manager specify a different location',
    function (): void {
        $salesTargetDataForStoreManagerApp = getSalesTargetListDataForStoreManagerApp();

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->andReturn(false);
        });

        $salesTargetController = new SalesTargetController();
        $salesTargetController->getSalesTargetsByPromoter($request, $salesTargetDataForStoreManagerApp);
    }
)->throws(HttpException::class);

test(
    'calls the getSalesTargetDetailsByPromoter method and returns sales target details',
    function (): void {
        $salesTarget = SaleTarget::factory()->make([
            'id' => 1,
            'company_id' => $this->company->id,
            'target_type' => TargetType::PROMOTER_WISE->value,
            'time_interval_type' => TimeIntervalType::DAILY->value,
            'status' => true,
        ]);

        $promoter = Promoter::factory()->make([
            'id' => 1,
            'employee_id' => $this->employee->id,
        ]);

        $promoter->locations = $this->location;
        $salesTarget->promoters = $promoter;

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->andReturn(true);
        });

        $this->mock(EmployeeQueries::class, function ($mock): void {
            $mock->shouldReceive('getEmployeeCompanyId')
                ->once()
                ->andReturn($this->company->id);
        });

        $this->mock(SaleTargetQueries::class, function ($mock) use ($salesTarget): void {
            $mock->shouldReceive('getIdByPromoter')
                ->once()
                ->andReturn($salesTarget);
        });

        $salesTargetController = new SalesTargetController();
        $response = $salesTargetController->getSalesTargetDetailsByPromoter(
            $request,
            $salesTarget->id,
            $this->location->id
        );

        expect($response['sales_target']->resource->toArray())
            ->toHaveKeys(['name', 'amount']);
    }
);

test(
    'getSalesTargetDetailsByPromoter method throw exception when the store manager specify a different location',
    function (): void {
        $salesTarget = SaleTarget::factory()->make([
            'id' => 1,
            'company_id' => $this->company->id,
            'target_type' => TargetType::PROMOTER_WISE->value,
            'time_interval_type' => TimeIntervalType::DAILY->value,
            'status' => true,
        ]);

        $promoter = Promoter::factory()->make([
            'id' => 1,
            'employee_id' => $this->employee->id,
        ]);

        $promoter->locations = $this->location;
        $salesTarget->promoters = $promoter;

        $request = new Request();
        $request->setUserResolver(fn (): StoreManager => $this->storeManager);

        $this->mock(StoreManagerQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByIdAndStoreId')
                ->once()
                ->andReturn(false);
        });

        $salesTargetController = new SalesTargetController();
        $salesTargetController->getSalesTargetDetailsByPromoter($request, $salesTarget->id, $this->location->id);
    }
)->throws(HttpException::class);
