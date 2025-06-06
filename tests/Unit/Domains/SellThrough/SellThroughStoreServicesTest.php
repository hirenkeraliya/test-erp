<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use App\Domains\SellThroughAggregate\Services\SellThroughLocationServices;
use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'it calls sellThroughAggregateForLocationPaginate and sellThroughAggregateForLocationGet method of the StoreQueries class as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $date = Carbon::now();

        $filterData = [
            'date' => $date,
            'location_id' => null,
            'per_page' => null,
            'report_type' => SellThroughTypes::LOCATIONS->value,
            'filter_by' => null,
        ];

        $this->mock(SellThroughAggregateQueries::class, function ($mock): void {
            $mock->shouldReceive('sellThroughAggregateForLocationPaginate')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 20, 15));
            $mock->shouldReceive('sellThroughAggregateForLocationGet')
                ->once()
                ->andReturn(collect([]));
        });

        $sellThroughStoreServices = new SellThroughLocationServices();
        $response = $sellThroughStoreServices->fetchSellThroughDetailsByStore($filterData, $companyId);

        $this->assertInstanceOf(LengthAwarePaginator::class, $response['data']->resource);
        $this->assertEquals(20, $response['total_records']);
    }
);

test('printSellThroughDetailsByStore method will return the string as expected', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $date = Carbon::now()->format('Y-m-d');

    $filterData = [
        'date' => $date,
        'location_ids' => [1],
        'report_type' => SellThroughTypes::LOCATIONS->value,
        'filter_by' => null,
    ];

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn(Company::factory()->make([
                'default_country_id' => 1,
            ]));
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('getLocationNamesWithCodesByIds')
            ->once()
            ->andReturn(Location::factory()->make([
                'company_id' => 1,
                'type_id' => LocationTypes::STORE->value,
            ]));
    });

    $this->mock(SellThroughAggregateQueries::class, function ($mock): void {
        $mock->shouldReceive('sellThroughAggregateForLocationGet')
            ->times(2)
            ->andReturn(collect([]));
    });

    $sellThroughStoreServices = new SellThroughLocationServices();
    $response = $sellThroughStoreServices->printSellThroughDetailsByStore($filterData, $companyId, []);

    expect($response)->toBeString();
});

test(
    'exportSellThroughDetailsByStore method will return the BinaryFileResponse as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $date = Carbon::now()->format('Y-m-d');

        $filterData = [
            'date' => $date,
            'location_ids' => [1],
            'report_type' => SellThroughTypes::LOCATIONS->value,
            'filter_by' => null,
        ];

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getNameAndCodeById')
                ->once()
                ->andReturn(Company::factory()->make([
                    'default_country_id' => 1,
                ]));
        });

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getLocationNamesWithCodesByIds')
                ->once()
                ->andReturn(Location::factory()->make([
                    'company_id' => 1,
                    'type_id' => LocationTypes::STORE->value,
                ]));
        });

        $this->mock(SellThroughAggregateQueries::class, function ($mock): void {
            $mock->shouldReceive('sellThroughAggregateForLocationGet')
                ->once()
                ->andReturn(collect([]));
        });

        $sellThroughStoreServices = new SellThroughLocationServices();
        $response = $sellThroughStoreServices->exportSellThroughDetailsByStore(
            $filterData,
            $companyId,
            'abc.csv',
            []
        );

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);

test('SellThroughDetailsByStoreForChart method will return the array as expected', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $date = Carbon::now()->format('Y-m-d');

    $filterData = [
        'date' => $date,
        'store_id' => 1,
        'report_type' => SellThroughTypes::LOCATIONS->value,
        'filter_by' => null,
    ];

    $this->mock(SellThroughAggregateQueries::class, function ($mock): void {
        $mock->shouldReceive('sellThroughAggregateForLocationGet')
            ->once()
            ->andReturn(collect([]));
    });

    $sellThroughStoreServices = new SellThroughLocationServices();
    $response = $sellThroughStoreServices->SellThroughDetailsByStoreForChart(
        $filterData,
        $companyId
    );

    expect($response)->toBe([
        'records' => [
            'labels' => [],
            'sell_through' => [],
        ],
    ]);
});
