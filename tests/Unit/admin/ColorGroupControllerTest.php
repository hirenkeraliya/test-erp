<?php

declare(strict_types=1);

use App\Domains\ColorGroup\ColorGroupQueries;
use App\Domains\ColorGroup\DataObjects\ColorGroupData;
use App\Http\Controllers\Admin\ColorGroupController;
use App\Models\ColorGroup;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('It calls the list query method of the color group queries class and returns proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $colorGroupQueries = $this->mock(ColorGroupQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('listQuery')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(new LengthAwarePaginator([], 50, 15));
    });

    $colorGroupController = new ColorGroupController($colorGroupQueries);

    $response = $colorGroupController->fetchColorGroups(new Request($requestParameter));

    $this->assertEquals(50, $response['total_records']);
    $this->assertEquals(collect([]), $response['data']);
});

test('It calls addNew method of the color group queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $colorGroupData = ColorGroup::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($colorGroupData['company_id']);

    $colorGroupRecords = new ColorGroupData(...$colorGroupData);

    $colorGroupQueries = $this->mock(ColorGroupQueries::class, function ($mock) use (
        $colorGroupRecords,
        $companyId
    ): void {
        $mock->shouldReceive('addNew')
            ->once()
            ->with($colorGroupRecords, $companyId);
    });

    $colorGroupController = new ColorGroupController($colorGroupQueries);
    $redirectResponse = $colorGroupController->store($colorGroupRecords);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The color group has been added successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/color-groups', $redirectResponse->getTargetUrl());
});

test('It calls update method of the color group queries class', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $colorGroupData = ColorGroup::factory()->make([
        'company_id' => $companyId,
    ])->toArray();
    unset($colorGroupData['company_id']);

    $colorGroupRecords = new ColorGroupData(...$colorGroupData);

    $colorGroupQueries = $this->mock(ColorGroupQueries::class, function ($mock) use (
        $colorGroupRecords,
        $companyId
    ): void {
        $mock->shouldReceive('update')
            ->once()
            ->with($colorGroupRecords, 1, $companyId);
    });

    $colorGroupController = new ColorGroupController($colorGroupQueries);
    $redirectResponse = $colorGroupController->update($colorGroupRecords, $companyId);

    $this->assertEquals(302, $redirectResponse->getStatusCode());
    $this->assertEquals(
        'The color group has been updated successfully.',
        $redirectResponse->getSession()->all()['success']
    );
    $this->assertStringContainsString('admin/color-groups', $redirectResponse->getTargetUrl());
});

test('It calls the exportColorGroups method and returns a proper response', function (): void {
    $companyId = 1;

    setCompanyIdInSession($companyId);

    $requestParameter = [
        'search_text' => 'test',
        'sort_by' => 'test',
        'sort_direction' => 'test',
        'per_page' => 'test',
    ];

    $colorGroupQueries = $this->mock(ColorGroupQueries::class, function ($mock) use (
        $requestParameter,
        $companyId
    ): void {
        $mock->shouldReceive('getColorGroupsExport')
            ->once()
            ->with($requestParameter, $companyId)
            ->andReturn(collect(new ColorGroup()));
    });

    $colorGroupController = new ColorGroupController($colorGroupQueries);

    $response = $colorGroupController->exportColorGroups('filename.csv', new Request($requestParameter));

    $this->assertEquals(200, $response->getStatusCode());

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);
});

test(
    'It calls the getColorGroupSalesSummary method of the ColorGroupQueries class as expected',
    function (): void {
        $companyId = 1;

        setCompanyIdInSession($companyId);
        $filterData = [
            'locationId' => null,
            'id' => null,
            'type' => null,
            'date' => '',
        ];

        $colorGroupQueries = $this->mock(ColorGroupQueries::class, function ($mock): void {
            $mock->shouldReceive('getColorGroupSalesSummary')
                ->once()
                ->andReturn(collect([]));
        });

        $colorGroupController = new ColorGroupController($colorGroupQueries);
        $redirectResponse = $colorGroupController->getColorGroupSalesSummary(new Request($filterData));

        expect($redirectResponse)
            ->toHaveKeys(['color_groups', 'total_sales', 'total_units_sold']);
    }
);
