<?php

declare(strict_types=1);

namespace App\Domains\Member\Mail;

use App\Domains\EmailTemplate\EmailTemplateQueries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;

class SendEmail extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable;

    /**
     * Create a new message instance.
     */
    public function __construct(
        private readonly int $emailTemplateId,
    ) {
    }

    public function build(): static
    {
        $emailTemplateQueries = resolve(EmailTemplateQueries::class);
        $emailTemplate = $emailTemplateQueries->getById($this->emailTemplateId);

        return $this->subject($emailTemplate->name)
            ->html($emailTemplate->html);
    }
}
