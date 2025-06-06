<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Sale\Enums\SeasonalReportTypes;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\SeasonalCustomComparisonReportService;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleSeason\SaleSeasonQueries;
use App\Models\Company;
use App\Models\SaleSeason;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

it('export method call and expected BinaryFileResponse', function (): void {
    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $saleSeason = SaleSeason::factory()->make([
        'id' => 1,
        'company_id' => $company->id,
    ]);

    $compareSaleSeason = SaleSeason::factory()->make([
        'id' => 2,
        'company_id' => $company->id,
    ]);

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn($company);
    });

    $this->mock(SaleSeasonQueries::class, function ($mock) use ($saleSeason, $compareSaleSeason): void {
        $mock->shouldReceive('getById')
            ->twice()
            ->andReturn($saleSeason, $compareSaleSeason);
    });

    $this->mock(SaleQueries::class, function ($mock): void {
        $mock->shouldReceive('getSeasonalSalesData')
            ->once()
            ->andReturn(collect());
    });

    $this->mock(SaleReturnQueries::class, function ($mock): void {
        $mock->shouldReceive('getSeasonalSaleReturnsData')
            ->once()
            ->andReturn(collect());
    });

    $filterData = [
        'store_ids' => null,
        'brand_ids' => [],
        'report_type_id' => SeasonalReportTypes::BY_COMPARISON->value,
        'sale_season_id' => $saleSeason->id,
        'compare_sale_season_id' => $compareSaleSeason->id,
    ];

    $seasonalCustomComparisonReportService = new SeasonalCustomComparisonReportService();
    $response = $seasonalCustomComparisonReportService->export($filterData, $company->id, 'demo.csv');
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
