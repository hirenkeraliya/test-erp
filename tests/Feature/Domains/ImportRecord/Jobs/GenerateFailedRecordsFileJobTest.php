<?php

declare(strict_types=1);

use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\Enums\Status;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Jobs\GenerateFailedRecordsFileJob;
use App\Domains\ImportRecordFailedRow\ImportRecordFailedRowQueries;
use App\Models\ImportRecord;

test('it call generateFailedRecordsFile method of ImportRecordQueries class', function (): void {
    $importRecord = ImportRecord::factory()->create([
        'id' => 1,
        'type_id' => ImportTypes::PRODUCTS->value,
        'records_in_file' => 0,
        'records_imported' => 0,
        'records_failed' => 0,
        'status' => Status::COMPLETED->value,
    ]);

    $this->mock(ImportRecordQueries::class, function ($mock) use ($importRecord): void {
        $mock->shouldReceive('generateFailedRecordsFile')
            ->once();

        $mock->shouldReceive('getById')
            ->once()
            ->andReturn($importRecord);
    });

    $this->mock(ImportRecordFailedRowQueries::class, function ($mock): void {
        $mock->shouldReceive('deleteByImportRecordId')
            ->once();
    });

    GenerateFailedRecordsFileJob::dispatch($importRecord->id, $importRecord->company_id)->onQueue(
        config('horizon.default_queue_name')
    );
});
