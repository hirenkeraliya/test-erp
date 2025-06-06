<?php

declare(strict_types=1);

use App\Domains\Color\ColorQueries;
use App\Domains\Color\Enums\ColorImportColumns;
use App\Domains\Color\Imports\ImportColorBulkUpdate;
use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Models\ImportRecord;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;

    $colorBulkUpdateData = getColorBulkUpdateData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(ColorQueries::class, function ($mock) use ($companyId, $colorBulkUpdateData): void {
        $mock->shouldReceive('existsByName')
            ->once()
            ->with($colorBulkUpdateData['name'], $companyId)
            ->andReturn(true);

        $mock->shouldReceive('codeTakenByAnotherColor')
            ->once()
            ->with($colorBulkUpdateData['code'], $colorBulkUpdateData['name'], $companyId)
            ->andReturn(false);
    });

    $importColorBulkUpdate = new ImportColorBulkUpdate();
    $redirectResponse = $importColorBulkUpdate->validate($colorBulkUpdateData, $importRecord);
    expect($redirectResponse)->toBeArray();
});

test(
    'name is required for import record',
    function (): void {
        $companyId = 1;

        $colorData = [
            'name' => '',
            'code' => '',
        ];

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $this->mock(ColorQueries::class, function ($mock) use ($companyId, $colorData): void {
            $mock->shouldReceive('codeTakenByAnotherColor')
                ->once()
                ->with($colorData['code'], $colorData['name'], $companyId)
                ->andReturn(false);
        });

        $ImportColorBulkUpdate = new ImportColorBulkUpdate();
        $redirectResponse = $ImportColorBulkUpdate->validate($colorData, $importRecord);
        expect($redirectResponse)->toContain('The name is required.');
    }
);

test('save method works for the color details update', function (): void {
    $companyId = 1;

    $colorBulkUpdateData = getColorBulkUpdateData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::COLOR_BULK_UPDATE->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $this->mock(ColorQueries::class, function ($mock): void {
        $mock->shouldReceive('updateByName')
            ->once();
    });

    $ImportColorBulkUpdate = new ImportColorBulkUpdate();
    $ImportColorBulkUpdate->save($colorBulkUpdateData, $importRecord);
});

test('validate import Color Bulk Update Import Columns', function (): void {
    $requiredHeaderColumns = ColorImportColumns::getArrayValues();

    $importColorBulkUpdate = new ImportColorBulkUpdate();
    $response = $importColorBulkUpdate->validateColumns($requiredHeaderColumns, [], 1);
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertTrue($response['status']);
});

function getColorBulkUpdateData(): array
{
    return [
        'name' => 'Abc',
        'code' => 'def',
        'color_code' => null,
        'color_group' => null,
    ];
}
