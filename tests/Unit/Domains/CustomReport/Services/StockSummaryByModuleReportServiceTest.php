<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use App\Domains\StockSummary\Services\StockSummaryByModuleReportService;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;

test('StockSummaryByModuleReportService generates report successfully', function (): void {
    $company = Company::factory()->make([
        'default_country_id' => 1,
    ]);

    $locations = Location::factory()->count(2)->make([
        'company_id' => 1,
    ]);

    $filterData = [
        'report_by' => 1,
        'report_type' => 2,
        'location_ids' => $locations->pluck('id')->toArray(),
        'date_range' => ['2023-01-01', '2023-01-31'],
        'article_number' => ['A123', 'B456'],
    ];

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->andReturn($company);
    });

    $this->mock(Location::class, function ($mock) use ($locations, $filterData): void {
        $mock->shouldReceive('whereIn')
            ->with('id', $filterData['location_ids'])
            ->andReturn(new Collection($locations));
    });

    $this->mock(SellThroughAggregateQueries::class, function ($mock) use ($filterData): void {
        $mock->shouldReceive('getStockSummaryByModuleForExport')
            ->with($filterData, $filterData['report_type'], $filterData['report_by'])
            ->andReturn(collect([
                [
                    'product_id' => 1,
                    'product_name' => 'Product A',
                    'article_number' => 'A123',
                    'location_code' => 'Location 1',
                    'total_sales' => 100,
                    'total_grn_in' => 50,
                    'total_grn_out' => 20,
                ],
                [
                    'product_id' => 2,
                    'product_name' => 'Product B',
                    'article_number' => 'B456',
                    'location_code' => 'Location 2',
                    'total_sales' => 200,
                    'total_grn_in' => 80,
                    'total_grn_out' => 30,
                ],
            ]));
    });

    $this->mock(LocationQueries::class, function ($mock) use ($locations): void {
        $mock->shouldReceive('getNameByIds')
            ->with($locations->toArray())
            ->andReturn(implode(', ', $locations->pluck('name')->toArray()));
    });

    $service = new StockSummaryByModuleReportService();
    $result = $service->print($filterData, 1, $locations);

    expect($result)->toBeString();
});
