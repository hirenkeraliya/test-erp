<?php

declare(strict_types=1);

use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProfitAndLossReportService;
use App\Http\Controllers\Admin\ProfitAndLossReportController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated Profits and losses report method of the sale item queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'product_id' => null,
            'category_ids' => null,
            'brand_ids' => null,
            'department_ids' => null,
            'size_ids' => null,
            'color_ids' => null,
            'location_ids' => null,
            'date_range' => null,
            'tag_ids' => null,
            'region_ids' => null,
            'counter_ids' => null,
            'product_collection_id' => null,
            'article_numbers' => null,
            'attributes' => null,
        ];

        $saleQueries = $this->mock(ProductQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedProfitsAndLossesReport')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
            $mock->shouldReceive('getFilteredTotalsForProfitsAndLossesReport')
                ->once()
                ->andReturn(collect([
                    'total_quantity_sold' => 100,
                    'total_amount_sold' => 100,
                    'total_quantity_returned' => 100,
                    'total_returned_amount' => 100,
                ]));
        });

        $profitAndLossReportController = new ProfitAndLossReportController($saleQueries);

        $response = $profitAndLossReportController->fetch(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        expect($response['data']->resource)->toBeInstanceOf(LengthAwarePaginator::class);
    }
);

test('It calls the export method and returns a proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'product_id' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'department_ids' => null,
        'size_ids' => null,
        'color_ids' => null,
        'location_ids' => null,
        'article_numbers' => null,
        'date_range' => null,
        'tag_ids' => null,
        'region_ids' => null,
        'counter_ids' => null,
        'product_collection_id' => null,
        'export_columns' => null,
        'attributes' => null,
    ];

    $saleItemQueries = $this->mock(ProductQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getProfitsAndLossesReportForExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Product()));
    });

    $profitAndLossReportController = new ProfitAndLossReportController($saleItemQueries);

    $response = $profitAndLossReportController->export('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'the print method and returns the string',
    function (): void {
        setCompanyIdInSession();
        $filterData = [
            'search_text' => [],
            'sort_by' => [],
            'sort_direction' => [],
            'per_page' => [],
            'product_id' => [],
            'category_ids' => [],
            'brand_ids' => [],
            'department_ids' => [],
            'location_ids' => [],
            'size_ids' => [],
            'color_ids' => [],
            'article_number' => [],
            'date_range' => [],
            'tag_ids' => null,
            'region_ids' => null,
            'counter_ids' => null,
            'product_collection_id' => null,
            'export_columns' => null,
            'attributes' => null,
        ];

        $this->mock(ProfitAndLossReportService::class, function ($mock): void {
            $mock->shouldReceive('print')
                ->once();
        });

        $productQueries = $this->mock(ProductQueries::class);

        $profitAndLossReportController = new ProfitAndLossReportController($productQueries);
        $response = $profitAndLossReportController->print(new Request($filterData));

        expect($response)->toBeString();
    }
);
