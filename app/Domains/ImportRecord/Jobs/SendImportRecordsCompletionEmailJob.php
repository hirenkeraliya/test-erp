<?php

declare(strict_types=1);

namespace App\Domains\ImportRecord\Jobs;

use App\Domains\EmailRecipient\EmailRecipientQueries;
use App\Domains\EmailRecipient\Enums\EmailTypes;
use App\Domains\ImportRecord\Mail\SendImportRecordsCompletionMail;
use App\Models\EmailRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SendImportRecordsCompletionEmailJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $importRecordId,
        public int $companyId,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $emailRecipientQueries = resolve(EmailRecipientQueries::class);

        $recipients = $emailRecipientQueries->getByEmailType(
            $this->companyId,
            EmailTypes::IMPORT_RECORDS_STATUS_UPDATES->value
        );

        try {
            foreach ($recipients as $recipient) {
                /** @var EmailRecipient $recipient */
                Mail::to([[
                    'name' => $recipient->receiver_name,
                    'email' => $recipient->receiver_email,
                ]])->send(new SendImportRecordsCompletionMail($this->importRecordId, $this->companyId));
            }
        } catch (Throwable $throwable) {
            Log::error('Send Import Record Completion Email Job Error', [
                'error_message' => 'Error message: ' . $throwable->getMessage(),
                'error_code' => 'Error code: ' . $throwable->getCode(),
                'Import Record Id' => $this->importRecordId,
                'file' => 'File: ' . $throwable->getFile(),
                'line' => 'Line: ' . $throwable->getLine(),
                'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                'Full error' => [$throwable],
            ]);

            $this->fail($throwable);
        }
    }
}
