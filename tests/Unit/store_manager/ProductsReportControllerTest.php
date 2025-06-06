<?php

declare(strict_types=1);

use App\Domains\Product\ProductQueries;
use App\Domains\Product\Services\ProductReportService;
use App\Http\Controllers\StoreManager\ProductsReportController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the get paginated products report for store manager method of the sale item queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession(1);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'product_id' => null,
            'category_ids' => null,
            'location_ids' => [1],
            'brand_ids' => null,
            'department_ids' => null,
            'size_ids' => null,
            'color_ids' => null,
            'article_numbers' => null,
            'date_range' => null,
            'tag_ids' => null,
            'region_ids' => null,
            'counter_ids' => null,
            'product_collection_id' => null,
            'purchase_type' => null,
        ];

        $saleQueries = $this->mock(ProductQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedProductsReport')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
            $mock->shouldReceive('getProductsReportForExport')
                ->once()
                ->andReturn(collect([
                    'total_quantity_sold' => 100,
                    'total_amount_sold' => 100,
                    'total_quantity_returned' => 100,
                    'total_returned_amount' => 100,
                ]));
        });

        $productsReportController = new ProductsReportController($saleQueries);

        $response = $productsReportController->fetchProductsReport(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        expect($response['data']->resource)->toBeInstanceOf(LengthAwarePaginator::class);
    }
);

test('It calls the exportProductsReport method and returns a proper response', function (): void {
    setStoreIdInSession();
    setStoreManagerStoreCompanyIdInSession(1);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'product_id' => null,
        'category_ids' => null,
        'location_ids' => [1],
        'brand_ids' => null,
        'department_ids' => null,
        'size_ids' => null,
        'color_ids' => null,
        'article_numbers' => null,
        'date_range' => null,
        'tag_ids' => null,
        'region_ids' => null,
        'counter_ids' => null,
        'product_collection_id' => null,
        'export_columns' => null,
    ];

    $saleItemQueries = $this->mock(ProductQueries::class, function ($mock) use ($requestParameter): void {
        $mock->shouldReceive('getProductsReportForExport')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new Product()));
    });

    $productsReportController = new ProductsReportController($saleItemQueries);

    $response = $productsReportController->exportProductsReport('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'the printProducts method and returns the string',
    function (): void {
        setStoreIdInSession();
        setStoreManagerStoreCompanyIdInSession(1);

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
            $mock->shouldReceive('print')
                ->once();
        });

        $productQueries = $this->mock(ProductQueries::class);

        $productsReportController = new ProductsReportController($productQueries);
        $response = $productsReportController->printProducts(new Request($filterData));

        expect($response)->toBeString();
    }
);
