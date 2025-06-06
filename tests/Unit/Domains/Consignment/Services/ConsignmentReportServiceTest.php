<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Consignment\Services\ConsignmentReportService;
use App\Domains\Product\ProductQueries;
use App\Models\Company;
use Illuminate\Support\Collection;

test('preparedData method is the prepare the data return the response', function (): void {
    $product = collect([]);
    $consignmentReportService = resolve(ConsignmentReportService::class);
    $response = $consignmentReportService->preparedData($product, collect([]));
    expect($response)->toBeInstanceOf(Collection::class);
});

test('print method is the prepare the data return the response', function (): void {
    $this->mock(ProductQueries::class, function ($mock): void {
        $mock->shouldReceive('getConsignmentReportForExport')
            ->once()
            ->andReturn(collect([]));
    });
    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn(new Company());
    });
    $filterData = [
        'search_text' => null,
        'per_page' => 10,
        'date_range' => null,
        'export_columns' => null,
    ];
    $consignmentReportService = resolve(ConsignmentReportService::class);
    $response = $consignmentReportService->print($filterData, 1, collect([]));
    expect($response)->toBeString();
});
