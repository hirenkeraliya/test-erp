<?php

declare(strict_types=1);

use App\Domains\StoreDayClose\StoreDayCloseQueries;
use App\Http\Controllers\StoreManager\DayCloseReportController;
use App\Models\StoreDayClose;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated day close report list for store manager method of the store day close queries class and returns proper response',
    function (): void {
        $locationId = 1;
        setStoreIdInSession();

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'store_manager_id' => 'null',
            'date_range' => 'null',
            'closed_at' => null,
        ];

        $saleQueries = $this->mock(StoreDayCloseQueries::class, function ($mock) use (
            $requestParameter,
            $locationId
        ): void {
            $mock->shouldReceive('getPaginatedDayCloseReportListForStoreManager')
                ->once()
                ->with($requestParameter, $locationId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $dayCloseReportController = new DayCloseReportController($saleQueries);

        $response = $dayCloseReportController->fetchDayCloseReport(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the exportStoreDayClose method and returns a proper response', function (): void {
    setStoreIdInSession();

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'store_manager_id' => 'null',
        'date_range' => 'null',
        'closed_at' => null,
        'export_columns' => null,
    ];

    $storeDayCloseQueries = $this->mock(StoreDayCloseQueries::class, function ($mock) use (
        $requestParameter
    ): void {
        $mock->shouldReceive('getDayCloseListForExportInStoreManagerPanel')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new StoreDayClose()));
    });

    $dayCloseController = new DayCloseReportController($storeDayCloseQueries);

    $response = $dayCloseController->exportStoreDayClose('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
