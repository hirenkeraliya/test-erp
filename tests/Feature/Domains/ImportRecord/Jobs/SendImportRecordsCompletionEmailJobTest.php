<?php

declare(strict_types=1);

use App\Domains\EmailRecipient\Enums\EmailTypes;
use App\Domains\ImportRecord\Enums\ImportTypes;
use App\Domains\ImportRecord\Enums\Status;
use App\Domains\ImportRecord\ImportRecordQueries;
use App\Domains\ImportRecord\Jobs\SendImportRecordsCompletionEmailJob;
use App\Domains\ImportRecord\Mail\SendImportRecordsCompletionMail;
use App\Models\EmailRecipient;
use App\Models\ImportRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

test('it sends the import record status updates emails', function (): void {
    Mail::fake();

    $importRecord = getImportRecordDetails();

    $emailRecipient = EmailRecipient::factory(2)->create([
        'company_id' => $importRecord->company_id,
        'email_type_id' => EmailTypes::IMPORT_RECORDS_STATUS_UPDATES->value,
    ]);

    $this->mock(ImportRecordQueries::class, function ($mock) use ($importRecord): void {
        $mock->shouldReceive('getByIdWithCompany')
            ->times(2)
            ->andReturn($importRecord);
    });

    SendImportRecordsCompletionEmailJob::dispatch(
        $importRecord->id,
        $importRecord->company_id,
        $emailRecipient
    )->onQueue(config('horizon.default_queue_name'));

    Mail::assertSent(SendImportRecordsCompletionMail::class, 2);
});

it('sends email to recipient for import file completion', function (): void {
    $importRecord = getImportRecordDetails();

    $recipientA = EmailRecipient::factory()->create([
        'company_id' => $importRecord->company_id,
        'email_type_id' => EmailTypes::IMPORT_RECORDS_STATUS_UPDATES->value,
    ]);

    Mail::fake();

    $this->mock(ImportRecordQueries::class, function ($mock) use ($importRecord): void {
        $mock->shouldReceive('getByIdWithCompany')
            ->once()
            ->andReturn($importRecord);
    });

    SendImportRecordsCompletionEmailJob::dispatch(
        $importRecord->id,
        $importRecord->company_id,
        new Collection([$recipientA])
    )->onQueue(config('horizon.default_queue_name'));

    Mail::assertSent(
        SendImportRecordsCompletionMail::class,
        fn ($mail): bool => $mail->hasTo($recipientA->receiver_email)
        && $mail->importRecord->id === $importRecord->id
    );
});

it('doesnt send any email if there are no recipient', function (): void {
    $importRecord = getImportRecordDetails();

    Mail::fake();

    SendImportRecordsCompletionEmailJob::dispatch(
        $importRecord->id,
        $importRecord->company_id,
        new Collection()
    )->onQueue(config('horizon.default_queue_name'));

    Mail::assertNotSent(SendImportRecordsCompletionMail::class);
});

function getImportRecordDetails(array $headerColumns = []): ImportRecord
{
    Storage::fake('public');

    $importRecord = ImportRecord::factory()->create([
        'type_id' => ImportTypes::PRODUCTS->value,
        'records_in_file' => 0,
        'records_imported' => 0,
        'records_failed' => 0,
        'status' => Status::COMPLETED->value,
        'header_columns' => $headerColumns,
    ]);

    $importRecord->copyMedia(__DIR__ . '/import-file.xlsx')
        ->toMediaCollection('uploaded_file');

    return $importRecord;
}
