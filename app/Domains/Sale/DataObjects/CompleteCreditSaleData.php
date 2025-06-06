<?php

declare(strict_types=1);

namespace App\Domains\Sale\DataObjects;

use App\Domains\Voucher\DataObjects\GenerateVoucherData;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class CompleteCreditSaleData extends Data
{
    public function __construct(
        public ?string $happened_at,
        public ?array $payments = [],
        public ?array $loyalty_points = [],
        #[DataCollectionOf(GenerateVoucherData::class)]
        public ?DataCollection $vouchers = null,
        public ?int $cashback_id = null,
        public ?float $cashback_amount = null,
        public ?float $cashback_round_off_amount = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'happened_at' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'payments' => ['required', 'array'],
            'payments.*.type_id' => ['required', 'integer'],
            'payments.*.amount' => ['required', 'numeric'],
            'payments.*.currency_id' => ['sometimes', 'nullable', 'integer'],
            'payments.*.current_currency_rate' => ['sometimes', 'nullable', 'numeric'],
            'payments.*.currency_amount' => ['sometimes', 'nullable', 'numeric'],
            'payments.*.booking_payment_id' => ['sometimes', 'integer'],
            'payments.*.credit_note_id' => ['sometimes', 'integer'],
            'payments.*.gift_card_id' => ['sometimes', 'integer'],
            'payments.*.loyalty_points' => ['sometimes', 'nullable', 'integer'],

            'loyalty_points' => ['sometimes', 'nullable', 'array'],
            'loyalty_points.*.loyalty_campaign_id' => ['required', 'integer'],
            'loyalty_points.*.minimum_spend_amount' => ['required', 'numeric', 'min:0.00'],
            'loyalty_points.*.points' => ['required', 'integer'],
            'loyalty_points.*.expired_at' => ['nullable', 'date', 'max:255', 'date_format:Y-m-d'],

            'vouchers' => ['sometimes', 'nullable', 'array'],
            'vouchers.*.voucher_configuration_id' => ['required', 'integer'],
            'vouchers.*.discount_type' => ['required', 'integer'],
            'vouchers.*.number' => ['required', 'string'],
            'vouchers.*.minimum_spend_amount' => ['required', 'numeric', 'min:0.01'],
            'vouchers.*.percentage' => ['sometimes', 'nullable', 'numeric', 'between:0.01,100'],
            'vouchers.*.flat_amount' => ['sometimes', 'nullable', 'numeric', 'between:0.01,99999999.99'],
            'vouchers.*.expired_at' => ['sometimes', 'nullable', 'date', 'max:255', 'date_format:Y-m-d'],

            'cashback_id' => ['sometimes', 'nullable', 'integer'],
            'cashback_amount' => ['sometimes', 'nullable', 'numeric'],
            'cashback_round_off_amount' => ['sometimes', 'nullable', 'numeric'],
        ];
    }
}
