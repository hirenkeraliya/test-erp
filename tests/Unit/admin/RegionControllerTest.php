<?php

declare(strict_types=1);

use App\Domains\Region\DataObjects\RegionData;
use App\Domains\Region\RegionQueries;
use App\Http\Controllers\Admin\RegionController;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the list query method of the region queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $regionQueries = $this->mock(RegionQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $regionController = new RegionController($regionQueries);

    $response = $regionController->fetchRegions(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls addNew method of the region queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $regionData = Region::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($regionData['company_id']);

    $regionRecords = new RegionData(...$regionData);

    $regionQueries = $this->mock(RegionQueries::class, function ($mock) use ($regionRecords, $companyId): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($regionRecords, $companyId);
    });

    $regionController = new RegionController($regionQueries);
    $redirectResponse = $regionController->store($regionRecords);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('The region has been added successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/regions', $redirectResponse->getTargetUrl());
});

test('It calls update method of the region queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $regionData = Region::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($regionData['company_id']);

    $regionRecords = new RegionData(...$regionData);

    $regionQueries = $this->mock(RegionQueries::class, function ($mock) use ($regionRecords, $companyId): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($regionRecords, 1, $companyId);
    });

    $regionController = new RegionController($regionQueries);
    $redirectResponse = $regionController->update($regionRecords, $companyId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals('The region has been updated successfully.', $redirectResponse->getSession()->all()['success']);
    $this->assertStringContainsString('admin/regions', $redirectResponse->getTargetUrl());
});

test('It calls the exportRegions method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $regionQueries = $this->mock(RegionQueries::class, function ($mock) use ($requestParameter, $companyId): void {
        $mock->shouldReceive('getRegionsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new Region()));
    });

    $regionController = new RegionController($regionQueries);

    $response = $regionController->exportRegions('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test('It calls addNewFromLocation method of the region queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $regionData = Region::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($regionData['company_id']);

    $regionRecords = new RegionData(...$regionData);

    $regionQueries = $this->mock(RegionQueries::class, function ($mock) use ($regionRecords, $companyId): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($regionRecords, $companyId);
    });

    $regionController = new RegionController($regionQueries);
    $redirectResponse = $regionController->addNewFromLocation($regionRecords);

    expect($redirectResponse)->toBeArray();
});
