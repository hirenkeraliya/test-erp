<?php

declare(strict_types=1);

use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\CounterUpdate\Services\PrintClosedCounterDetailsService;
use App\Http\Controllers\Admin\ClosedCounterReportController;
use App\Models\CounterUpdate;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the list closed counter query method of the counterUpdateQueries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $filterData = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'location_ids' => 'null',
            'counter_ids' => 'null',
            'cashier_id' => 'null',
            'date_range' => 'null',
            'closed_at' => null,
        ];
        $counterUpdateQueries = $this->mock(CounterUpdateQueries::class, function ($mock) use (
            $filterData,
            $companyId
        ): void {
            $mock->shouldReceive('closedCounterQueryList')
                ->once()
                ->with($filterData, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
            $mock->shouldReceive('closedCounterTotalSalesCollection')
                ->once()
                ->with($filterData, $companyId)
                ->andReturn(0);
        });
        $closedCounterReportController = new ClosedCounterReportController($counterUpdateQueries);
        $response = $closedCounterReportController->fetchClosedCounters(new Request($filterData));
        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'It calls the get by id filter by company method of the counterUpdateQueries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $counterUpdateQueries = $this->mock(CounterUpdateQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getByIdFilterByCompany')
                ->once()
                ->with(1, $companyId)
                ->andReturn(new CounterUpdate());
        });
        $closedCounterReportController = new ClosedCounterReportController($counterUpdateQueries);
        $response = $closedCounterReportController->fetchClosedCounterDetails(1);
        expect($response)
            ->toHaveKey('closed_counter_update_details');
    }
);

test('It calls the exportClosedCounters method and returns a proper response', function (): void {
    setCompanyIdInSession();

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'location_ids' => 'null',
        'counter_ids' => 'null',
        'cashier_id' => 'null',
        'date_range' => 'null',
        'closed_at' => null,
        'export_columns' => null,
    ];

    $counterUpdateQueries = $this->mock(CounterUpdateQueries::class, function ($mock) use (
        $requestParameter
    ): void {
        $mock->shouldReceive('closedCounterListForExport')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new CounterUpdate()));
    });

    $closedCounterController = new ClosedCounterReportController($counterUpdateQueries);

    $response = $closedCounterController->exportClosedCounters('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the printCloseCounterDrawerDetails method of PrintClosedCounterDetailsService class',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $counterUpdateQueries = $this->mock(CounterUpdateQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getCounterUpdateTillDetailsByIdAndFilterByCompany')
                ->once()
                ->with(1, $companyId)
                ->andReturn(new CounterUpdate());
        });

        $this->mock(PrintClosedCounterDetailsService::class, function ($mock): void {
            $mock->shouldReceive('printCloseCounterDrawerDetails')
                ->once()
                ->andReturn('test');
        });

        $closedCounterReportController = new ClosedCounterReportController($counterUpdateQueries);
        $response = $closedCounterReportController->exportClosedCounterDrawerDetails(1);
        $this->assertEquals('test', $response);
    }
);

test(
    'It calls the printCloseCounterTrackOfflineMode method of PrintClosedCounterDetailsService class',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);
        $counterUpdateQueries = $this->mock(CounterUpdateQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getCounterUpdateTillDetailsByIdAndFilterByCompany')
                ->once()
                ->with(1, $companyId)
                ->andReturn(new CounterUpdate());
        });

        $this->mock(PrintClosedCounterDetailsService::class, function ($mock): void {
            $mock->shouldReceive('printCloseCounterTrackOfflineMode')
                ->once()
                ->andReturn('test');
        });

        $closedCounterReportController = new ClosedCounterReportController($counterUpdateQueries);
        $response = $closedCounterReportController->exportTrackOfflineMode(1);
        $this->assertEquals('test', $response);
    }
);
