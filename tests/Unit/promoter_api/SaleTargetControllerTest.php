<?php

declare(strict_types=1);

use App\Domains\Employee\EmployeeQueries;
use App\Domains\SaleTarget\DataObjects\SalesTargetListDataForPromoterApp;
use App\Domains\SaleTarget\Enums\TargetType;
use App\Domains\SaleTarget\Enums\TimeIntervalType;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Http\Controllers\Api\Promoter\SalesTargetController;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Promoter;
use App\Models\SaleTarget;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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

    $this->promoter = Promoter::factory()->make([
        'id' => 1,
        'employee_id' => $this->employee->id,
    ]);
});

function getSaleTargetDataForPromoterApp(): SalesTargetListDataForPromoterApp
{
    $salesTargetData = [
        'time_interval_type_id' => null,
        'page' => 1,
        'per_page' => 10,
        'sort_by' => 'id',
        'search_text' => null,
        'sort_direction' => 'desc',
    ];

    return new SalesTargetListDataForPromoterApp(...$salesTargetData);
}

test('calls the getSalesTargets method and returns sales targets list', function (): void {
    $saleTargetDataForPromoterApp = getSaleTargetDataForPromoterApp();

    $request = new Request();
    $request->setUserResolver(fn (): Promoter => $this->promoter);

    $this->mock(EmployeeQueries::class, function ($mock): void {
        $mock->shouldReceive('getEmployeeCompanyId')
            ->once()
            ->andReturn($this->company->id);
    });

    $this->mock(SaleTargetQueries::class, function ($mock): void {
        $mock->shouldReceive('getPaginatedListForPromoterApp')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $salesTargetController = new SalesTargetController();
    $response = $salesTargetController->getSalesTargets($request, $saleTargetDataForPromoterApp);

    expect($response['sales_targets']->collection)->toBeInstanceOf(Collection::class);
});

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
            'target_type' => TargetType::PROMOTER_WISE->value,
            'time_interval_type' => TimeIntervalType::DAILY->value,
            'status' => true,
        ]);

        $salesTarget->promoters = $this->promoter;

        $request = new Request();
        $request->setUserResolver(fn (): Promoter => $this->promoter);

        $this->mock(EmployeeQueries::class, function ($mock): void {
            $mock->shouldReceive('getEmployeeCompanyId')
                ->once()
                ->andReturn($this->company->id);
        });

        $this->mock(SaleTargetQueries::class, function ($mock) use ($salesTarget): void {
            $mock->shouldReceive('getByIdForPromoterApp')
                ->once()
                ->andReturn($salesTarget);
        });

        $salesTargetController = new SalesTargetController();
        $response = $salesTargetController->getSalesTargetDetails($request, $salesTarget->id);

        expect($response['sales_target']->resource->toArray())
            ->toHaveKeys(['name', 'amount']);
    });
