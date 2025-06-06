<?php

declare(strict_types=1);

namespace App\Domains\Voucher\DataObjects;

use Spatie\LaravelData\Data;

class BirthdayVoucherData extends Data
{
    public function __construct(
        public int $voucher_configuration_id,
        public int $discount_type,
        public string $number,
        public float $minimum_spend_amount,
        public ?string $expired_at,
        public string $happened_at,
        public ?float $percentage = null,
        public ?float $flat_amount = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'voucher_configuration_id' => ['required', 'integer'],
            'discount_type' => ['required', 'integer'],
            'number' => ['required', 'string'],
            'minimum_spend_amount' => ['required', 'numeric', 'min:0.01'],
            'percentage' => ['sometimes', 'nullable', 'numeric', 'between:0.01,100'],
            'flat_amount' => ['sometimes', 'nullable', 'numeric', 'between:0.01,99999999.99'],
            'expired_at' => ['sometimes', 'nullable', 'date', 'max:255', 'date_format:Y-m-d'],
            'happened_at' => ['required', 'date_format:Y-m-d H:i:s'],
        ];
    }
}
