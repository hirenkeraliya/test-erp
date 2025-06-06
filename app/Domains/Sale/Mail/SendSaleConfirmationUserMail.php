<?php

declare(strict_types=1);

namespace App\Domains\Sale\Mail;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendSaleConfirmationUserMail extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Sale $sale,
        public string $currencySymbol,
        public array $emailLogos = []
    ) {
        $this->emailLogos['header'] = null;
        if ($sale->member && $sale->member->company) {
            $this->emailLogos['header'] = $sale->member->company->getDiskBasedFirstMediaUrl('dark_logo');
        }

        $this->emailLogos['footer'] = null;
        if (! $sale->member) {
            return;
        }

        if (! $sale->member->company) {
            return;
        }

        $this->emailLogos['footer'] = $sale->member->company->getDiskBasedFirstMediaUrl('email_footer_logo');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        /** @var Carbon $happenedAtFormat */
        $happenedAtFormat = Carbon::createFromFormat('Y-m-d H:i:s', $this->sale->happened_at);
        $happenedAt = $happenedAtFormat->format('d/m/Y h:i:s A');

        $title = 'Sale Confirmation: [' . $this->sale->offline_sale_id . '] for [' . $this->sale->member?->getFullName() . '] - Placed on [' . $happenedAt . ']';

        return $this->subject($title)
            ->markdown('emails.send_sale_confirmation_user_email');
    }
}
