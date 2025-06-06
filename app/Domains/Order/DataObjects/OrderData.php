<?php

declare(strict_types=1);

namespace App\Domains\Order\DataObjects;

use App\Domains\Member\Enums\Types;
use App\Domains\Order\Enums\OrderChannels;
use App\Domains\Order\Enums\OrderTypes;
use App\Rules\MobileNumber;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class OrderData extends Data
{
    public function __construct(
        public int $order_type,
        public int $channel_type,
        public ?int $member_id = null,
        public ?array $order_items = [],
        public ?array $payments = [],
        public ?array $return_items = [],
        public ?string $notes = null,
        public ?string $bill_reference_number = null,
        public ?float $order_round_off_amount = null,
        public ?float $order_return_round_off_amount = null,
        public ?float $total_tax_amount = null,
        public ?float $cart_discount_amount = null,
        public ?array $member_details = [],
        public ?int $location_id = null,
        public ?float $cart_price_override_amount = null,
        public ?float $cart_price_override_percentage = null,
        public bool $is_layaway = false,
        public ?float $layaway_pending_amount = null,
        public bool $is_credit = false,
        public ?float $credit_pending_amount = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'member_id' => ['sometimes', 'nullable', 'exists:members,id'],
            'order_type' => ['required', Rule::in(OrderTypes::getArrayValues())],
            'channel_type' => ['required', Rule::in(OrderChannels::getArrayValues())],

            'is_credit' => ['sometimes', 'boolean'],
            'credit_pending_amount' => ['sometimes', 'nullable', 'numeric'],

            'is_layaway' => ['sometimes', 'boolean'],
            'layaway_pending_amount' => ['sometimes', 'nullable', 'numeric'],

            'order_items' => ['sometimes', 'nullable', 'array'],
            'order_items.*.id' => ['required', 'integer'],
            'order_items.*.open_price' => ['sometimes', 'numeric', 'min:0'],
            'order_items.*.price' => ['sometimes', 'numeric', 'min:0'],
            'order_items.*.quantity' => ['required', 'numeric', 'min:0.01'],

            'order_items.*.item_discount_amount' => ['sometimes', 'nullable', 'numeric'],
            'order_items.*.complimentary_item_reason_id' => ['sometimes', 'nullable', 'integer'],
            'order_items.*.complimentary_item_discount' => ['sometimes', 'nullable', 'numeric', 'min:0.01'],

            'order_items.*.promoter_ids' => ['sometimes', 'nullable', 'array'],
            'order_items.*.promoter_ids.*' => ['required', 'integer'],

            'order_items.*.price_override_amount' => ['sometimes', 'nullable', 'numeric', 'min:0.0'],
            'order_items.*.price_override_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0.0'],
            'order_items.*.total_price_paid' => ['sometimes', 'nullable', 'numeric'],

            'order_items.*.batch_details' => ['sometimes', 'array'],
            'order_items.*.batch_details.*.quantity' => ['sometimes', 'numeric'],
            'order_items.*.batch_details.*.batch_number' => ['sometimes', 'nullable', 'string'],
            'order_items.*.batch_details.*.batch_expiry_date' => [
                'sometimes',
                'nullable',
                'date',
                'max:255',
                'date_format:Y-m-d',
            ],

            'payments' => ['sometimes', 'nullable', 'array'],
            'payments.*.type_id' => ['required', 'integer'],
            'payments.*.credit_note_id' => ['sometimes', 'nullable', 'integer'],
            'payments.*.amount' => ['required', 'numeric'],
            'payments.*.notes' => ['sometimes', 'nullable', 'string'],

            'notes' => ['sometimes', 'nullable', 'string'],
            'bill_reference_number' => ['sometimes', 'nullable', 'string'],
            'order_round_off_amount' => ['sometimes', 'nullable', 'numeric'],
            'order_return_round_off_amount' => ['sometimes', 'nullable', 'numeric'],
            'total_tax_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'cart_discount_amount' => ['sometimes', 'nullable', 'numeric'],
            'cart_price_override_amount' => ['sometimes', 'nullable', 'numeric'],
            'cart_price_override_percentage' => ['sometimes', 'nullable', 'numeric'],

            'member_details' => ['required', 'array'],
            'member_details.*.type_id' => ['sometimes', 'integer', 'in:' . Types::getValues()],
            'member_details.*.name' => ['sometimes', 'string', 'max:255'],
            'member_details.*.mobile_number' => ['sometimes', 'string', 'max:255', new MobileNumber()],
            'member_details.*.card_number' => ['sometimes', 'string'],
        ];
    }
}
