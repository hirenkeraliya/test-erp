<?php

declare(strict_types=1);

use App\Domains\Currency\CurrencyQueries;
use App\Domains\SaleTarget\SaleTargetQueries;
use App\Http\Controllers\StoreManager\SaleTargetController;
use App\Models\Currency;
use App\Models\SaleTarget;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the list query method of the sale target queries class and returns proper response',
    function (): void {
        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
            'target_type' => null,
            'time_interval_type' => null,
            'select_status' => null,
            'location_ids' => [1],
            'promoter_ids' => null,
        ];

        $saleTargetQueries = $this->mock(SaleTargetQueries::class, function ($mock) use ($requestParameter): void {
            setStoreIdInSession(1);
            setStoreManagerStoreCompanyIdInSession(1);
            $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, 1)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $saleTargetController = new SaleTargetController($saleTargetQueries);

        $response = $saleTargetController->fetchSaleTargets(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the exportSaleTargets method and returns a proper response', function (): void {
    $companyId = 1;

    setStoreIdInSession($companyId);
    setStoreManagerStoreCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
        'target_type' => null,
        'time_interval_type' => null,
        'select_status' => null,
        'location_ids' => [1],
        'promoter_ids' => null,
    ];

    $saleTargetQueries = $this->mock(SaleTargetQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getSaleTargetExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new SaleTarget()));
    });

    $saleTargetController = new SaleTargetController($saleTargetQueries);

    $response = $saleTargetController->exportSaleTargets('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('it call the fetchSaleTarget method of saleTargetQueries class and return proper message', function (): void {
    setStoreManagerStoreCompanyIdInSession(1);

    $saleTargetQueries = $this->mock(SaleTargetQueries::class, function ($mock): void {
        $mock->shouldReceive('getById')
            ->once()
            ->andReturn(new SaleTarget([
                'amount_type' => 1,
                'target_type' => 1,
                'time_interval_type' => 1,
            ]));
    });

    $this->mock(CurrencyQueries::class, function ($mock): void {
        $mock->shouldReceive('getByCompanyId')
            ->times(1)
            ->andReturn(new Currency([
                'symbol' => 'RS',
            ]));
    });

    $saleTargetController = new SaleTargetController($saleTargetQueries);
    $response = $saleTargetController->fetchSaleTarget(1);
    expect($response)->toHaveKey('sale_target_details');
});
