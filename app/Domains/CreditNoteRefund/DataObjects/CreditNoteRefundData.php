<?php

declare(strict_types=1);

namespace App\Domains\CreditNoteRefund\DataObjects;

use Spatie\LaravelData\Data;

class CreditNoteRefundData extends Data
{
    public function __construct(
        public int $payment_type_id,
        public float $amount,
        public ?int $currency_id,
        public ?float $current_currency_rate,
        public ?float $currency_amount,
        public int $store_manager_id,
        public string $passcode,
        public ?string $store_manager_authorization_code = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'payment_type_id' => ['required', 'integer', 'exists:payment_types,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency_id' => ['sometimes', 'nullable', 'exists:currencies,id'],
            'current_currency_rate' => ['sometimes', 'nullable', 'numeric'],
            'currency_amount' => ['sometimes', 'nullable', 'numeric', 'min:0.01'],
            'store_manager_id' => ['required', 'integer', 'exists:store_managers,id'],
            'passcode' => ['required', 'string', 'exists:store_managers,passcode'],
            'store_manager_authorization_code' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
