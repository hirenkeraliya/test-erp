<?php

declare(strict_types=1);

use App\Domains\SaleAchievedTarget\SaleAchievedTargetQueries;
use App\Http\Controllers\StoreManager\SaleTargetReportController;
use App\Models\SaleAchievedTarget;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the getPaginatedSaleTargetAchievedListForStoreManager method of the sale achieved target queries class and returns proper response',
    function (): void {
        $locationId = 1;
        setStoreIdInSession($locationId);
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession();

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'date_range' => [],
            'promoter_ids' => null,
            'target_type' => null,
            'time_interval_type' => null,
            'week' => [],
            'year' => null,
            'month' => [],
        ];

        $saleAchievedTargetQueries = $this->mock(SaleAchievedTargetQueries::class, function ($mock) use (
            $requestParameter,
            $locationId,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedSaleTargetAchievedListForStoreManager')
            ->once()
            ->with($requestParameter, $locationId, $companyId)
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
        $locationId = 1;
        setStoreIdInSession($locationId);
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession();

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'date_range' => [],
            'promoter_ids' => null,
            'target_type' => null,
            'time_interval_type' => null,
            'week' => [],
            'year' => null,
            'month' => [],
        ];

        $saleAchievedTargetQueries = $this->mock(SaleAchievedTargetQueries::class, function ($mock) use (
            $requestParameter,
            $locationId,
            $companyId
        ): void {
            $mock->shouldReceive('getSaleAchievedTargetExportForStoreManager')
            ->once()
            ->with($requestParameter, $locationId, $companyId)
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
