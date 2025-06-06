<?php

declare(strict_types=1);

namespace App\Domains\ImportRecord\Mail;

use App\Domains\ImportRecord\ImportRecordQueries;
use App\Models\ImportRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class SendImportRecordsCompletionMail extends Mailable
{
    use Queueable;

    public ImportRecord $importRecord;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public int $importRecordId,
        public int $companyId,
        public array $emailLogos = []
    ) {
        $importRecordQueries = resolve(ImportRecordQueries::class);
        $this->importRecord = $importRecordQueries->getByIdWithCompany($this->importRecordId, $this->companyId);

        $company = $this->importRecord->company ?? null;
        $this->emailLogos['header'] = $company?->getDiskBasedFirstMediaUrl('dark_logo');
        $this->emailLogos['footer'] = $company?->getDiskBasedFirstMediaUrl('email_footer_logo');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        return $this->subject('Import Records Process Completed')
            ->markdown('emails.import_records_completion_email')
            ->when($this->importRecord->records_failed, function ($mail): void {
                $mail->attach($this->importRecord->getLocalFilePath('failed_rows_file'));
            });
    }
}
