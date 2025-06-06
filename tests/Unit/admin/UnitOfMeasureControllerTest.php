<?php

declare(strict_types=1);

use App\Domains\UnitOfMeasure\DataObjects\UnitOfMeasureData;
use App\Domains\UnitOfMeasure\UnitOfMeasureQueries;
use App\Http\Controllers\Admin\UnitOfMeasureController;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the list query method of the unit of measure queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        $unitOfMeasureQueries = $this->mock(UnitOfMeasureQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $unitOfMeasureController = new UnitOfMeasureController($unitOfMeasureQueries);

        $response = $unitOfMeasureController->fetchUnitOfMeasures(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']);
    }
);

test('It calls the addNew method of unit of measure queries class', function (): void {
    $unitOfMeasureData = new UnitOfMeasureData('XYZ', true);
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $unitOfMeasureQueries = $this->mock(UnitOfMeasureQueries::class, function ($mock) use (
        $unitOfMeasureData,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($unitOfMeasureData, $companyId);
    });

    $unitOfMeasureController = new UnitOfMeasureController($unitOfMeasureQueries);
    $redirectResponse = $unitOfMeasureController->store($unitOfMeasureData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The Unit of Measure has been added successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/unit-of-measures', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get by id method of the unit of measure queries class and returns proper response',
    function (): void {
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

        $unitOfMeasureController = new UnitOfMeasureController($unitOfMeasureQueries);
        $response = $unitOfMeasureController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
                ->has('unitOfMeasure', fn (Assert $unitOfMeasure): Assert => $unitOfMeasure->where('name', 'xyz'))
        );
    }
);

test('It calls the update method of unit of measure queries class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $unitOfMeasuresData = new UnitOfMeasureData('XYZ', false);

    $unitOfMeasureQueries = $this->mock(UnitOfMeasureQueries::class, function ($mock) use (
        $unitOfMeasuresData,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($unitOfMeasuresData, 1, $companyId);
    });

    $unitOfMeasureController = new UnitOfMeasureController($unitOfMeasureQueries);
    $redirectResponse = $unitOfMeasureController->update($unitOfMeasuresData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('Unit of Measure updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/unit-of-measures', $redirectResponse->getTargetUrl());
});

test('It calls the exportUnitOfMeasures method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $unitOfMeasureQueries = $this->mock(UnitOfMeasureQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getUnitOfMeasuresExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new UnitOfMeasure()));
    });

    $unitOfMeasureController = new UnitOfMeasureController($unitOfMeasureQueries);

    $response = $unitOfMeasureController->exportUnitOfMeasures('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the delete method of the unit of measure queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $unitOfMeasureId = 1;

        $unitOfMeasureQueries = $this->mock(UnitOfMeasureQueries::class, function ($mock): void {
            $mock->shouldReceive('delete')
                ->once();
        });

        $unitOfMeasureController = new UnitOfMeasureController($unitOfMeasureQueries);

        $redirectResponse = $unitOfMeasureController->delete($unitOfMeasureId);

        $this->assertEquals(302, $redirectResponse->getStatusCode());
        $this->assertEquals('UOM deleted successfully.', $redirectResponse->getSession()->all()['success']);
        $this->assertStringContainsString('admin/unit-of-measures', $redirectResponse->getTargetUrl());
    }
);
