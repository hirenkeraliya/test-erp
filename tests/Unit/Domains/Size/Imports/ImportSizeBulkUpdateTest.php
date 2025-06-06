<?php

declare(strict_types=1);

use App\Domains\Common\Enums\ModelMapping;
use App\Domains\ImportRecord\Enums\ColumnValidationIssueTypes;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\Size\Enums\SizeImportColumns;
use App\Domains\Size\Imports\ImportSizeBulkUpdate;
use App\Domains\Size\SizeQueries;
use App\Domains\SizeGroup\SizeGroupQueries;
use App\Models\ImportRecord;

test('the validate method returns blank array when no error in given details', function (): void {
    $companyId = 1;

    $sizeBulkUpdateData = getSizeBulkUpdateData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'created_by_id' => 1,
    ]);

    $this->mock(SizeQueries::class, function ($mock): void {
        $mock->shouldReceive('existsByName')
             ->times(2);
        $mock->shouldReceive('codeTakenByAnotherSize')
             ->times(1);
    });

    $importSizeBulkUpdate = new ImportSizeBulkUpdate();
    $redirectResponse = $importSizeBulkUpdate->validate($sizeBulkUpdateData, $importRecord);
    expect($redirectResponse)->toBeArray();
});

test(
    'name and create_after are required for import record',
    function (): void {
        $companyId = 1;

        $sizeData = [
            'name' => '',
            'code' => 's',
            'size_group' => '',
            'create_after' => '',
        ];

        $importRecord = ImportRecord::factory()->make([
            'company_id' => $companyId,
            'created_by_id' => 1,
        ]);

        $this->mock(SizeQueries::class, function ($mock): void {
            $mock->shouldReceive('codeTakenByAnotherSize')
                ->once();
        });

        $this->mock(SizeGroupQueries::class, function ($mock): void {
            $mock->shouldReceive('getIdByName')
                ->once();
        });

        $importSizeBulkUpdate = new ImportSizeBulkUpdate();
        $redirectResponse = $importSizeBulkUpdate->validate($sizeData, $importRecord);
        $this->assertEquals(2, count($redirectResponse));
    }
);

test('save method works for the size details update', function (): void {
    $companyId = 1;

    $sizeBulkUpdateData = getSizeBulkUpdateData();

    $importRecord = ImportRecord::factory()->make([
        'company_id' => $companyId,
        'type_id' => ImportTypes::SIZE_BULK_UPDATE->value,
        'created_by_id' => 1,
        'created_by_type' => ModelMapping::ADMIN->name,
    ]);

    $this->mock(SizeQueries::class, function ($mock): void {
        $mock->shouldReceive('getIdBySortName')
            ->once();
        $mock->shouldReceive('updateByName')
            ->once();
    });

    $importSizeBulkUpdate = new ImportSizeBulkUpdate();
    $importSizeBulkUpdate->save($sizeBulkUpdateData, $importRecord);
});

test('validate import Color Group Bulk Update Import Columns', function (): void {
    $requiredHeaderColumns = SizeImportColumns::getArrayValues();

    $importSizeBulkUpdate = new ImportSizeBulkUpdate();
    $response = $importSizeBulkUpdate->validateColumns($requiredHeaderColumns, [], 1);
    $this->assertTrue(ColumnValidationIssueTypes::COLUMN_ISSUE->value === $response['type']);
    $this->assertTrue($response['status']);
});

function getSizeBulkUpdateData(): array
{
    return [
        'name' => 'size-1',
        'code' => 'size-code',
        'sizeGroup' => null,
        'create_after' => 1,
    ];
}
