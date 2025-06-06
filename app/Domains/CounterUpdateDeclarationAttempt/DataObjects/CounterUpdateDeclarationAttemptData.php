<?php

declare(strict_types=1);

namespace App\Domains\CounterUpdateDeclarationAttempt\DataObjects;

use App\Domains\PaymentType\Enums\StaticPaymentTypes;
use Spatie\LaravelData\Data;

class CounterUpdateDeclarationAttemptData extends Data
{
    public function __construct(
        public string $offline_id,
        public string $happened_at,
        public array $payments,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'offline_id' => ['required', 'string', 'unique:counter_update_declaration_attempts,offline_id'],
            'happened_at' => ['required', 'date_format:Y-m-d H:i:s'],

            'payments' => ['required', 'array'],
            'payments.*.payment_type_id' => ['required', 'integer'],
            'payments.*.declared_amount' => ['required', 'numeric'],
            'payments.*.calculated_amount' => ['required', 'numeric'],
            'payments.*.denominations' => [
                'nullable',
                'required_if:payments.*.payment_type_id,' . StaticPaymentTypes::CASH->value,
                'array',
            ],
            'payments.*.denominations.*.denomination' => ['required', 'numeric', 'min:0.01'],
            'payments.*.denominations.*.quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
