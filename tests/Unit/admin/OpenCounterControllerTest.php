<?php

declare(strict_types=1);

use App\Domains\CounterUpdate\CounterUpdateQueries;
use App\Domains\Sale\SaleQueries;
use App\Http\Controllers\Admin\OpenCounterController;
use App\Models\CounterUpdate;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the getOpenCounterDetailsForReportsList method of the counterUpdateQueries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $filterData = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => 'desc',
            'per_page' => '10',
            'location_ids' => null,
            'counter_ids' => null,
            'cashier_id' => null,
        ];
        $counterUpdateQueries = $this->mock(CounterUpdateQueries::class, function ($mock) use (
            $filterData,
            $companyId
        ): void {
            $mock->shouldReceive('getOpenCounterDetailsForReportsList')
                ->once()
                ->with($filterData, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $openCounterController = new OpenCounterController($counterUpdateQueries);
        $response = $openCounterController->fetchOpenCounters(new Request($filterData));
        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals([], $response['data']->resource->items());
    }
);

test('It calls the exportOpenCounters method and returns a proper response', function (): void {
    setCompanyIdInSession();

    $requestParameter = [
        'search_text' => null,
        'sort_by' => null,
        'sort_direction' => 'desc',
        'per_page' => '10',
        'location_ids' => null,
        'counter_ids' => null,
        'cashier_id' => null,
    ];

    $counterUpdateQueries = $this->mock(CounterUpdateQueries::class, function ($mock) use (
        $requestParameter
    ): void {
        $mock->shouldReceive('getOpenCounterDetailsExport')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new CounterUpdate()));
    });

    $openCounterController = new OpenCounterController($counterUpdateQueries);

    $response = $openCounterController->exportOpenCounters('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls fetchOpenCounterSales method of the sale and sale return  queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => null,
            'sort_by' => null,
            'sort_direction' => 'desc',
            'per_page' => '10',
        ];

        $saleQueries = $this->mock(SaleQueries::class, function ($mock) use ($requestParameter, $companyId): void {
            $mock->shouldReceive('getOpenCounterSalesDetailsForReportsList')
                ->once()
                ->with($requestParameter, 1, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
            $mock->shouldReceive('getFilteredTotalsForOpenCountersReport')
                ->once()
                ->with($requestParameter, 1, 1)
                ->andReturn(new Sale());
        });

        $openCounterController = new OpenCounterController($saleQueries);

        $response = $openCounterController->fetchOpenCounterSales(new Request($requestParameter), 1);

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);
