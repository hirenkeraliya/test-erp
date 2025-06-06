<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Sale\SaleQueries;
use App\Domains\Sale\Services\SaleHourReportService;
use App\Domains\SaleReturn\SaleReturnQueries;
use App\Models\Company;
use App\Models\Counter;
use App\Models\CounterUpdate;
use App\Models\Currency;
use App\Models\Location;
use App\Models\Sale;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

it('preparedSalesByHour function returns expected sales data by hour', function (): void {
    $filterData = [
        'date_range' => [],
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => 1,
    ]);

    $location = Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'happened_at' => now(),
        'member_id' => 1,
        'counter_update_id' => $counterUpdate->id,
        'total_amount_paid' => 100,
    ]);

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn($company);
    });

    $this->mock(SaleQueries::class, function ($mock) use ($filterData, $company, $sale): void {
        $mock->shouldReceive('getSaleHourForPrint')
            ->once()
            ->with($filterData, $company->id)
            ->andReturn(collect([$sale]));
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($filterData, $company): void {
        $mock->shouldReceive('getSaleReturnHourForPrint')
            ->once()
            ->with($filterData, $company->id)
            ->andReturn(collect([]));
    });

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
        ->once()
        ->andReturn(new Currency([
            'symbol' => 'RS',
        ]));
    });

    $salesCollectionReportService = new SaleHourReportService();
    [$saleHours] = $salesCollectionReportService->preparedSalesByHour($filterData, $company->id);

    expect($saleHours)
        ->toHaveKeys(['sales', 'totals', 'grand_total'])
        ->toHaveKey('grand_total', $sale->getTotalAmountPaid());
});

it('export function returns expected BinaryFileResponse', function (): void {
    $filterData = [
        'date_range' => [],
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $filterData['company_id'] = $company->id;

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => 1,
    ]);

    Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
        ->once()
        ->andReturn(new Currency([
            'symbol' => 'RS',
        ]));
    });

    $sale = Sale::factory()->make([
        'id' => 1,
        'happened_at' => now(),
        'member_id' => 1,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn($company);
    });

    $this->mock(SaleQueries::class, function ($mock) use ($filterData, $company, $sale): void {
        $mock->shouldReceive('getSaleHourForPrint')
            ->once()
            ->with($filterData, $company->id)
            ->andReturn(collect([$sale]));
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($filterData, $company): void {
        $mock->shouldReceive('getSaleReturnHourForPrint')
            ->once()
            ->with($filterData, $company->id)
            ->andReturn(collect([]));
    });

    $salesCollectionReportService = new SaleHourReportService();
    $result = $salesCollectionReportService->export($filterData, 'demo.csv');

    expect($result)->toBeInstanceOf(BinaryFileResponse::class);
});

it('print function returns expected string', function (): void {
    $filterData = [
        'date_range' => [],
    ];

    $company = Company::factory()->make([
        'id' => 1,
        'name' => 'Test Company',
        'default_country_id' => 1,
    ]);

    $filterData['company_id'] = $company->id;

    $counter = Counter::factory()->make([
        'id' => 1,
        'location_id' => 1,
    ]);

    Location::factory()->make([
        'id' => 1,
        'company_id' => 1,
        'type_id' => LocationTypes::STORE->value,
    ]);

    $counterUpdate = CounterUpdate::factory()->make([
        'id' => 1,
        'counter_id' => $counter->id,
        'cashier_id' => 1,
    ]);

    $sale = Sale::factory()->make([
        'id' => 1,
        'happened_at' => now(),
        'member_id' => 1,
        'counter_update_id' => $counterUpdate->id,
    ]);

    $this->mock(CompanyQueries::class, function ($mock) use ($company): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn($company);
    });

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
        ->once()
        ->andReturn(new Currency([
            'symbol' => 'RS',
        ]));
    });

    $this->mock(SaleQueries::class, function ($mock) use ($filterData, $company, $sale): void {
        $mock->shouldReceive('getSaleHourForPrint')
            ->once()
            ->with($filterData, $company->id)
            ->andReturn(collect([$sale]));
    });

    $this->mock(SaleReturnQueries::class, function ($mock) use ($filterData, $company): void {
        $mock->shouldReceive('getSaleReturnHourForPrint')
            ->once()
            ->with($filterData, $company->id)
            ->andReturn(collect([]));
    });

    $salesCollectionReportService = new SaleHourReportService();
    $result = $salesCollectionReportService->print($company->id, $filterData);

    expect($result)->toBeString();
});
