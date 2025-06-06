<?php

declare(strict_types=1);

namespace App\Domains\BookingPaymentRefund\DataObjects;

use Spatie\LaravelData\Data;

class BookingPaymentRefundData extends Data
{
    public function __construct(
        public float $amount,
        public int $payment_type_id,
        public ?int $currency_id,
        public ?float $current_currency_rate,
        public ?float $currency_amount,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'payment_type_id' => ['required', 'integer', 'exists:payment_types,id'],
            'currency_id' => ['sometimes', 'nullable', 'integer', 'exists:currencies,id'],
            'current_currency_rate' => ['sometimes', 'nullable', 'numeric'],
            'currency_amount' => ['sometimes', 'nullable', 'numeric', 'min:0.01'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
