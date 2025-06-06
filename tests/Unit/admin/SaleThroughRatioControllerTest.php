<?php

declare(strict_types=1);

use App\Domains\SaleThroughRatio\DataObjects\SaleThroughRatioData;
use App\Domains\SaleThroughRatio\SaleThroughRatioQueries;
use App\Http\Controllers\Admin\SaleThroughRatioController;
use App\Models\SaleThroughRatio;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the List query method of the SaleThroughRatioQueries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'abc',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
            'per_page' => 1,
        ];

        $saleThroughRatioQueries = $this->mock(SaleThroughRatioQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 20, 15));
        });

        $saleThroughRatioController = new SaleThroughRatioController($saleThroughRatioQueries);

        $response = $saleThroughRatioController->fetchsaleThroughRatios(new Request($requestParameter));

        $this->assertEquals(20, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']);
    }
);

test(
    'It calls the addNew method of the SaleThroughRatioQueries class and returns proper response',
    function (): void {
        $saleThroughRatioRecord = [
            'name' => 'test',
            'percentage' => 10,
            'description' => 'hello',
        ];

        setCompanyIdInSession();

        $saleThroughRatioData = new SaleThroughRatioData(...$saleThroughRatioRecord);

        $saleThroughRatioQueries = $this->mock(SaleThroughRatioQueries::class, function ($mock) use (
            $saleThroughRatioData
        ): void {
            $mock->shouldReceive('addNew')
                ->once()
                ->with($saleThroughRatioData, 1);
        });

        $saleThroughRatioController = new SaleThroughRatioController($saleThroughRatioQueries);
        $redirectResponse = $saleThroughRatioController->store($saleThroughRatioData);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals(
            'Sale Through Ratio added successfully.',
            $redirectResponse->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/sale-through-ratios', $redirectResponse->getTargetUrl());
    }
);

test(
    'It calls the get by id method of the SaleThroughRatioQueries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $saleThroughRatioRecord = [
            'name' => 'test',
        ];

        $saleThroughRatioQueries = $this->mock(SaleThroughRatioQueries::class, function ($mock) use (
            $saleThroughRatioRecord,
            $companyId
        ): void {
            $mock->shouldReceive('getById')
                ->once()
                ->with(1, $companyId)
                ->andReturn(new SaleThroughRatio($saleThroughRatioRecord));
        });

        $saleThroughRatioController = new SaleThroughRatioController($saleThroughRatioQueries);
        $response = $saleThroughRatioController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has('saleThroughRatio', fn (Assert $saleThroughRatio): Assert => $saleThroughRatio->where('name', 'test'))
        );
    }
);

test(
    'It calls the update method of the SaleThroughRatioQueries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession();

        $saleThroughRatioRecord = [
            'name' => 'test',
            'percentage' => 10,
            'description' => 'hello',
        ];

        $saleThroughRatioData = new SaleThroughRatioData(...$saleThroughRatioRecord);

        $saleThroughRatioQueries = $this->mock(SaleThroughRatioQueries::class, function ($mock) use (
            $saleThroughRatioData,
            $companyId
        ): void {
            $mock->shouldReceive('update')
                ->once()
                ->with($saleThroughRatioData, 1, $companyId);
        });

        $saleThroughRatioController = new SaleThroughRatioController($saleThroughRatioQueries);
        $redirectResponse = $saleThroughRatioController->update($saleThroughRatioData, 1);
        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals(
            'Sale Through Ratio has been updated successfully.',
            $redirectResponse->getSession()->all()['success']
        );
        $this->assertStringContainsString('admin/sale-through-ratios', $redirectResponse->getTargetUrl());
    }
);

test('It calls the exportSaleThroughRatios method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $saleThroughRatioQueries = $this->mock(SaleThroughRatioQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getsaleThroughRatiosExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new SaleThroughRatio()));
    });

    $saleThroughRatioController = new SaleThroughRatioController($saleThroughRatioQueries);

    $response = $saleThroughRatioController->exportSaleThroughRatios(
        'filename.csv',
        new Request($requestParameter)
    );

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
