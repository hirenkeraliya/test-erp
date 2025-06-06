<?php

declare(strict_types=1);

use App\Domains\Denomination\DataObjects\DenominationData;
use App\Domains\Denomination\DenominationQueries;
use App\Http\Controllers\Admin\DenominationController;
use App\Models\Denomination;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the List query method of the denomination queries class and returns proper response', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => '100',
        'sort_by' => null,
        'sort_direction' => 'desc',
        'per_page' => '10',
    ];

    $denominationQueries = $this->mock(DenominationQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $denominationController = new DenominationController($denominationQueries);

    $response = $denominationController->fetchDenominations(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls the add denomination method of denomination queries class', function (): void {
    $denominationData = new DenominationData(100);
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $denominationQueries = $this->mock(DenominationQueries::class, function ($mock) use (
        $denominationData,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($denominationData, $companyId);
    });

    $denominationController = new DenominationController($denominationQueries);
    $redirectResponse = $denominationController->store($denominationData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('The denomination was added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/denominations', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get by id method of the denomination queries class and returns proper response',
    function (): void {
        $companyId = 1;
        setCompanyIdInSession($companyId);

        $requestParameter = [
            'denomination' => 100,
        ];

        $denominationQueries = $this->mock(DenominationQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getById')
                ->once()
                ->with(1, $companyId)
                ->andReturn(new Denomination($requestParameter));
        });

        $denominationController = new DenominationController($denominationQueries);
        $response = $denominationController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
        ->has('denomination', fn (Assert $denomination): Assert => $denomination->where('denomination', 100))
        );
    }
);

test('It calls the update denomination method of denomination queries class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $denominationData = new DenominationData(100);

    $denominationQueries = $this->mock(DenominationQueries::class, function ($mock) use (
        $denominationData,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($denominationData, 1, $companyId);
    });

    $denominationController = new DenominationController($denominationQueries);
    $redirectResponse = $denominationController->update($denominationData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The denomination was updated successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/denominations', $redirectResponse->getTargetUrl());
});

test('It calls the delete method of the denomination queries class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $denominationQueries = $this->mock(DenominationQueries::class, function ($mock) use ($companyId): void {
        $mock->shouldReceive('delete')
            ->once()
            ->with(1, $companyId);
    });

    $denominationController = new DenominationController($denominationQueries);
    $redirectResponse = $denominationController->delete(1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The denomination has been successfully deleted.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/denominations', $redirectResponse->getTargetUrl());
});

test('It calls the exportDenominations method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $denominationQueries = $this->mock(DenominationQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getDenominationsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Denomination()));
    });

    $denominationController = new DenominationController($denominationQueries);

    $response = $denominationController->exportDenominations('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
