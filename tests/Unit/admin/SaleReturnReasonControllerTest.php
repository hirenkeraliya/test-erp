<?php

declare(strict_types=1);

use App\Domains\Common\Enums\SaleReturnOrVoidSaleReasonTypes;
use App\Domains\Location\LocationQueries;
use App\Domains\SaleReturnReason\DataObjects\SaleReturnReasonData;
use App\Domains\SaleReturnReason\SaleReturnReasonQueries;
use App\Http\Controllers\Admin\SaleReturnReasonController;
use App\Models\Location;
use App\Models\SaleReturnReason;
use App\Models\SaleReturnReasonType;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the List query method of the sale return reason queries class and returns proper response',
    function (): void {
        $companyId = 1;

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        setCompanyIdInSession($companyId);

        $saleReturnReasonQueries = $this->mock(SaleReturnReasonQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $saleReturnReasonController = new SaleReturnReasonController($saleReturnReasonQueries);

        $response = $saleReturnReasonController->fetchSaleReturnReasons(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']->resource);
    }
);

test('It calls the add sale return reason method of sale return reason queries class', function (): void {
    $companyId = 1;
    $saleReturnReasonData = new SaleReturnReasonData(
        'Sale return reason 1',
        true,
        [SaleReturnOrVoidSaleReasonTypes::POS->value],
        1,
    );
    setCompanyIdInSession($companyId);

    $saleReturnReasonQueries = $this->mock(SaleReturnReasonQueries::class, function ($mock) use (
        $saleReturnReasonData,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($saleReturnReasonData, $companyId);
    });

    $this->mock(LocationQueries::class, function ($mock): void {
        $mock->shouldReceive('doesLocationExist')
            ->once()
            ->andReturn(true);
    });

    $saleReturnReasonController = new SaleReturnReasonController($saleReturnReasonQueries);
    $redirectResponse = $saleReturnReasonController->store($saleReturnReasonData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The sale return reason has been added successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/sale-return-reasons', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get by id method of the sale return reason queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $this->mock(LocationQueries::class, function ($mock): void {
            $mock->shouldReceive('getStoreWithBasicColumns')
                ->once()
                ->andReturn(collect(new Location()));
            $mock->shouldReceive('getWithBasicColumnsOfWarehouse')
                ->once()
                ->andReturn(collect(new Location()));
        });

        $saleReturnReason = SaleReturnReason::factory()->make([
            'id' => 1,
            'company_id' => $companyId,
            'reason' => 'Sale return reason',
            'put_back_in_inventory' => false,
        ]);

        $saleReturnReasonType = SaleReturnReasonType::factory()->make([
            'id' => 1,
            'sale_return_reason_id' => $saleReturnReason->id,
        ]);

        $saleReturnReason->saleReturnReasonTypes = collect([$saleReturnReasonType]);

        $saleReturnReasonQueries = $this->mock(SaleReturnReasonQueries::class, function ($mock) use (
            $saleReturnReason,
            $companyId
        ): void {
            $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn($saleReturnReason);
        });

        $saleReturnReasonController = new SaleReturnReasonController($saleReturnReasonQueries);
        $response = $saleReturnReasonController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has(
            'saleReturnReason',
            fn (Assert $saleReturnReason): Assert => $saleReturnReason
                ->where('reason', 'Sale return reason')
                ->where('put_back_in_inventory', false)
                ->where('company_id', $companyId)
                ->etc()
        )
        );
    }
);

test('It calls the update sale return reason method of sale return reason queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $saleReturnReasonData = new SaleReturnReasonData(
        'Sale return reason',
        true,
        [SaleReturnOrVoidSaleReasonTypes::POS->value],
        null,
    );

    $saleReturnReasonQueries = $this->mock(SaleReturnReasonQueries::class, function ($mock) use (
        $saleReturnReasonData,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($saleReturnReasonData, 1, $companyId);
    });

    $saleReturnReasonController = new SaleReturnReasonController($saleReturnReasonQueries);
    $redirectResponse = $saleReturnReasonController->update($saleReturnReasonData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Sale return reason updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/sale-return-reasons', $redirectResponse->getTargetUrl());
});

test('It calls the exportSaleReturnReasons method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $saleReturnReasonQueries = $this->mock(SaleReturnReasonQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getSaleReturnReasonsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new SaleReturnReason()));
    });

    $saleReturnReasonController = new SaleReturnReasonController($saleReturnReasonQueries);

    $response = $saleReturnReasonController->exportSaleReturnReasons('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
