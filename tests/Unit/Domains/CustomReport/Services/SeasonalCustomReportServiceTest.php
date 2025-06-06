<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Sale\Enums\SeasonalReportTypes;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\SeasonalCustomReportService;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Domains\SaleSeason\SaleSeasonQueries;
use App\Models\Company;
use App\Models\Currency;
use App\Models\SaleSeason;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

it('print method call and expected string response', function (): void {
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
            ->twice()
            ->andReturn(collect());
    });

    $this->mock(SaleReturnQueries::class, function ($mock): void {
        $mock->shouldReceive('getSeasonalSaleReturnsData')
            ->twice()
            ->andReturn(collect());
    });

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
        ->once()
        ->andReturn(new Currency([
            'symbol' => 'RS',
        ]));
    });

    $filterData = [
        'store_ids' => null,
        'brand_ids' => [],
        'report_type_id' => SeasonalReportTypes::BY_SUMMARY->value,
        'sale_season_id' => $saleSeason->id,
        'compare_sale_season_id' => $compareSaleSeason->id,
    ];

    $seasonalCustomReportService = new SeasonalCustomReportService();
    $response = $seasonalCustomReportService->print($filterData, $company->id);
    expect($response)->toBeString();
});

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
            ->twice()
            ->andReturn(collect());
    });

    $this->mock(SaleReturnQueries::class, function ($mock): void {
        $mock->shouldReceive('getSeasonalSaleReturnsData')
            ->twice()
            ->andReturn(collect());
    });

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
        ->once()
        ->andReturn(new Currency([
            'symbol' => 'RS',
        ]));
    });

    $filterData = [
        'store_ids' => null,
        'brand_ids' => [],
        'report_type_id' => SeasonalReportTypes::BY_SUMMARY->value,
        'sale_season_id' => $saleSeason->id,
        'compare_sale_season_id' => $compareSaleSeason->id,
        'sale_season_date_range' => [$saleSeason->start_date, $saleSeason->end_date],
        'sale_season_compare_date_range' => [$compareSaleSeason->start_date, $compareSaleSeason->end_date],
    ];

    $seasonalCustomReportService = new SeasonalCustomReportService();
    $response = $seasonalCustomReportService->export($filterData, $company->id, 'demo.csv');
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
