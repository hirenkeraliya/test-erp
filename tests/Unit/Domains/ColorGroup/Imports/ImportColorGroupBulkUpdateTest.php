<?php

declare(strict_types=1);

use App\Domains\ColorGroup\ColorGroupQueries;
use App\Domains\ColorGroup\Enums\ColorGroupImportColumns;
use App\Domains\ColorGroup\Imports\ImportColorGroupBulkUpdate;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Models\ImportRecord;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;

    $colorGroupData = [
        'name' => 'Abc',
        'code' => 'def',
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(ColorGroupQueries::class, function ($mock) use ($companyId, $colorGroupData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($colorGroupData['name'], $companyId)
            ->andReturn(true);

        $mock->shouldReceive('codeTakenByAnotherColorGroup')
            ->once()
            ->with($colorGroupData['code'], $colorGroupData['name'], $companyId)
            ->andReturn(false);
    });

    $importColorGroupBulkUpdate = new ImportColorGroupBulkUpdate();
    $redirectResponse = $importColorGroupBulkUpdate->validate($colorGroupData, $importRecord);
    expect($redirectResponse)->toBeArray();
});

test(
    'name is required for import record',
    function (): void {
        $companyId = 1;

        $colorGroupData = [
            'name' => '',
            'code' => '',
        ];

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $this->mock(ColorGroupQueries::class, function ($mock) use ($companyId, $colorGroupData): void {
            $mock->shouldReceive('codeTakenByAnotherColorGroup')
                ->once()
                ->with($colorGroupData['code'], $colorGroupData['name'], $companyId)
                ->andReturn(false);
        });

        $importColorGroupBulkUpdate = new ImportColorGroupBulkUpdate();
        $redirectResponse = $importColorGroupBulkUpdate->validate($colorGroupData, $importRecord);
        expect($redirectResponse)->toContain('The name is required.');
    }
);

test('save method works for the color group details update', function (): void {
    $companyId = 1;

    $colorGroupData = [
        'name' => 'color-group-1',
        'code' => 'color-group-code',
        'color_code' => '#ABCDE',
    ];

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::COLOR_GROUP_BULK_UPDATE->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $this->mock(ColorGroupQueries::class, function ($mock): void {
        $mock->shouldReceive('updateByName')
            ->times(1);
    });

    $importColorGroupBulkUpdate = new ImportColorGroupBulkUpdate();
    $importColorGroupBulkUpdate->save($colorGroupData, $importRecord);
});

test('validate import Color Group Bulk Update Import Columns', function (): void {
    $requiredHeaderColumns = ColorGroupImportColumns::getArrayValues();

    $importColorGroupBulkUpdate = new ImportColorGroupBulkUpdate();
    $response = $importColorGroupBulkUpdate->validateColumns($requiredHeaderColumns, [], 1);
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertTrue($response['status']);
});
