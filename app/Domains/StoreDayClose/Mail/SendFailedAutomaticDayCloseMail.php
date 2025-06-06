<?php

declare(strict_types=1);

namespace App\Domains\StoreDayClose\Mail;

use App\Models\Employee;
use App\Models\Location;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendFailedAutomaticDayCloseMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Employee $employee,
        public Location $location,
        public array $emailLogos = []
    ) {
        $company = $this->employee->company ?? null;
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
        return $this->subject('The automatic day close failed - ' . $this->location->name)
            ->markdown('emails.failed_automatic_day_close_email');
    }
}
