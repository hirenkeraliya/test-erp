<?php

declare(strict_types=1);

namespace App\Domains\DigitalInvoice\DataObjects;

use Spatie\LaravelData\Data;

class DigitalInvoiceData extends Data
{
    public function __construct(
        public int $module_id,
        public string $module_type,
        public string $buyer_name,
        public string $buyer_tin,
        public string $buyer_identification_number,
        public string $buyer_sst_number,
        public ?string $buyer_email,
        public string $buyer_address,
        public string $buyer_contact,
    ) {
    }

    public static function rules(): array
    {
        return [
            'module_id' => ['required', 'integer'],
            'module_type' => ['required', 'string'],
            'buyer_name' => ['required', 'string', 'max:255'],
            'buyer_tin' => ['required', 'string', 'max:255'],
            'buyer_identification_number' => ['required', 'string', 'max:255'],
            'buyer_sst_number' => ['required', 'string', 'max:255'],
            'buyer_email' => ['nullable', 'email', 'max:255'],
            'buyer_address' => ['required', 'string', 'max:500'],
            'buyer_contact' => ['required'],
        ];
    }
}
