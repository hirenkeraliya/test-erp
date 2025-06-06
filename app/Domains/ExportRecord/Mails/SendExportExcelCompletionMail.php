<?php

namespace App\Domains\ExportRecord\Mails;

use App\Models\ExportRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendExportExcelCompletionMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public ExportRecord $exportRecord,
        public string $attachmentPath,
        public string $attachmentFileName
    ) {
    }

    public function build(): static
    {
        return $this->subject('Requested Excel File')
            ->markdown('emails.export_excel_completion_email')
            ->attach($this->exportRecord->getLocalFilePath('export_file'), [
                'as' => $this->attachmentFileName,
            ]);
    }
}
