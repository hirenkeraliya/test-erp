<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use App\Domains\SellThroughAggregate\Services\SellThroughBySizeServices;
use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'it calls sellThroughAggregateForSizePaginate and sellThroughAggregateForSizeGet method of the SellThroughAggregateQueries class as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $date = Carbon::now();

        $filterData = [
            'date' => $date,
            'store_id' => null,
            'per_page' => null,
            'report_type' => SellThroughTypes::SIZES->value,
            'filter_by' => null,
        ];

        $this->mock(SellThroughAggregateQueries::class, function ($mock): void {
            $mock->shouldReceive('sellThroughAggregateForSizePaginate')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 20, 15));
            $mock->shouldReceive('sellThroughAggregateForSizeGet')
            ->once()
            ->andReturn(collect([]));
        });

        $sellThroughBySizeService = new SellThroughBySizeServices();
        $response = $sellThroughBySizeService->fetchSellThroughDetailsBySize($filterData, $companyId);
        $this->assertInstanceOf(LengthAwarePaginator::class, $response['data']->resource);
        $this->assertEquals(20, $response['total_records']);
    }
);

test('printSellThroughDetailsBySize method will return the string as expected', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $date = Carbon::now()->format('Y-m-d');

    $filterData = [
        'date' => $date,
        'location_ids' => [1],
        'report_type' => SellThroughTypes::SIZES->value,
        'filter_by' => null,
    ];

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getNameAndCodeById')
            ->once()
            ->andReturn(Company::factory()->make([
                'default_country_id' => 1,
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
        $mock->shouldReceive('sellThroughAggregateForSizeGet')
            ->times(2)
            ->andReturn(collect([]));
    });

    $sellThroughBySizeService = new SellThroughBySizeServices();
    $response = $sellThroughBySizeService->printSellThroughDetailsBySize($filterData, $companyId, []);

    expect($response)->toBeString();
});

test('sellThroughDetailsBySizeForChart method will return the array as expected', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $date = Carbon::now()->format('Y-m-d');

    $filterData = [
        'date' => $date,
        'store_id' => 1,
        'report_type' => SellThroughTypes::SIZES->value,
        'filter_by' => null,
    ];

    $this->mock(SellThroughAggregateQueries::class, function ($mock): void {
        $mock->shouldReceive('sellThroughAggregateForSizeGet')
        ->once()
        ->andReturn(collect([]));
    });

    $sellThroughBySizeService = new SellThroughBySizeServices();
    $response = $sellThroughBySizeService->sellThroughDetailsBySizeForChart($filterData, $companyId);

    expect($response)->toBe([
        'records' => [
            'labels' => [],
            'sell_through' => [],
        ],
    ]);
});

test('exportSellThroughDetailsBySize method will return the BinaryFileResponse as expected', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $date = Carbon::now()->format('Y-m-d');

    $filterData = [
        'date' => $date,
        'location_ids' => [1],
        'report_type' => SellThroughTypes::SIZES->value,
        'filter_by' => null,
    ];

    $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getNameAndCodeById')
        ->once()
        ->andReturn(Company::factory()->make([
            'default_country_id' => 1,
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
        $mock->shouldReceive('sellThroughAggregateForSizeGet')
        ->once()
        ->andReturn(collect([]));
    });

    $sellThroughBySizeService = new SellThroughBySizeServices();
    $response = $sellThroughBySizeService->exportSellThroughDetailsBySize($filterData, $companyId, 'abc.csv', []);

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
}
);
