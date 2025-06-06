<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\Enums\LocationTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\SellThroughAggregate\Enums\SellThroughFilterTypes;
use App\Domains\SellThroughAggregate\Enums\SellThroughTypes;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use App\Domains\SellThroughAggregate\Services\SellThroughByColorServices;
use App\Models\Company;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'it calls sellThroughAggregateForColorPaginate and sellThroughAggregateForColorGet method of the SellThroughAggregateQueries class as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $date = Carbon::now();

        $filterData = [
            'date_range' => [$date, $date],
            'store_id' => null,
            'per_page' => null,
            'report_type' => SellThroughTypes::COLORS->value,
            'filter_by' => null,
            'include_by' => [1],
            'date' => $date,
            'sort_by' => null,
            'sort_direction' => null,
            'search_text' => null,
        ];

        $this->mock(SellThroughAggregateQueries::class, function ($mock): void {
            $mock->shouldReceive('sellThroughAggregateForColorPaginate')
                ->once()
                ->andReturn(new LengthAwarePaginator([], 20, 15));
            $mock->shouldReceive('sellThroughAggregateForColorGet')
                ->once()
                ->andReturn(collect([]));
        });

        $sellThroughByColorService = resolve(SellThroughByColorServices::class);
        $response = $sellThroughByColorService->fetchSellThroughDetailsByColor($filterData, $companyId);

        $this->assertInstanceOf(LengthAwarePaginator::class, $response['data']->resource);
        $this->assertEquals(20, $response['total_records']);
    }
);

test('printSellThroughDetailsByColor method will return the string as expected', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $date = Carbon::now()->format('Y-m-d');

    $filterData = [
        'location_ids' => [1],
        'report_type' => SellThroughTypes::COLORS->value,
        'filter_by' => null,
        'include_by' => [1],
        'date' => $date,
        'sort_by' => null,
        'sort_direction' => null,
        'search_text' => null,
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
        $mock->shouldReceive('sellThroughAggregateForColorGet')
            ->times(2)
            ->andReturn(collect([]));
    });

    $sellThroughByColorService = resolve(SellThroughByColorServices::class);
    $response = $sellThroughByColorService->printSellThroughDetailsByColor($filterData, $companyId, []);

    expect($response)->toBeString();
});

test('exportSellThroughDetailsByColor method will return the BinaryFileResponse as expected', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $date = Carbon::now()->format('Y-m-d');

    $filterData = [
        'date_range' => [$date, $date],
        'location_ids' => [1],
        'report_type' => SellThroughTypes::COLORS->value,
        'filter_by' => SellThroughFilterTypes::ALL->value,
        'include_by' => [1],
        'date' => $date,
        'sort_by' => null,
        'sort_direction' => null,
        'search_text' => null,
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
        $mock->shouldReceive('sellThroughAggregateForColorGet')
        ->once()
        ->andReturn(collect([]));
    });

    $sellThroughByColorService = resolve(SellThroughByColorServices::class);
    $response = $sellThroughByColorService->exportSellThroughDetailsByColor($filterData, $companyId, 'abc.csv', []);

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
}
);

test('sellThroughDetailsByColorForChart method will return the array as expected', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $date = Carbon::now()->format('Y-m-d');

    $filterData = [
        'store_id' => 1,
        'report_type' => SellThroughTypes::COLORS->value,
        'filter_by' => null,
        'include_by' => [1],
        'date' => $date,
        'sort_by' => null,
        'sort_direction' => null,
        'search_text' => null,
    ];

    $this->mock(SellThroughAggregateQueries::class, function ($mock): void {
        $mock->shouldReceive('sellThroughAggregateForColorGet')
            ->once()
            ->andReturn(collect([]));
    });

    $sellThroughByColorService = resolve(SellThroughByColorServices::class);
    $response = $sellThroughByColorService->sellThroughDetailsByColorForChart($filterData, $companyId);

    expect($response)->toBe([
        'records' => [
            'labels' => [],
            'sell_through' => [],
        ],
        'colors' => [],
        'records_for_bar' => [
            'labels' => [],
            'sell_through' => [],
        ],
    ]);
});
