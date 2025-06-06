<?php

declare(strict_types=1);

use App\Domains\SaleAchievedTarget\SaleAchievedTargetQueries;
use App\Http\Controllers\Admin\SaleTargetReportController;
use App\Models\SaleAchievedTarget;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the getPaginatedSaleTargetAchievedList method of the sale achieved target queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'date_range' => [],
            'promoter_ids' => 'null',
            'location_ids' => 'null',
            'target_type' => null,
            'time_interval_type' => null,
            'week' => [],
            'year' => null,
            'month' => [],
        ];

        $saleAchievedTargetQueries = $this->mock(SaleAchievedTargetQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedSaleTargetAchievedList')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $saleTargetReportController = new SaleTargetReportController($saleAchievedTargetQueries);

        $response = $saleTargetReportController->fetchSaleAchievedTargets(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test(
    'It calls the exportSaleAchievedTarget method of the sale achieved target queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'date_range' => [],
            'promoter_ids' => 'null',
            'location_ids' => 'null',
            'target_type' => null,
            'time_interval_type' => null,
            'week' => [],
            'year' => null,
            'month' => [],
        ];

        $saleAchievedTargetQueries = $this->mock(SaleAchievedTargetQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getSaleAchievedTargetForExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new SaleAchievedTarget()));
        });

        $saleTargetReportController = new SaleTargetReportController($saleAchievedTargetQueries);

        $response = $saleTargetReportController->exportSaleAchievedTarget(
            'filename.csv',
            new Request($requestParameter)
        );

        $this->assertEquals(200, $response->getStatusCode());

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);
    }
);
