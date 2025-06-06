<?php

declare(strict_types=1);

namespace App\Domains\EmailRecipient\Exports;

use App\Domains\EmailRecipient\Enums\EmailTypes;
use App\Models\EmailRecipient;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmailRecipientExport implements FromCollection, WithHeadings
{
    public function __construct(
        protected Collection $emailRecipients
    ) {
    }

    public function collection(): Collection
    {
        return $this->emailRecipients->map(fn (EmailRecipient $emailRecipient): array => [
            'receiver_name' => $emailRecipient->receiver_name,
            'receiver_email' => $emailRecipient->receiver_email,
            'email_type' => EmailTypes::getFormattedCaseName($emailRecipient->email_type_id),
        ]);
    }

    public function headings(): array
    {
        return ['Receiver Name', 'Receiver Email', 'Email Type'];
    }
}
