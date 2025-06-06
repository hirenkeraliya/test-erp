<?php

namespace App\Domains\ExportRecord\Jobs;

use App\Domains\EmailRecipient\EmailRecipientQueries;
use App\Domains\EmailRecipient\Enums\EmailTypes;
use App\Domains\ExportRecord\Enums\ExportRecordTypes;
use App\Domains\ExportRecord\Mails\SendExportExcelCompletionMail;
use App\Models\EmailRecipient;
use App\Models\ExportRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendExportExcelCompletionEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ExportRecord $exportRecord,
        public string $filePath,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $emailRecipientQueries = resolve(EmailRecipientQueries::class);

        $emailType = ExportRecordTypes::getEmailTypeFor($this->exportRecord->type_id);

        $recipients = $emailRecipientQueries->getByEmailType(
            (int) $this->exportRecord->company_id,
            $emailType->value
        );

        foreach ($recipients as $recipient) {
            /** @var EmailRecipient $recipient */
            try {
                Mail::to([[
                    'name' => $recipient->receiver_name,
                    'email' => $recipient->receiver_email,
                ]])->send(new SendExportExcelCompletionMail(
                    $this->exportRecord,
                    $this->filePath,
                    EmailTypes::getFormattedCaseName($emailType->value) . '.xlsx'
                ));
            } catch (Throwable $throwable) {
                Log::error('Send Export Excel completion Email', [
                    'error_message' => 'Error message: ' . $throwable->getMessage(),
                    'error_code' => 'Error code: ' . $throwable->getCode(),
                    'name' => $recipient->receiver_name,
                    'email' => $recipient->receiver_email,
                    'Export Record ID' => $this->exportRecord->id,
                    'file' => 'File: ' . $throwable->getFile(),
                    'line' => 'Line: ' . $throwable->getLine(),
                    'stack_trace' => 'Stack trace: ' . json_encode($throwable->getTrace(), JSON_PRETTY_PRINT),
                    'Full error' => [$throwable],
                ]);
            }
        }
    }
}
