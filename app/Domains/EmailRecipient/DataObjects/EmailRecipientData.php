<?php

declare(strict_types=1);

namespace App\Domains\EmailRecipient\DataObjects;

use App\Domains\EmailRecipient\Enums\EmailTypes;
use Spatie\LaravelData\Data;

class EmailRecipientData extends Data
{
    public function __construct(
        public int $email_type_id,
        public string $receiver_name,
        public string $receiver_email,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'email_type_id' => ['required', 'integer', 'in:' . EmailTypes::getValues()],
            'receiver_name' => ['required', 'string', 'max:255'],
            'receiver_email' => ['required', 'email:rfc,dns', 'max:255'],
        ];
    }
}
