<?php

declare(strict_types=1);

use App\Domains\Company\CompanyQueries;
use App\Domains\Promoter\PromoterQueries;
use App\Domains\PromoterCommission\PromoterCommissionQueries;
use App\Domains\PromoterCommissionUpdate\PromoterCommissionUpdateQueries;
use App\Http\Controllers\StoreManager\PromoterCommissionController;
use App\Models\Company;
use App\Models\PromoterCommission;
use App\Models\PromoterCommissionUpdate;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the getPaginatedCommissionByPromotersForMonth method of the PromoterCommissionQueries class and returns proper response',
    function (): void {
        $companyId = 1;
        $locationId = 1;
        setStoreIdInSession($locationId);

        setStoreManagerStoreCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'month_range' => null,
            'promoter_ids' => [],
            'location_ids' => [$locationId],
            'brand_ids' => [],
            'department_ids' => [],
            'group_ids' => [],
        ];

        $promoterCommissionQueries = $this->mock(PromoterCommissionQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getPaginatedCommissionByPromotersForMonth')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn([new LengthAwarePaginator([], 50, 15), 0, 0]);
        });

        $promoterQueries = $this->mock(PromoterQueries::class);
        $companyQueries = $this->mock(CompanyQueries::class);

        $salesByPromoterController = new PromoterCommissionController(
            $promoterCommissionQueries,
            $promoterQueries,
            $companyQueries
        );

        $response = $salesByPromoterController->fetCommissionsByPromoters(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the exportCommissionByPromoters method and returns a proper response', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $locationId = 1;
    setStoreIdInSession($locationId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'month_range' => null,
        'promoter_ids' => [],
        'location_ids' => [$locationId],
        'brand_ids' => [],
        'department_ids' => [],
        'group_ids' => [],
        'export_columns' => [],
    ];

    $promoterCommissionQueries = $this->mock(PromoterCommissionQueries::class, function ($mock) use (
        $requestParameter
    ): void {
        $mock->shouldReceive('getPaginatedCommissionByPromotersForMonthForExport')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(collect(new PromoterCommission()));
    });

    $promoterQueries = $this->mock(PromoterQueries::class);
    $companyQueries = $this->mock(CompanyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByIdWithPromoterCommissionDetails')
            ->once()
            ->with(1)
            ->andReturn(new Company());
    });

    $salesByPromoterController = new PromoterCommissionController(
        $promoterCommissionQueries,
        $promoterQueries,
        $companyQueries
    );

    $response = $salesByPromoterController->exportCommissionByPromoters('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the fetchPromoterCommissionDetails method of the PromoterCommissionUpdateQueries class and returns proper response',
    function (): void {
        $companyId = 1;
        setStoreManagerStoreCompanyIdInSession($companyId);

        $locationId = 1;
        setStoreIdInSession($locationId);

        $promoterCommissionId = 1;

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'promoter_ids' => [],
            'location_ids' => [$locationId],
            'brand_ids' => [],
            'department_ids' => [],
        ];

        $promoterCommissionQueries = $this->mock(PromoterCommissionQueries::class);
        $promoterQueries = $this->mock(PromoterQueries::class);
        $companyQueries = $this->mock(CompanyQueries::class);

        $this->mock(PromoterCommissionUpdateQueries::class, function ($mock) use (
            $requestParameter,
            $promoterCommissionId
        ): void {
            $mock->shouldReceive('getPaginatedCommissionDetailsByPromoter')
            ->once()
            ->with($requestParameter, $promoterCommissionId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $salesByPromoterController = new PromoterCommissionController(
            $promoterCommissionQueries,
            $promoterQueries,
            $companyQueries
        );

        $response = $salesByPromoterController->fetchPromoterCommissionDetails(
            new Request($requestParameter),
            $promoterCommissionId
        );

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the exportPromoterCommissionDetails method and returns a proper response', function (): void {
    $companyId = 1;

    setStoreManagerStoreCompanyIdInSession($companyId);

    $promoterCommissionId = 1;

    $locationId = 1;
    setStoreIdInSession($locationId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'location_ids' => [$locationId],
        'brand_ids' => [],
        'department_ids' => [],
    ];

    $this->mock(PromoterCommissionUpdateQueries::class, function ($mock) use (
        $requestParameter,
        $promoterCommissionId
    ): void {
        $mock->shouldReceive('getPromoterCommissionDetailsForExport')
        ->once()
        ->with($requestParameter, $promoterCommissionId)
        ->andReturn(collect(new PromoterCommissionUpdate()));
    });

    $promoterQueries = $this->mock(PromoterQueries::class);
    $promoterCommissionQueries = $this->mock(PromoterCommissionQueries::class);
    $companyQueries = $this->mock(CompanyQueries::class);

    $salesByPromoterController = new PromoterCommissionController(
        $promoterCommissionQueries,
        $promoterQueries,
        $companyQueries
    );

    $response = $salesByPromoterController->exportPromoterCommissionDetails(
        $promoterCommissionId,
        'filename.csv',
        new Request($requestParameter)
    );

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
