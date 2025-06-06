<?php

declare(strict_types=1);

namespace App\Domains\Region\Mail;

use App\Models\Region;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class SendDailySalesSummaryToRegionManagerMail extends Mailable
{
    use Queueable;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Region $region,
        public array $preparedData = [],
    ) {
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        return $this->subject(
            'Summary of '.$this->region->name.' region sales report of ('.$this->preparedData['date'].')'
        )
            ->markdown('emails.region.daily_total_sales_email')->with('preparedData', $this->preparedData);
    }
}
