<?php

declare(strict_types=1);

use App\Domains\Consignment\Services\ConsignmentReportService;
use App\Domains\Product\ProductQueries;
use App\Http\Controllers\Admin\ConsignmentReportController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get fetchConsignmentReport consignment report method of the product queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'per_page' => 'test',
            'date_range' => null,
        ];

        $consignmentQueries = $this->mock(ProductQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedConsignmentReport')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));

            $mock->shouldReceive('getConsignmentReportForExport')
                ->once()
                ->andReturn(collect([]));
        });

        $consignmentReportController = new ConsignmentReportController($consignmentQueries);

        $response = $consignmentReportController->fetchConsignmentReport(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        expect($response['data']->resource)->toBeInstanceOf(LengthAwarePaginator::class);
    }
);

test('It calls the exportConsignmentReport method and returns a proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'per_page' => 'test',
        'date_range' => null,
        'export_columns' => null,
    ];

    $consignmentQueries = $this->mock(ProductQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getConsignmentReportForExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Product()));
    });

    $consignmentReportController = new ConsignmentReportController($consignmentQueries);

    $response = $consignmentReportController->exportConsignmentReport('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'the printConsignment method and returns the string',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'search_text' => [],
            'per_page' => [],
            'date_range' => [],
            'export_columns' => null,
        ];

        $this->mock(ConsignmentReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $productQueries = $this->mock(ProductQueries::class);

        $consignmentReportController = new ConsignmentReportController($productQueries);
        $response = $consignmentReportController->printConsignment(new Request($filterData));

        expect($response)->toBeString();
    }
);
