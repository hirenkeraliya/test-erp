<?php

declare(strict_types=1);

use App\Domains\SizeGroup\DataObjects\SizeGroupData;
use App\Domains\SizeGroup\SizeGroupQueries;
use App\Http\Controllers\Admin\SizeGroupController;
use App\Models\SizeGroup;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the list query method of the size group queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $sizeGroupQueries = $this->mock(SizeGroupQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $sizeGroupController = new SizeGroupController($sizeGroupQueries);

    $response = $sizeGroupController->fetchSizeGroups(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls addNew method of the size group queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $sizeGroupData = SizeGroup::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($sizeGroupData['company_id']);

    $sizeGroupRecords = new SizeGroupData(...$sizeGroupData);

    $sizeGroupQueries = $this->mock(SizeGroupQueries::class, function ($mock) use (
        $sizeGroupRecords,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($sizeGroupRecords, $companyId);
    });

    $sizeGroupController = new SizeGroupController($sizeGroupQueries);
    $redirectResponse = $sizeGroupController->store($sizeGroupRecords);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The size group has been added successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/size-groups', $redirectResponse->getTargetUrl());
});

test('It calls update method of the size group queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $sizeGroupData = SizeGroup::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($sizeGroupData['company_id']);

    $sizeGroupRecords = new SizeGroupData(...$sizeGroupData);

    $sizeGroupQueries = $this->mock(SizeGroupQueries::class, function ($mock) use (
        $sizeGroupRecords,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($sizeGroupRecords, 1, $companyId);
    });

    $sizeGroupController = new SizeGroupController($sizeGroupQueries);
    $redirectResponse = $sizeGroupController->update($sizeGroupRecords, $companyId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The size group has been updated successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/size-groups', $redirectResponse->getTargetUrl());
});

test('It calls the exportSizeGroups method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $sizeGroupQueries = $this->mock(SizeGroupQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getSizeGroupsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new SizeGroup()));
    });

    $sizeGroupController = new SizeGroupController($sizeGroupQueries);

    $response = $sizeGroupController->exportSizeGroups('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});
