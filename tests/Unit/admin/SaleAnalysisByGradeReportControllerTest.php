<?php

declare(strict_types=1);

use App\Domains\Common\Services\PrintPdfHeaderFilterService;
use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Product\ProductQueries;
use App\Domains\SaleThroughRatio\SaleThroughRatioQueries;
use App\Http\Controllers\Admin\SaleAnalysisByGradeReportController;
use App\Models\Company;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('it fetches sale analysis details as expected', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $filterData = [
        'search_text' => null,
        'page' => 1,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'date_range' => null,
        'article_numbers' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'color_ids' => null,
        'size_ids' => null,
        'style_ids' => null,
        'tag_ids' => null,
        'department_ids' => null,
        'product_id' => null,
        'location_id' => null,
        'grade_filter' => null,
        'product_collection_id' => null,
    ];

    $this->mock(SaleThroughRatioQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedPercentageAndName')
            ->once()
            ->andReturn(collect([]));
    });

    $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedSaleThroughAnalysisData')
            ->once()
            ->andReturn(collect([]));
    });

    $saleAnalysisByGradeReportController = new SaleAnalysisByGradeReportController($productQueries);
    $response = $saleAnalysisByGradeReportController->fetchSaleAnalysisByGradeReport(new Request($filterData));

    expect($response['data'])->toBeInstanceOf(Collection::class);
    expect($response)->toHaveKeys(['data', 'total_records', 'last_page', 'current_page', 'per_page']);
});

test('it fetches total sale analysis details as expected', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $filterData = [
        'search_text' => null,
        'page' => 1,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'date_range' => null,
        'article_numbers' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'color_ids' => null,
        'size_ids' => null,
        'style_ids' => null,
        'tag_ids' => null,
        'department_ids' => null,
        'product_id' => null,
        'location_id' => null,
        'grade_filter' => null,
        'product_collection_id' => null,
    ];

    $this->mock(SaleThroughRatioQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedPercentageAndName')
            ->once()
            ->andReturn(collect([]));
    });

    $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedSaleThroughAnalysisData')
            ->once()
            ->andReturn(collect([]));
    });

    $saleAnalysisByGradeReportController = new SaleAnalysisByGradeReportController($productQueries);
    $response = $saleAnalysisByGradeReportController->fetchTotalSaleAnalysisByGradeReport(new Request($filterData));

    expect($response)->toHaveKey('totals');
});

test('it fetches sale analysis details as expected for print', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $filterData = [
        'search_text' => null,
        'page' => 1,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'date_range' => [now(), now()],
        'article_numbers' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'color_ids' => null,
        'size_ids' => null,
        'style_ids' => null,
        'tag_ids' => null,
        'department_ids' => null,
        'product_id' => null,
        'location_id' => null,
        'grade_filter' => null,
        'product_collection_id' => null,
    ];

    $this->mock(SaleThroughRatioQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedPercentageAndName')
            ->once()
            ->andReturn(collect([]));
    });

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn(Company::make());
    });

    $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedSaleThroughAnalysisData')
            ->once()
            ->andReturn(collect([]));
    });

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
    });

    $this->mock(PrintPdfHeaderFilterService::class, function ($mock): void {
        $mock->shouldReceive('buildFilterData')
            ->once()
            ->andReturn([]);
    });

    $saleAnalysisByGradeReportController = new SaleAnalysisByGradeReportController($productQueries);
    $response = $saleAnalysisByGradeReportController->printSaleAnalysisByGradeReport(new Request($filterData));

    expect($response)->toBeString();
});

test('it fetches sale analysis details as expected for export', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $filterData = [
        'search_text' => null,
        'page' => 1,
        'sort_by' => null,
        'sort_direction' => null,
        'per_page' => 1,
        'date' => now()->format('Y-m-d'),
        'article_numbers' => null,
        'category_ids' => null,
        'brand_ids' => null,
        'color_ids' => null,
        'size_ids' => null,
        'style_ids' => null,
        'tag_ids' => null,
        'department_ids' => null,
        'product_id' => null,
        'location_id' => null,
        'grade_filter' => null,
        'product_collection_id' => null,
    ];

    $this->mock(SaleThroughRatioQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedPercentageAndName')
            ->once()
            ->andReturn(collect([]));
    });

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn(Company::make());
    });

    $productQueries = $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getCachedSaleThroughAnalysisData')
            ->once()
            ->andReturn(collect([]));
    });
    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
    });

    $this->mock(PrintPdfHeaderFilterService::class, function ($mock): void {
        $mock->shouldReceive('buildFilterData')
            ->once()
            ->andReturn([]);
    });

    $saleAnalysisByGradeReportController = new SaleAnalysisByGradeReportController($productQueries);
    $response = $saleAnalysisByGradeReportController->exportSaleAnalysisByGradeReport(
        new Request($filterData),
        'abc.csv'
    );

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
