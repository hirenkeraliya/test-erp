<?php

declare(strict_types=1);

use App\Domains\PackageType\DataObjects\PackageTypeData;
use App\Domains\PackageType\PackageTypeQueries;
use App\Http\Controllers\Admin\PackageTypeController;
use App\Models\PackageType;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test(
    'It calls the list query method of the package type queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'search_text' => 'test',
            'sort_by' => 'test',
            'sort_direction' => 'test',
            'per_page' => 'test',
        ];

        $packageTypeQueries = $this->mock(PackageTypeQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('listQuery')
                ->once()
                ->with($requestParameter, $companyId)
                ->andReturn(new LengthAwarePaginator([], 50, 15));
        });

        $packageTypeController = new PackageTypeController($packageTypeQueries);

        $response = $packageTypeController->fetchPackageTypes(new Request($requestParameter));

        $this->assertEquals(50, $response['total_records']);
        $this->assertEquals(collect([]), $response['data']);
    }
);

test('It calls the addNew method of package type queries class', function (): void {
    $packageTypeData = new PackageTypeData('XYZ');
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $packageTypeQueries = $this->mock(PackageTypeQueries::class, function ($mock) use (
        $packageTypeData,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($packageTypeData, $companyId);
    });

    $packageTypeController = new PackageTypeController($packageTypeQueries);
    $redirectResponse = $packageTypeController->store($packageTypeData);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The package type has been added successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/package_types', $redirectResponse->getTargetUrl());
});

test(
    'It calls the get by id method of the package type queries class and returns proper response',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);

        $requestParameter = [
            'name' => 'xyz',
        ];

        $packageTypeQueries = $this->mock(PackageTypeQueries::class, function ($mock) use (
            $requestParameter,
            $companyId
        ): void {
            $mock->shouldReceive('getById')
                ->once()
                ->with(1, $companyId)
                ->andReturn(new PackageType($requestParameter));
        });

        $packageTypeController = new PackageTypeController($packageTypeQueries);
        $response = $packageTypeController->edit(1);
        $response->rootView('admin.index');

        $newResponse = new TestResponse($response->toResponse(new Request()));

        $newResponse->assertInertia(
            fn (Assert $inertia): Assert => $inertia
                ->has('packageType', fn (Assert $packageType): Assert => $packageType->where('name', 'xyz'))
        );
    }
);

test('It calls the update method of package type queries class', function (): void {
    $companyId = 1;
    setCompanyIdInSession($companyId);

    $packageTypeData = new PackageTypeData('XYZ');

    $packageTypeQueries = $this->mock(PackageTypeQueries::class, function ($mock) use (
        $packageTypeData,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($packageTypeData, 1, $companyId);
    });

    $packageTypeController = new PackageTypeController($packageTypeQueries);
    $redirectResponse = $packageTypeController->update($packageTypeData, 1);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The package type has been updated successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/package_types', $redirectResponse->getTargetUrl());
});

test('It calls the exportPackageType method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $packageTypeQueries = $this->mock(PackageTypeQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getPackageTypeExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new PackageType()));
    });

    $packageTypeController = new PackageTypeController($packageTypeQueries);

    $response = $packageTypeController->exportPackageType('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());
    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
