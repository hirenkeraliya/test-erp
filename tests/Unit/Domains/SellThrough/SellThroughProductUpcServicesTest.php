<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Currency\CurrencyQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use App\Domains\SellThroughAggregate\Services\SellThroughProductUpcServices;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'it calls sellThroughAggregateForUpcPaginate and sellThroughAggregateForUpcGet method of the SellThroughAggregateQueries class as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $date = Carbon::now();

        $filterData = [
            'date' => $date,
            'store_id' => null,
            'per_page' => null,
            'report_type' => SellThroughTypes::BY_UPC->value,
            'filter_by' => null,
        ];

        $this->mock(SellThroughAggregateQueries::class, function ($mock): void {
            $mock->shouldReceive('sellThroughAggregateForUpcPaginate')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 20, 15));

            $mock->shouldReceive('sellThroughAggregateForUpcGet')
                ->once()
                ->andReturn(collect([]));
        });

        $sellThroughProductUpcServices = new SellThroughProductUpcServices();
        $response = $sellThroughProductUpcServices->fetchSellThroughDetailsByProductUpc($filterData, $companyId);

        $this->assertInstanceOf(LengthAwarePaginator::class, $response['data']->resource);
    }
);

test('printSellThroughDetailsByProductUpc method will return the string as expected', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $date = Carbon::now()->format('Y-m-d');

    $filterData = [
        'date' => $date,
        'location_ids' => [1],
        'report_type' => SellThroughTypes::BY_UPC->value,
        'filter_by' => null,
    ];

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn(Company::factory()->make([
                'default_country_id' => 1,
            ]));
    });

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
        ->once()
        ->andReturn(new Currency([
            'symbol' => 'RS',
        ]));
    });

    $this->mock(LocationQueries::class, function ($mock) use ($companyId): void {
        $mock->shouldReceive('getLocationNamesWithCodesByIds')
            ->once()
            ->andReturn(Location::factory()->make([
                'company_id' => $companyId,
                'type_id' => LocationTypes::STORE->value,
            ]));
    });

    $this->mock(SellThroughAggregateQueries::class, function ($mock): void {
        $mock->shouldReceive('sellThroughAggregateForUpcGet')
            ->times(2)
            ->andReturn(collect([]));
    });

    $sellThroughProductUpcServices = new SellThroughProductUpcServices();
    $response = $sellThroughProductUpcServices->printSellThroughDetailsByProductUpc($filterData, $companyId, []);

    expect($response)->toBeString();
});

test(
    'exportSellThroughDetailsByProductUpc method will return the BinaryFileResponse as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $date = Carbon::now()->format('Y-m-d');

        $filterData = [
            'date' => $date,
            'location_ids' => [1],
            'report_type' => SellThroughTypes::BY_UPC->value,
            'filter_by' => null,
        ];

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn(Company::factory()->make([
                'default_country_id' => 1,
            ]));
        });

        $this->mock(CurrencyQueries::class, function ($mock): void {
            $mock->shouldReceive('getByCompanyId')
            ->once()
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
        });

        $this->mock(LocationQueries::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('getLocationNamesWithCodesByIds')
                ->once()
                ->andReturn(Location::factory()->make([
                    'company_id' => $companyId,
                    'type_id' => LocationTypes::STORE->value,
                ]));
        });

        $this->mock(SellThroughAggregateQueries::class, function ($mock): void {
            $mock->shouldReceive('sellThroughAggregateForUpcGet')
        ->once()
        ->andReturn(collect([]));
        });

        $sellThroughProductUpcServices = new SellThroughProductUpcServices();
        $response = $sellThroughProductUpcServices->exportSellThroughDetailsByProductUpc(
            $filterData,
            $companyId,
            'abc.csv',
            []
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test('SellThroughDetailsByProductUpcForChart method will return the array as expected', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $date = Carbon::now()->format('Y-m-d');

    $filterData = [
        'date' => $date,
        'store_id' => 1,
        'report_type' => SellThroughTypes::BY_UPC->value,
        'filter_by' => null,
    ];

    $this->mock(SellThroughAggregateQueries::class, function ($mock): void {
        $mock->shouldReceive('sellThroughAggregateForUpcGet')
            ->once()
            ->andReturn(collect([]));
    });

    $sellThroughProductUpcServices = new SellThroughProductUpcServices();
    $response = $sellThroughProductUpcServices->SellThroughDetailsByProductUpcForChart(
        $filterData,
        $companyId
    );

    expect($response)->toBe([
        'records' => [
            'labels' => [],
            'sell_through' => [],
        ],
        'records_for_bar' => [
            'labels' => [],
            'sell_through' => [],
        ],
    ]);
});
