<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\SizeGroup\Enums\SizeGroupImportColumns;
use App\Domains\SizeGroup\Imports\ImportSizeGroupBulkUpdate;
use App\Domains\SizeGroup\SizeGroupQueries;
use App\Models\ImportRecord;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;

    $sizeGroupData = [
        'name' => 'Abc',
        'code' => 'def',
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(SizeGroupQueries::class, function ($mock) use ($companyId, $sizeGroupData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($sizeGroupData['name'], $companyId)
            ->andReturn(true);

        $mock->shouldReceive('codeTakenByAnotherSizeGroup')
            ->once()
            ->with($sizeGroupData['code'], $sizeGroupData['name'], $companyId)
            ->andReturn(false);
    });

    $importSizeGroupBulkUpdate = new ImportSizeGroupBulkUpdate();
    $redirectResponse = $importSizeGroupBulkUpdate->validate($sizeGroupData, $importRecord);
    expect($redirectResponse)->toBeArray();
});

test(
    'name is required for import record',
    function (): void {
        $companyId = 1;

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $sizeGroupData = [
            'name' => '',
            'code' => '',
        ];

        $this->mock(SizeGroupQueries::class, function ($mock) use ($companyId, $sizeGroupData): void {
            $mock->shouldReceive('codeTakenByAnotherSizeGroup')
                ->once()
                ->with($sizeGroupData['code'], $sizeGroupData['name'], $companyId)
                ->andReturn(false);
        });

        $importSizeGroupBulkUpdate = new ImportSizeGroupBulkUpdate();
        $redirectResponse = $importSizeGroupBulkUpdate->validate($sizeGroupData, $importRecord);
        expect($redirectResponse)->toContain('The name is required.');
    }
);

test('save method works for the size group details update', function (): void {
    $companyId = 1;

    $sizeGroupData = [
        'name' => 'size-group-1',
        'code' => 'size-group-code',
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::SIZE_GROUP_BULK_UPDATE->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $this->mock(SizeGroupQueries::class, function ($mock): void {
        $mock->shouldReceive('updateByName')
            ->times(1);
    });

    $importSizeGroupBulkUpdate = new ImportSizeGroupBulkUpdate();
    $importSizeGroupBulkUpdate->save($sizeGroupData, $importRecord);
});

test('validate import Size Group Bulk Update Import Columns', function (): void {
    $requiredHeaderColumns = SizeGroupImportColumns::getArrayValues();

    $importSizeGroupBulkUpdate = new ImportSizeGroupBulkUpdate();
    $response = $importSizeGroupBulkUpdate->validateColumns($requiredHeaderColumns, [], 1);
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertTrue($response['status']);
});
