<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Sale\Enums\SeasonalReportTypes;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\SeasonalCustomDetailReportService;
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
        'company_id' => 1,
    ]);

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn($company);
    });

    $this->mock(SaleSeasonQueries::class, function ($mock) use ($saleSeason): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($saleSeason);
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
        'report_type_id' => SeasonalReportTypes::BY_SEASON->value,
        'sale_season_id' => $saleSeason->id,
    ];

    $seasonalCustomDetailReportService = new SeasonalCustomDetailReportService();
    $response = $seasonalCustomDetailReportService->print($filterData, $company->id);
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
        'company_id' => 1,
    ]);

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn($company);
    });

    $this->mock(SaleSeasonQueries::class, function ($mock) use ($saleSeason): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($saleSeason);
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
        'report_type_id' => SeasonalReportTypes::BY_SEASON->value,
        'sale_season_id' => $saleSeason->id,
    ];

    $seasonalCustomDetailReportService = new SeasonalCustomDetailReportService();
    $response = $seasonalCustomDetailReportService->export($filterData, $company->id, 'demo.csv');
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
