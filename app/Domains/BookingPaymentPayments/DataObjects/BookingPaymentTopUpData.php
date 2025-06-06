<?php

declare(strict_types=1);

namespace App\Domains\BookingPaymentPayments\DataObjects;

use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class BookingPaymentTopUpData extends Data
{
    public function __construct(
        public ?int $payment_type_id,
        public float $amount,
        public ?string $remarks,
        public ?array $payments = [],
    ) {
    }

    /**
     * @return array<string, array<(Unique|string)>>
     */
    public static function rules(): array
    {
        return [
            'payment_type_id' => ['sometimes', 'nullable', 'integer', 'exists:payment_types,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'remarks' => ['nullable', 'string'],
            'payments' => ['sometimes', 'nullable', 'array'],
            'payments.*.payment_type_id' => ['required', 'integer'],
            'payments.*.currency_id' => ['sometimes', 'nullable', 'integer', 'exists:currencies,id'],
            'payments.*.current_currency_rate' => ['sometimes', 'nullable', 'numeric'],
            'payments.*.currency_amount' => ['sometimes', 'nullable', 'numeric', 'min:0.01'],
            'payments.*.credit_note_id' => ['sometimes', 'integer'],
            'payments.*.gift_card_id' => ['sometimes', 'integer'],
            'payments.*.amount' => ['required', 'numeric'],
            'payments.*.extra_details' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
