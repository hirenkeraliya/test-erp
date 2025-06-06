<?php

declare(strict_types=1);

namespace App\Domains\Member\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendConfirmationEmailMail extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Member $member,
        public string $message,
        public array $emailLogos = []
    ) {
        $company = $member->company ?? null;
        $this->emailLogos['header'] = $company?->getDiskBasedFirstMediaUrl('dark_logo');
        $this->emailLogos['footer'] = $company?->getDiskBasedFirstMediaUrl('email_footer_logo');
    }

    public function build(): static
    {
        return $this->subject('Email Verification OTP')
            ->markdown('emails.members.send_otp');
    }
}
