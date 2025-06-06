<?php

declare(strict_types=1);

use App\Domains\Activity\ActivityLogQueries;
use App\Domains\Activity\Services\ActivityService;
use App\Domains\Common\Enums\ModelMappingTypes;
use App\Domains\Company\CompanyQueries;
use App\Http\Controllers\Admin\ActivityReportController;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the getPaginatedActivityList method of the activitylogQueries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => null,
            'per_page' => null,
            'date_range' => null,
            'employee_id' => null,
            'module_type' => ModelMappingTypes::BASE_MODULES->value,
        ];
        $activityLogQueries = $this->mock(ActivityLogQueries::class, function ($mock) use (
            $filterData,
            $companyId,
        ): void {
            $mock->shouldReceive('getPaginatedActivityList')
                ->once()
                ->with($filterData, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $activityReportController = new ActivityReportController($activityLogQueries);
        $response = $activityReportController->fetchActivities(new Request($filterData));
        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the getActivitiesForExport method and returns a proper response', function (): void {
    setCompanyIdInSession();

    $requestParameter = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
        'date_range' => null,
        'employee_id' => null,
        'module_type' => ModelMappingTypes::BASE_MODULES->value,
        'export_columns' => null,
    ];

    $activityLogQueries = $this->mock(ActivityLogQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getActivitiesForExport')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new Activity()));
    });

    $activityReportController = new ActivityReportController($activityLogQueries);

    $response = $activityReportController->exportActivities('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('the printActivities method and returns the string', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $filterData = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => null,
        'date_range' => null,
        'employee_id' => null,
        'module_type' => ModelMappingTypes::BASE_MODULES->value,
        'export_columns' => null,
    ];

    $activities = new Collection();

    $activityLogQueries = $this->mock(ActivityLogQueries::class, function ($mock) use (
        $filterData,
        $companyId,
        $activities
    ): void {
        $mock->shouldReceive('getActivitiesWithRelationsForPrint')
            ->once()
            ->with($filterData, $companyId)
            ->andReturn($activities);
    });

    $this->mock(CompanyQueries::class, function ($mock) use ($companyId): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->with($companyId)
            ->andReturn(new Company());
    });

    $this->mock(ActivityService::class, function ($mock): void {
        $mock->shouldReceive('activityDataPrint')
            ->once()
            ->andReturn(new Collection());
    });

    $activityReportController = new ActivityReportController($activityLogQueries);
    $response = $activityReportController->printActivities(new Request($filterData));

    expect($response)->toBeString();
});
