<?php

declare(strict_types=1);

use App\Domains\Product\ProductQueries;
use App\Domains\QuantitySold\Enums\ReportTypes;
use App\Http\Controllers\Admin\QuantitySoldReportController;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

test(
    'It calls the List query method of the ProductQueries class and returns proper response according report type',
    function (int $reportType): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'per_page' => 1,
            'sort_by' => 'id',
            'sort_direction' => 'desc',
            'compare_sort_by' => 'id',
            'compare_sort_direction' => 'desc',
            'separate_column_sorting' => false,
            'date_range' => [],
            'location_id' => null,
            'compare_location_id' => null,
            'region_id' => null,
            'compare_region_id' => null,
            'report_type' => $reportType,
            'article_numbers' => [],
            'color_ids' => [],
            'style_ids' => [],
            'category_ids' => [],
            'brand_ids' => [],
            'size_ids' => [],
            'tag_ids' => [],
            'department_ids' => [],
        ];

        $this->mock(ProductQueries::class, function ($mock) use ($requestParameter, $companyId): void {
            if ($requestParameter['report_type'] === ReportTypes::BY_PARENT_ARTICLE_NUMBER->value) {
                $mock->shouldReceive('getCachedProductQuantitySoldReportWithArticleNumber')
                    ->once()
                    ->with($requestParameter, $companyId)
                    ->andReturn(new LengthAwarePaginator([], 20, 15));

                $mock->shouldReceive('getCachedConsolidateProductQuantitySoldSumAndCountWithArticleNumber')
                    ->once()
                    ->with($requestParameter, $companyId)
                    ->andReturn(collect([]));
            }

            if ($requestParameter['report_type'] === ReportTypes::BY_UPC->value) {
                $mock->shouldReceive('getCachedProductQuantitySoldReportWithUpc')
                    ->once()
                    ->with($requestParameter, $companyId)
                    ->andReturn(new LengthAwarePaginator([], 20, 15));

                $mock->shouldReceive('getCachedConsolidateProductQuantitySoldSumAndCountWithUpc')
                    ->once()
                    ->with($requestParameter, $companyId)
                    ->andReturn(collect([]));
            }
        });

        $quantitySoldReportController = new QuantitySoldReportController();

        $response = $quantitySoldReportController->fetchQuantitySold(new Request($requestParameter));

        $this->assertEquals(20, $response['total_records']);
        $this->assertEquals(collect([]), $response['products']->resource);
    }
)->with([ReportTypes::BY_PARENT_ARTICLE_NUMBER->value, ReportTypes::BY_UPC->value]);

test(
    'It calls the List query method of the ProductQueries class and returns proper response according report type with individual sort',
    function (int $reportType): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'per_page' => 1,
            'sort_by' => 'id',
            'sort_direction' => 'desc',
            'compare_sort_by' => 'id',
            'compare_sort_direction' => 'desc',
            'separate_column_sorting' => true,
            'date_range' => [],
            'location_id' => null,
            'compare_location_id' => null,
            'region_id' => null,
            'compare_region_id' => null,
            'report_type' => $reportType,
            'article_numbers' => [],
            'color_ids' => [],
            'style_ids' => [],
            'category_ids' => [],
            'brand_ids' => [],
            'size_ids' => [],
            'tag_ids' => [],
            'department_ids' => [],
        ];

        $this->mock(ProductQueries::class, function ($mock) use ($requestParameter, $companyId): void {
            if ($requestParameter['report_type'] === ReportTypes::BY_PARENT_ARTICLE_NUMBER->value) {
                $mock->shouldReceive('getCachedSingleProductQuantitySoldReportWithArticleNumber')
                    ->once()
                    ->with($requestParameter, $companyId)
                    ->andReturn(new LengthAwarePaginator([], 20, 15));

                $mock->shouldReceive('getCachedSingleCompareProductQuantitySoldReportWithArticleNumber')
                    ->once()
                    ->with($requestParameter, $companyId)
                    ->andReturn(new LengthAwarePaginator([], 20, 15));

                $mock->shouldReceive('getCachedConsolidateProductQuantitySoldSumAndCountWithArticleNumber')
                    ->once()
                    ->with($requestParameter, $companyId)
                    ->andReturn(collect([]));
            }

            if ($requestParameter['report_type'] === ReportTypes::BY_UPC->value) {
                $mock->shouldReceive('getCachedSingleProductQuantitySoldReportWithUpc')
                    ->once()
                    ->with($requestParameter, $companyId)
                    ->andReturn(new LengthAwarePaginator([], 20, 15));

                $mock->shouldReceive('getCachedSingleCompareProductQuantitySoldReportWithUpc')
                    ->once()
                    ->with($requestParameter, $companyId)
                    ->andReturn(new LengthAwarePaginator([], 20, 15));

                $mock->shouldReceive('getCachedConsolidateProductQuantitySoldSumAndCountWithUpc')
                    ->once()
                    ->with($requestParameter, $companyId)
                    ->andReturn(collect([]));
            }
        });

        $quantitySoldReportController = new QuantitySoldReportController();

        $response = $quantitySoldReportController->fetchQuantitySold(new Request($requestParameter));

        $this->assertEquals(20, $response['total_records']);
        $this->assertEquals(collect([]), $response['products']->resource);
    }
)->with([ReportTypes::BY_PARENT_ARTICLE_NUMBER->value, ReportTypes::BY_UPC->value]);
