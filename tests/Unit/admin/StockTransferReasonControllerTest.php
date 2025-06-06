<?php

declare(strict_types=1);

use App\Domains\StockTransferReason\DataObjects\StockTransferReasonData;
use App\Domains\StockTransferReason\StockTransferReasonQueries;
use App\Http\Controllers\Admin\StockTransferReasonController;
use App\Models\StockTransferReason;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the List query method of the stock transfer reason queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        $stockTransferReasonQueries = $this->mock(StockTransferReasonQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $stockTransferReasonController = new StockTransferReasonController($stockTransferReasonQueries);

        $response = $stockTransferReasonController->fetchStockTransferReasons(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']);
    }
);

test('It calls the addNew method of stock transfer reason queries class', function (): void {
    $stockTransferReasonData = new StockTransferReasonData('Abcd', 'Efgi');
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $stockTransferReasonQueries = $this->mock(StockTransferReasonQueries::class, function ($mock) use (
        $stockTransferReasonData,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($stockTransferReasonData, $companyId);
    });

    $stockTransferReasonController = new StockTransferReasonController($stockTransferReasonQueries);
    $redirectResponse = $stockTransferReasonController->store($stockTransferReasonData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The stock transfer reason has been added successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/stock-transfer-reason', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get by id method of the stock transfer reason queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'company_id' => $companyId,
            'name' => 'XYZ',
            'code' => 'WEL',
        ];

        $stockTransferReasonQueries = $this->mock(StockTransferReasonQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new StockTransferReason($requestParameter));
        });

        $stockTransferReasonController = new StockTransferReasonController($stockTransferReasonQueries);
        $response = $stockTransferReasonController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has(
            'stockTransferReason',
            fn (Assert $stockTransferReason): Assert => $stockTransferReason->where('name', 'XYZ')->where(
                'code',
                'WEL'
            )->where('company_id', $companyId)
        )
        );
    }
);

test('It calls the update method of stock transfer reason queries class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);
    $stockTransferReasonData = new StockTransferReasonData('Abcd', 'Efgi');

    $stockTransferReasonQueries = $this->mock(StockTransferReasonQueries::class, function ($mock) use (
        $stockTransferReasonData,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($stockTransferReasonData, 1, $companyId);
    });

    $stockTransferReasonController = new StockTransferReasonController($stockTransferReasonQueries);
    $redirectResponse = $stockTransferReasonController->update($stockTransferReasonData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'Stock Transfer Reason updated successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/stock-transfer-reason', $redirectResponse->getTargetUrl());
});

test('It calls the exportStockTransferReasons method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $stockTransferReasonQueries = $this->mock(StockTransferReasonQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getStockTransferReasonsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new StockTransferReason()));
    });

    $stockTransferReasonController = new StockTransferReasonController($stockTransferReasonQueries);

    $response = $stockTransferReasonController->exportStockTransferReasons(
        'filename.csv',
        new Request($requestParameter)
    );

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
