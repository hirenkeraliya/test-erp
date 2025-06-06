<?php

declare(strict_types=1);

use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Domains\UnitOfMeasureDerivative\DataObjects\UnitOfMeasureDerivativeData;
use App\Domains\UnitOfMeasureDerivative\UnitOfMeasureDerivativeQueries;
use App\Http\Controllers\Admin\UnitOfMeasureDerivativeController;
use App\Models\UnitOfMeasure;
use App\Models\UnitOfMeasureDerivative;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the list query method of the unit of measure derivatives queries class and returns proper response',
    function (): void {
        $companyId = 1;

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        setCompanyIdInSession($companyId);

        $unitOfMeasureDerivativeQueries = $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, 1, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $unitOfMeasureDerivativeController = new UnitOfMeasureDerivativeController($unitOfMeasureDerivativeQueries);

        $response = $unitOfMeasureDerivativeController->fetchDerivatives(new Request($requestParameter), 1);

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']);
    }
);

test('It calls the addNew method of derivative queries class', function (): void {
    $derivateData = new UnitOfMeasureDerivativeData('XYZ', 20.50);
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'name' => 'xyz',
    ];

    $unitOfMeasureQueries = $this->mock(UnitOfMeasureQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new UnitOfMeasure($requestParameter));
    });

    $unitOfMeasureDerivativeQueries = $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use (
        $derivateData,
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($derivateData, 1);
    });

    $unitOfMeasureDerivativeController = new UnitOfMeasureDerivativeController($unitOfMeasureDerivativeQueries);
    $redirectResponse = $unitOfMeasureDerivativeController->store($derivateData, 1, $unitOfMeasureQueries);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Derivative added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString(
        'admin/unit-of-measures/' . 1 . '/derivatives',
        $redirectResponse->getTargetUrl()
    );
});

test(
    'It calls the get by id method of the derivative queries class and returns proper response',
    function (): void {
        $companyId = 1;
        $unitOfMeasure = UnitOfMeasure::factory()->make([
            'company_id' => $companyId,
        ]);
        $unitOfMeasureDerivate = UnitOfMeasureDerivative::factory()->make([
            'unit_of_measure_id' => 1,
            'name' => 'new derivate',
            'ratio' => 20.10,
        ]);

        setCompanyIdInSession($companyId);

        $unitOfMeasureDerivativeQueries = $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use (
            $unitOfMeasureDerivate
        ): void {
            $mock->shouldReceive('getById')
                ->once()
                ->with($unitOfMeasureDerivate->unit_of_measure_id, 1)
                ->andReturn($unitOfMeasureDerivate);
        });

        $unitOfMeasureQueries = $this->mock(UnitOfMeasureQueries::class, function ($mock) use (
            $unitOfMeasureDerivate,
            $unitOfMeasure
        ): void {
            $mock->shouldReceive('getById')
                ->once()
                ->with($unitOfMeasureDerivate->unit_of_measure_id, 1)
                ->andReturn($unitOfMeasure);
        });

        $unitOfMeasureDerivativeController = new UnitOfMeasureDerivativeController($unitOfMeasureDerivativeQueries);
        $response = $unitOfMeasureDerivativeController->edit(
            $unitOfMeasureDerivate->unit_of_measure_id,
            1,
            $unitOfMeasureQueries
        );
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
                ->has(
                    'derivative',
                    fn (Assert $derivate): Assert => $derivate
                        ->where('name', 'new derivate')
                        ->where('ratio', 20.10)
                        ->etc()
                )
        );
    }
);

test('It calls the update method of derivative queries class', function (): void {
    $derivateData = new UnitOfMeasureDerivativeData('best', 50.50);
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'name' => 'xyz',
    ];

    UnitOfMeasure::factory()->make([
        'company_id' => $companyId,
    ]);

    $unitOfMeasureDerivate = UnitOfMeasureDerivative::factory()->make([
        'unit_of_measure_id' => 1,
    ]);

    $unitOfMeasureQueries = $this->mock(UnitOfMeasureQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getById')
            ->once()
            ->with(1, $companyId)
            ->andReturn(new UnitOfMeasure($requestParameter));
    });

    $unitOfMeasureDerivativeQueries = $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use (
        $derivateData,
        $unitOfMeasureDerivate
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($derivateData, $unitOfMeasureDerivate['unit_of_measure_id'], 1);
    });

    $unitOfMeasureController = new UnitOfMeasureDerivativeController($unitOfMeasureDerivativeQueries);
    $redirectResponse = $unitOfMeasureController->update(
        $derivateData,
        $unitOfMeasureDerivate['unit_of_measure_id'],
        1,
        $unitOfMeasureQueries
    );

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Derivative updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString(
        'admin/unit-of-measures/' . $unitOfMeasureDerivate['unit_of_measure_id'] . '/derivatives',
        $redirectResponse->getTargetUrl()
    );
});

test('It calls the exportDerivatives method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $unitOfMeasureDerivativeQueries = $this->mock(UnitOfMeasureDerivativeQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getDerivativesExport')
            ->once()
            ->with($requestParameter, 1, $companyId)
            ->andReturn(collect(new UnitOfMeasureDerivative()));
    });

    $unitOfMeasureDerivativeController = new UnitOfMeasureDerivativeController($unitOfMeasureDerivativeQueries);

    $response = $unitOfMeasureDerivativeController->exportDerivatives(
        new Request($requestParameter),
        1,
        'filename.csv'
    );

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
