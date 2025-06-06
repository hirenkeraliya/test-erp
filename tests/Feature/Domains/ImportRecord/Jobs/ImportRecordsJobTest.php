<?php

declare(strict_types=1);

use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\Enums\Status;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Jobs\ImportRecordsJob;
use App\Domains\ImportRecord\Services\ImportRecordService;
use App\Domains\ImportRecordFailedRow\ImportRecordFailedRowQueries;
use App\Domains\Product\Imports\ImportProduct;
use App\Models\ImportRecord;
use App\Services\SpreadsheetService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

test('It can import file', function (): void {
    Mail::fake();

    $spreadsheetData = getSpreadsheetData();

    $importRecord = getImportRecord();

    $this->mock(SpreadsheetService::class, function ($mock): void {
        $mock->shouldReceive('setRowFilters')
            ->times(0);

        mockSpreadsheetServiceMethod($mock);

        mockGetColumnValueForMethod($mock, 1, 1, 'name');
        mockGetColumnValueForMethod($mock, 1, 2, 'code');
        mockGetColumnValueForMethod($mock, 2, 1, 'Test Name 1');
        mockGetColumnValueForMethod($mock, 2, 2, 'Test Code 1');
        mockGetColumnValueForMethod($mock, 3, 1, 'Test Name 2');
        mockGetColumnValueForMethod($mock, 3, 2, 'Test Code 2');
    });

    $this->mock(ImportProduct::class, function ($mock): void {
        mockValidateMethod($mock);
        mockSaveMethod($mock);

        mockValidateMethod($mock, ['problem in import record 2']);
        mockSaveMethod($mock, 0);
    });

    $this->mock(ImportRecordQueries::class, function ($mock) use ($importRecord): void {
        mockImportRecordQueriesMethods($mock);

        $mock->shouldReceive('saveHeaderColumns')
            ->once();

        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($importRecord);

        $mock->shouldReceive('incrementImportedRecordsCount')
            ->once();

        $mock->shouldReceive('incrementFailedRecordsCount')
            ->once();
    });

    $this->mock(ImportRecordService::class, function ($mock): void {
        $jobRestartTime = now()->addSeconds(100);
        $mock->shouldReceive('getJobRestartTime')
            ->once()
            ->andReturn($jobRestartTime);

        mockIsThisFirstImportCycleMethod($mock, 2);

        $mock->shouldReceive('jobIsReadyToExpire')
            ->times(3)
            ->andReturn(false);

        $headerColumns = ['name', 'code'];
        mockHeaderColumnsAlreadySetMethod($mock, 1);
        mockHeaderColumnsAlreadySetMethod($mock, 2, $headerColumns);
        mockHeaderColumnsAlreadySetMethod($mock, 3, $headerColumns);

        mockHasMoreRecordsMethod($mock, 1, 0);
        mockHasMoreRecordsMethod($mock, 2);
        mockHasMoreRecordsMethod($mock, 3);

        $mock->shouldReceive('getNewEndRowNumber')
            ->times(0)
            ->with(3, 3, 1, 3)
            ->andReturn(3);
    });

    $this->mock(ImportRecordFailedRowQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->times(1);

        $mock->shouldReceive('deleteByImportRecordId')
            ->times(1);
    });

    ImportRecordsJob::dispatch($importRecord)->onQueue(config('horizon.default_queue_name'));
});

test('It does not set header columns if already set', function (): void {
    Mail::fake();

    $spreadsheetData = getSpreadsheetData();

    $importRecord = getImportRecord(['name', 'code']);

    $this->mock(SpreadsheetService::class, function ($mock): void {
        $mock->shouldReceive('setRowFilters')
            ->times(0);

        mockSpreadsheetServiceMethod($mock);

        mockGetColumnValueForMethod($mock, 1, 1, 'name', 0);
        mockGetColumnValueForMethod($mock, 1, 2, 'code', 0);
        mockGetColumnValueForMethod($mock, 2, 1, 'Test Name 1');
        mockGetColumnValueForMethod($mock, 2, 2, 'Test Code 1');
        mockGetColumnValueForMethod($mock, 3, 1, 'Test Name 2');
        mockGetColumnValueForMethod($mock, 3, 2, 'Test Code 2');
    });

    $this->mock(ImportProduct::class, function ($mock): void {
        mockValidateMethod($mock);
        mockSaveMethod($mock);

        mockValidateMethod($mock);
        mockSaveMethod($mock);
    });

    $this->mock(ImportRecordQueries::class, function ($mock) use ($importRecord): void {
        mockImportRecordQueriesMethods($mock);

        $mock->shouldReceive('saveHeaderColumns')
            ->times(0);

        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($importRecord);

        $mock->shouldReceive('incrementImportedRecordsCount')
            ->times(2);

        $mock->shouldReceive('incrementFailedRecordsCount')
            ->times(0);
    });

    $this->mock(ImportRecordService::class, function ($mock): void {
        $jobRestartTime = now()->addSeconds(100);
        $mock->shouldReceive('getJobRestartTime')
            ->once()
            ->andReturn($jobRestartTime);

        mockIsThisFirstImportCycleMethod($mock, 2);

        $mock->shouldReceive('jobIsReadyToExpire')
            ->times(3)
            ->andReturn(false);

        $headerColumns = ['name', 'code'];
        mockHeaderColumnsAlreadySetMethod($mock, 1, $headerColumns, true);
        mockHeaderColumnsAlreadySetMethod($mock, 2, $headerColumns);
        mockHeaderColumnsAlreadySetMethod($mock, 3, $headerColumns);

        mockHasMoreRecordsMethod($mock, 1, 0);
        mockHasMoreRecordsMethod($mock, 2);
        mockHasMoreRecordsMethod($mock, 3);

        $mock->shouldReceive('getNewEndRowNumber')
            ->times(0)
            ->with(3, 3, 1, 3)
            ->andReturn(3);
    });

    $this->mock(ImportRecordFailedRowQueries::class, function ($mock): void {
        $mock->shouldReceive('addNew')
            ->times(0)
            ->with([], [], 1);

        $mock->shouldReceive('deleteByImportRecordId')
            ->once();
    });

    ImportRecordsJob::dispatch($importRecord)->onQueue(config('horizon.default_queue_name'));
});

function getImportRecord(array $headerColumns = []): ImportRecord
{
    Storage::fake('public');

    $importRecord = ImportRecord::factory()->create([
        'type_id' => ImportTypes::PRODUCTS->value,
        'records_in_file' => 0,
        'records_imported' => 0,
        'records_failed' => 0,
        'status' => Status::PENDING->value,
        'header_columns' => $headerColumns,
    ]);

    $importRecord->copyMedia(__DIR__ . '/import-file.xlsx')
        ->toMediaCollection('uploaded_file');

    return $importRecord;
}

function getSpreadsheetData(): array
{
    return [
        [
            'name' => 'Test Name 1',
            'code' => 'Test Code 1',
        ],
        [
            'name' => 'Test Name 2',
            'code' => 'Test Code 2',
        ],
    ];
}

function mockGetColumnValueForMethod(
    $mockClass,
    int $rowIndex,
    int $columnIndex,
    string $response,
    int $times = 1
): void {
    $mockClass->shouldReceive('getColumnValueFor')
        ->times($times)
        ->with($rowIndex, $columnIndex)
        ->andReturn($response);
}

function mockValidateMethod($mockClass, array $response = [], int $times = 1): void
{
    $mockClass->shouldReceive('validate')
        ->times($times)
        ->andReturn($response);
}

function mockSaveMethod($mockClass, int $times = 1): void
{
    $mockClass->shouldReceive('save')
        ->times($times);
}

function mockIsThisFirstImportCycleMethod($mockClass, int $times = 1): void
{
    $mockClass->shouldReceive('isThisFirstImportCycle')
        ->times($times)
        ->with(null, null)
        ->andReturn(true);
}

function mockHeaderColumnsAlreadySetMethod(
    $mockClass,
    int $rowIndex,
    array $headerColumns = [],
    bool $return = false
): void {
    $mockClass->shouldReceive('headerColumnsAlreadySet')
        ->once()
        ->with($rowIndex, $headerColumns)
        ->andReturn($return);
}

function mockHasMoreRecordsMethod($mockClass, int $rowIndex, int $times = 1): void
{
    $mockClass->shouldReceive('hasMoreRecords')
        ->times($times)
        ->with(3, $rowIndex, 2)
        ->andReturn(false);
}

function mockSpreadsheetServiceMethod($mockClass): void
{
    $mockClass->shouldReceive('loadFileDetails')
            ->once();

    $mockClass->shouldReceive('getHighestRow')
            ->once()
            ->andReturn(3);

    $mockClass->shouldReceive('getHighestColumn')
            ->once()
            ->andReturn(2);

    $mockClass->shouldReceive('columnIndexFromString')
            ->once()
            ->andReturn(2);
}

function mockImportRecordQueriesMethods($mockClass): void
{
    $mockClass->shouldReceive('markAsInProgress')
        ->once();

    $mockClass->shouldReceive('markAsCompleted')
        ->once();

    $mockClass->shouldReceive('generateFailedRecordsFile')
        ->once();

    $mockClass->shouldReceive('getUploadedMedia')
        ->once()
        ->andReturn(getUploadedMedia());

    $mockClass->shouldReceive('getFilePath')
        ->once();
}

function getUploadedMedia(): Media
{
    return new Media([
        'id' => 1,
        'collection_name' => 'upload_file',
        'name' => 'import-file',
        'file_name' => 'import-file.xlsx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'disk' => 'public',
        'conversions_disk' => 'public',
        'size' => 4899,
    ]);
}
