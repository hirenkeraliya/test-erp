<?php

declare(strict_types=1);

namespace App\Domains\ExternalPurchaseOrderReceive\DataObjects;

use Spatie\LaravelData\Data;

class ExternalPurchaseOrderReceiveData extends Data
{
    public function __construct(
        public array $receive_items,
        public string $received_date,
        public ?string $notes = null,
    ) {
    }

    public static function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
            'received_date' => ['required', 'date', 'max:255', 'date_format:Y-m-d H:i:s'],
            'receive_items' => ['required', 'array'],
            'receive_items.*.quantity_received' => ['required', 'numeric', 'min:0.01'],
            'receive_items.*.batch_details' => ['nullable', 'array'],
            'receive_items.*.batch_details.*.batch_number' => ['required', 'string'],
            'receive_items.*.batch_details.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'receive_items.*.batch_details.*.expiry_date' => ['required', 'date',
                'date_format:Y-m-d',
                'after_or_equal:' . now()->format('Y-m-d'),
            ],
            'receive_items.*.batch_details.*.notes' => ['nullable', 'string'],
        ];
    }
}
