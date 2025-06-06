<?php

declare(strict_types=1);

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\VoidSaleReason\DataObjects\VoidSaleReasonData;
use App\Domains\VoidSaleReason\VoidSaleReasonQueries;
use App\Http\Controllers\Admin\VoidSaleReasonController;
use App\Models\VoidSaleReason;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the List query method of the void sale reason queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession();

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'per_page' => 1,
        ];

        $voidSaleReasonQueries = $this->mock(VoidSaleReasonQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 20, 15));
        });

        $voidSaleReasonController = new VoidSaleReasonController($voidSaleReasonQueries);

        $response = $voidSaleReasonController->fetchVoidSaleReasons(new Request($requestParameter));

        $this->assertEquals(20, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the add void sale reason method of void sale reason queries class', function (): void {
    $companyId = 1;
    $voidSaleReasonData = new VoidSaleReasonData('Void sale reason 1', [SaleReturnOrVoidSaleReasonTypes::POS->value]);
    setCompanyIdInSession($companyId);

    $voidSaleReasonQueries = $this->mock(VoidSaleReasonQueries::class, function ($mock) use (
        $voidSaleReasonData,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($voidSaleReasonData, $companyId);
    });

    $voidSaleReasonController = new VoidSaleReasonController($voidSaleReasonQueries);
    $redirectResponse = $voidSaleReasonController->store($voidSaleReasonData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Void sale reason added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/void-sale-reasons', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get by id method of the void sale reason queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'reason' => 'Void sale reason',
        ];

        $voidSaleReasonQueries = $this->mock(VoidSaleReasonQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new VoidSaleReason($requestParameter));
        });

        $voidSaleReasonController = new VoidSaleReasonController($voidSaleReasonQueries);
        $response = $voidSaleReasonController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
            ->has(
                'voidSaleReason',
                fn (Assert $saleReturnReason): Assert => $saleReturnReason
                    ->where('reason', 'Void sale reason')
                    ->etc()
            )
        );
    }
);

test('It calls the update void sale reason method of void sale reason queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $voidSaleReasonData = new VoidSaleReasonData('Void sale reason', [SaleReturnOrVoidSaleReasonTypes::POS->value]);

    $voidSaleReasonQueries = $this->mock(VoidSaleReasonQueries::class, function ($mock) use (
        $voidSaleReasonData,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($voidSaleReasonData, 1, $companyId);
    });

    $voidSaleReasonController = new VoidSaleReasonController($voidSaleReasonQueries);
    $redirectResponse = $voidSaleReasonController->update($voidSaleReasonData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Void sale reason updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/void-sale-reasons', $redirectResponse->getTargetUrl());
});

test('It calls the exportVoidSaleReasons method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $voidSaleReasonQueries = $this->mock(VoidSaleReasonQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getVoidSaleReasonsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new VoidSaleReason()));
    });

    $voidSaleReasonController = new VoidSaleReasonController($voidSaleReasonQueries);

    $response = $voidSaleReasonController->exportVoidSaleReasons('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
