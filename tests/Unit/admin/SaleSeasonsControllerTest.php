<?php

declare(strict_types=1);

use App\Domains\SaleSeason\DataObjects\SaleSeasonData;
use App\Domains\SaleSeason\SaleSeasonQueries;
use App\Http\Controllers\Admin\SaleSeasonsController;
use App\Models\SaleSeason;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

test('It calls the list query method of the sale season queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $saleSeasonQueries = $this->mock(SaleSeasonQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $saleSeasonController = new SaleSeasonsController($saleSeasonQueries);

    $response = $saleSeasonController->fetchSaleSeasons(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls addNew method of the sale season queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $saleSeasonData = SaleSeason::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($saleSeasonData['company_id']);

    $saleSeasonRecords = new SaleSeasonData(...$saleSeasonData);

    $saleSeasonQueries = $this->mock(SaleSeasonQueries::class, function ($mock) use (
        $saleSeasonRecords,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($saleSeasonRecords, $companyId);
    });

    $saleSeasonController = new SaleSeasonsController($saleSeasonQueries);
    $redirectResponse = $saleSeasonController->store($saleSeasonRecords);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The sale season has been added successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/sale-seasons', $redirectResponse->getTargetUrl());
});

test('It calls get by id method of the sale season queries class and return proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = SaleSeason::factory()->make([
        'company_id' => $companyId,
    ])->toArray();

    $saleSeasonQueries = $this->mock(SaleSeasonQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new SaleSeason($requestParameter));
    });

    $saleSeasonsController = new SaleSeasonsController($saleSeasonQueries);
    $response = $saleSeasonsController->edit(1);
    $response->rootView('admin.index');

    $newResponse = new TestResponse($response->toResponse(new Request()));

    $newResponse->assertInertia(
        fn (Assert $inertia): Assert => $inertia
            ->has(
                'saleSeason',
                fn (Assert $saleSeason): Assert => $saleSeason
                    ->where('name', $requestParameter['name'])
                    ->where('start_date', $requestParameter['start_date'])
                    ->where('end_date', $requestParameter['end_date'])
                    ->etc()
            )
    );
});

test('It calls update method of the sale season queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $saleSeasonData = SaleSeason::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($saleSeasonData['company_id']);

    $saleSeasonRecords = new SaleSeasonData(...$saleSeasonData);

    $saleSeasonQueries = $this->mock(SaleSeasonQueries::class, function ($mock) use (
        $saleSeasonRecords,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($saleSeasonRecords, 1, $companyId);
    });

    $saleSeasonsController = new SaleSeasonsController($saleSeasonQueries);
    $redirectResponse = $saleSeasonsController->update($saleSeasonRecords, $companyId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The sale season has been updated successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/sale-seasons', $redirectResponse->getTargetUrl());
});

test('It calls delete method of the sale season queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $saleSeasonQueries = $this->mock(SaleSeasonQueries::class, function ($mock) use ($companyId): void {
        $mock->shouldReceive('delete')
            ->once()
            ->with(1, $companyId);
    });

    $saleSeasonsController = new SaleSeasonsController($saleSeasonQueries);
    $redirectResponse = $saleSeasonsController->delete(1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Sale Season deleted successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/sale-seasons', $redirectResponse->getTargetUrl());
});
