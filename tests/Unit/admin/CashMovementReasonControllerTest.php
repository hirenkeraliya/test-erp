<?php

declare(strict_types=1);

use App\Domains\CashMovementReason\CashMovementReasonQueries;
use App\Domains\CashMovementReason\DataObjects\CashMovementReasonData;
use App\Http\Controllers\Admin\CashMovementReasonController;
use App\Models\CashMovementReason;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the List query method of the cash movement reason queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'per_page' => 1,
        ];

        $cashMovementReasonQueries = $this->mock(CashMovementReasonQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $cashMovementReasonController = new CashMovementReasonController($cashMovementReasonQueries);
        $response = $cashMovementReasonController->fetchCashMovementReasons(new Request($requestParameter));
        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the add cash movement reason method of cash movement reason queries class', function (): void {
    $companyId = 1;
    $cashMovementReasonData = new CashMovementReasonData('Cash movement reason 1', 1);
    setCompanyIdInSession($companyId);

    $cashMovementReasonQueries = $this->mock(CashMovementReasonQueries::class, function ($mock) use (
        $cashMovementReasonData,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($cashMovementReasonData, $companyId);
    });

    $cashMovementReasonController = new CashMovementReasonController($cashMovementReasonQueries);
    $redirectResponse = $cashMovementReasonController->store($cashMovementReasonData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Cash movement reason added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/cash-movement-reasons', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get by id method of the cash movement reason queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'company_id' => $companyId,
            'reason' => 'Cash movement reason',
            'type_id' => 3,
        ];

        $cashMovementReasonQueries = $this->mock(CashMovementReasonQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getById')
            ->once()
            ->with(3, $companyId)
            ->andReturn(new CashMovementReason($requestParameter));
        });

        $cashMovementReasonController = new CashMovementReasonController($cashMovementReasonQueries);
        $response = $cashMovementReasonController->edit(3);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has(
            'cashMovementReason',
            fn (Assert $cashMovementReason): Assert => $cashMovementReason->where(
                'reason',
                'Cash movement reason'
            )->where('type_id', 3)->where('company_id', $companyId)
        )
        );
    }
);

test('It calls the update cash movement reason method of cash movement reason queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $cashMovementReasonData = new CashMovementReasonData('Cash movement reason', 1, $companyId);

    $cashMovementReasonQueries = $this->mock(CashMovementReasonQueries::class, function ($mock) use (
        $cashMovementReasonData,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($cashMovementReasonData, 1, $companyId);
    });

    $cashMovementReasonController = new CashMovementReasonController($cashMovementReasonQueries);
    $redirectResponse = $cashMovementReasonController->update($cashMovementReasonData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'Cash movement reason updated successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/cash-movement-reasons', $redirectResponse->getTargetUrl());
});

test('It calls the exportCashMovementReasons method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $cashMovementReasonQueries = $this->mock(CashMovementReasonQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getCashMovementReasonsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new CashMovementReason()));
    });

    $cashMovementReasonController = new CashMovementReasonController($cashMovementReasonQueries);

    $response = $cashMovementReasonController->exportCashMovementReasons(
        'filename.csv',
        new Request($requestParameter)
    );

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
