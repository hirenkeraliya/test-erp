<?php

declare(strict_types=1);

use App\Domains\ImportRecordFailedRow\ImportRecordFailedRowQueries;
use App\Models\ImportRecord;
use App\Models\ImportRecordFailedRow;

beforeEach(function (): void {
    $this->importRecordFailedRowQueries = new ImportRecordFailedRowQueries();
});

test('an import record failed row can be added', function (): void {
    $importRecord = ImportRecord::factory()->create();
    $recordDetails = ['abc', 'xyz'];
    $validationErrors = ['ISSUE1', 'ISSUE2'];
    $this->importRecordFailedRowQueries->addNew($recordDetails, $validationErrors, $importRecord->id);

    $importRecordFailedRow = ImportRecordFailedRow::first();

    expect($importRecordFailedRow)
        ->import_record_id->toEqual($importRecord->id)
        ->row_data->toEqual($recordDetails)
        ->fail_reasons->toEqual($validationErrors);
});

test('an import record failed row can be deleted', function (): void {
    $importRecord = ImportRecord::factory()->create();
    $importRecordFailedRow = ImportRecordFailedRow::factory()->create([
        'import_record_id' => $importRecord->id,
    ]);
    $this->importRecordFailedRowQueries->deleteByImportRecordId($importRecord->id);
    $this->assertDatabaseMissing('import_record_failed_rows', $importRecordFailedRow->toArray());
});
