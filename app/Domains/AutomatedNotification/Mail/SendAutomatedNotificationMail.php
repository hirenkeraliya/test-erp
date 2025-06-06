<?php

declare(strict_types=1);

namespace App\Domains\AutomatedNotification\Mail;

use App\Domains\AutomatedNotification\Enums\AutomatedNotificationTypes;
use App\Domains\Company\CompanyQueries;
use App\Models\AutomatedNotification;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendAutomatedNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public AutomatedNotification $automatedNotification,
        public string $message,
        public array $data = [],
        public array $emailLogos = []
    ) {
        $companyQueries = resolve(CompanyQueries::class);
        /** @var Company $company */
        $company = $companyQueries->getById($this->automatedNotification->company_id);
        $this->emailLogos['header'] = $company->getDiskBasedFirstMediaUrl('dark_logo');
        $this->emailLogos['footer'] = $company->getDiskBasedFirstMediaUrl('email_footer_logo');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): self
    {
        $date = Carbon::now()->format('d-m-Y');
        if (
            $this->automatedNotification->type_id === AutomatedNotificationTypes::LOW_STOCK_COMPANY->value ||
            $this->automatedNotification->type_id === AutomatedNotificationTypes::LOW_STOCK_LOCATION->value ||
            $this->automatedNotification->type_id === AutomatedNotificationTypes::LOW_STOCK_PRODUCT->value) {
            if (isset($this->data['store_manager']) && true === $this->data['store_manager']) {
                return $this->subject($this->message)
                ->markdown('emails.automated_notification.low_stock_for_store_manager_email')->with([
                    'date' => $date,
                    'message' => $this->message,
                    'preparedData' => $this->data,
                ]);
            }

            if (isset($this->data['warehouse_manager']) && true === $this->data['warehouse_manager']) {
                return $this->subject($this->message)
                ->markdown('emails.automated_notification.low_stock_for_warehouse_manager_email')->with([
                    'date' => $date,
                    'message' => $this->message,
                    'preparedData' => $this->data,
                ]);
            }

            return $this->subject($this->message)
                ->markdown('emails.automated_notification.low_stock_email')->with([
                    'preparedData' => $this->data,
                    'date' => $date,
                    'message' => $this->message,
                ]);
        }

        if ($this->automatedNotification->type_id === AutomatedNotificationTypes::NO_STOCK->value) {
            return $this->subject($this->message)
                ->markdown('emails.automated_notification.no_stock_email')->with([
                    'preparedData' => $this->data,
                    'date' => $date,
                    'message' => $this->message,
                ]);
        }

        return $this->subject($this->message)
            ->markdown('emails.automated_notification.request_and_deadline_stock_email')->with([
                'date' => $date,
                'message' => $this->message,
            ]);
    }
}
