<?php

declare(strict_types=1);

use App\Domains\StoreDayClose\StoreDayCloseQueries;
use App\Http\Controllers\Admin\DayCloseReportController;
use App\Models\StoreDayClose;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated day close report list method of the store day close queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'location_ids' => 'null',
            'employee_id' => 'null',
            'date_range' => 'null',
            'closed_at' => null,
        ];

        $saleQueries = $this->mock(StoreDayCloseQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedDayCloseReportList')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
            $mock->shouldReceive('totalSaleCollectionAmount')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn([100, 100]);
        });

        $dayCloseReportController = new DayCloseReportController($saleQueries);

        $response = $dayCloseReportController->fetchDayCloseReport(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the exportStoreDayClose method and returns a proper response', function (): void {
    setCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'location_ids' => 'null',
        'employee_id' => 'null',
        'date_range' => 'null',
        'closed_at' => null,
        'export_columns' => null,
    ];

    $StoreDayCloseQueries = $this->mock(StoreDayCloseQueries::class, function ($mock) use (
        $requestParameter
    ): void {
        $mock->shouldReceive('getPaginatedDayCloseListForExport')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new StoreDayClose()));
    });

    $dayCloseController = new DayCloseReportController($StoreDayCloseQueries);

    $response = $dayCloseController->exportStoreDayClose('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
