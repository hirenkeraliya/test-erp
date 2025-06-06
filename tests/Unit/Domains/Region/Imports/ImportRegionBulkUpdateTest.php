<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Region\Enums\RegionImportColumns;
use App\Domains\Region\Imports\ImportRegionBulkUpdate;
use App\Domains\Region\RegionQueries;
use App\Domains\Region\Services\RegionService;
use App\Models\ImportRecord;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;

    $regionData = [
        'name' => 'Abc',
        'code' => 'def',
        'manager_name' => 'manager',
        'manager_email' => 'manager@gmail.com',
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(RegionQueries::class, function ($mock) use ($companyId, $regionData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($regionData['name'], $companyId)
            ->andReturn(false);

        $mock->shouldReceive('existsByCodeExceptCurrentRecord')
            ->once()
            ->andReturn(false);
    });

    $importUpdateRegion = new ImportRegionBulkUpdate();
    $redirectResponse = $importUpdateRegion->validate($regionData, $importRecord);
    expect($redirectResponse)->toBeArray();
});

test(
    'name is required for import record',
    function (): void {
        $companyId = 1;

        $regionData = [
            'name' => '',
            'code' => '',
            'manager_name' => '',
            'manager_email' => '',
        ];

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $this->mock(RegionQueries::class, function ($mock): void {
            $mock->shouldReceive('existsByCodeExceptCurrentRecord')
                ->once()
                ->andReturn(false);
        });

        $importUpdateRegion = new ImportRegionBulkUpdate();
        $redirectResponse = $importUpdateRegion->validate($regionData, $importRecord);
        expect($redirectResponse)->toContain('The name is required.');
    }
);

test('It calls updateByName method to update region details', function (): void {
    $companyId = 1;

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::REGIONS->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $regionData = [
        'name' => 'region-1',
        'code' => 'region-code',
        'manager_name' => 'manager',
        'manager_email' => 'manager@gmail.com',
    ];

    $this->mock(RegionQueries::class, function ($mock): void {
        $mock->shouldReceive('updateByName')
            ->once();
    });

    $this->mock(RegionService::class, function ($mock): void {
        $mock->shouldReceive('getRegionData')
            ->once();
    });

    $importUpdateRegion = new ImportRegionBulkUpdate();
    $importUpdateRegion->save($regionData, $importRecord);
    $this->assertTrue(true);
});

test('validate import regions Type Bulk Update Import Columns', function (): void {
    $requiredHeaderColumns = RegionImportColumns::getArrayValues();

    $importUpdateRegion = new ImportRegionBulkUpdate();
    $response = $importUpdateRegion->validateColumns($requiredHeaderColumns, [], 1);
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertTrue($response['status']);
});
