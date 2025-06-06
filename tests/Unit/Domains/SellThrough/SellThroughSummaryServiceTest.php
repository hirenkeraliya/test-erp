<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Location\LocationQueries;
use App\Domains\SellThroughAggregate\SellThroughAggregateQueries;
use App\Domains\SellThroughAggregate\Services\SellThroughSummaryServices;
use App\Models\Company;
use Carbon\Carbon;

test(
    'it calls sellThroughAggregateForSummaryGet method of the SellThroughAggregateQueries class as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $date = Carbon::now();

        $filterData = [
            'date_range' => [$date, $date],
            'date' => null,
            'location_ids' => null,
            'filter_by' => null,
        ];

        $this->mock(CompanyQueries::class, function ($mock): void {
            $mock->shouldReceive('getNameAndCodeById')
                ->once()
                ->andReturn(Company::factory()->make([
                    'default_country_id' => 1,
                ]));
        });

        $this->mock(SellThroughAggregateQueries::class, function ($mock): void {
            $mock->shouldReceive('sellThroughAggregateForSummaryGet')
                ->once()
                ->andReturn(collect([]));
        });

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldNotReceive('getStoreWithBasicColumns');
        });

        $sellThroughSummaryService = new SellThroughSummaryServices();
        $response = $sellThroughSummaryService->printSellThroughDetails($filterData, $companyId, []);

        expect($response)->toBeString();
    }
);
