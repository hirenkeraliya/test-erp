<?php

declare(strict_types=1);

use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProductReportService;
use App\Http\Controllers\Admin\OnlineProductsReportController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the getPaginatedOnlineProductsReport  method of the order item queries class and returns proper response',
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
            'article_numbers' => null,
            'date_range' => null,
            'tag_ids' => null,
            'region_ids' => null,
            'product_collection_id' => null,
            'attributes' => null,
        ];

        $productQueries = $this->mock(ProductQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedOnlineProductsReport')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
            $mock->shouldReceive('getOnlineProductsReportForExport')
                ->once()
                ->andReturn(collect([]));
        });

        $onlineProductsReportController = new OnlineProductsReportController($productQueries);

        $response = $onlineProductsReportController->fetchOnlineProductsReport(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        expect($response['data']->resource)->toBeInstanceOf(LengthAwarePaginator::class);
    }
);

test('It calls the exportOnlineProductsReport method and returns a proper response', function (): void {
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
        'product_collection_id' => null,
        'export_columns' => null,
        'attributes' => null,
    ];

    $productQueries = $this->mock(ProductQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getOnlineProductsReportForExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Product()));
    });

    $onlineProductsReportController = new OnlineProductsReportController($productQueries);

    $response = $onlineProductsReportController->exportOnlineProductsReport(
        'filename.csv',
        new Request($requestParameter)
    );

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'the printOnlineProducts method and returns the string',
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
            'article_numbers' => [],
            'date_range' => [],
            'tag_ids' => null,
            'region_ids' => null,
            'counter_ids' => null,
            'product_collection_id' => null,
            'export_columns' => null,
        ];

        $this->mock(ProductReportService::class, function ($mock): void {
            $mock->shouldReceive('onlineProductPrint')
                ->once();
        });

        $productQueries = $this->mock(ProductQueries::class);

        $onlineProductsReportController = new OnlineProductsReportController($productQueries);
        $response = $onlineProductsReportController->printOnlineProducts(new Request($filterData));

        expect($response)->toBeString();
    }
);
