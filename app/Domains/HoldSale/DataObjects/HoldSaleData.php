<?php

declare(strict_types=1);

namespace App\Domains\HoldSale\DataObjects;

use App\Domains\HoldSale\Enums\HoldSaleTypes;
use Spatie\LaravelData\Data;

class HoldSaleData extends Data
{
    public function __construct(
        public string $offline_id,
        public string $happened_at,
        public int $type_id,
        public ?float $total_amount_paid,
        public ?float $items_discount_amount,
        public ?float $total_discount_amount,
        public ?float $round_off,
        public ?string $notes,
        public ?string $released_at = null,
        public ?string $cancelled_at = null,
        public ?string $complete_at = null,
        public ?string $complete_offline_id = null,
        public ?int $member_id = null,
        public ?int $employee_id = null,
        public ?array $items = [],
        public ?array $return_items = [],
        public ?string $bill_reference_number = null,
        public ?float $change_due = null,
        public ?float $total_tax_amount = null,
        public ?float $cart_discount_amount = null,
        public ?bool $is_layaway = false,
        public ?float $layaway_pending_amount = null,
        public ?bool $is_credit_sale = false,
        public ?float $credit_pending_amount = null,
        public ?int $store_manager_id = null,
        public ?string $store_manager_passcode = null,
        public ?string $reason = null,
        public ?string $store_manager_authorization_code = null,
    ) {
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function rules(): array
    {
        return [
            'offline_id' => ['required', 'string'],
            'happened_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'type_id' => ['required', 'integer', 'in:' . HoldSaleTypes::getValues()],
            'cancelled_at' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'complete_offline_id' => ['sometimes', 'nullable', 'string'],
            'complete_at' => ['sometimes', 'date_format:Y-m-d H:i:s'],
            'released_at' => ['sometimes', 'date_format:Y-m-d H:i:s'],

            'member_id' => ['sometimes', 'nullable', 'exists:members,id'],
            'employee_id' => ['sometimes', 'nullable', 'exists:employees,id'],

            'is_layaway' => ['sometimes', 'boolean'],
            'layaway_pending_amount' => ['sometimes', 'nullable', 'numeric'],

            'is_credit_sale' => ['sometimes', 'boolean'],
            'credit_pending_amount' => ['sometimes', 'nullable', 'numeric'],

            'items' => ['sometimes', 'nullable', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.derivative_id' => ['sometimes', 'nullable', 'integer'],
            'items.*.open_price' => ['sometimes', 'numeric', 'min:0'],
            'items.*.price' => ['sometimes', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.is_exchange' => ['sometimes', 'boolean'],
            'items.*.group_id' => ['sometimes', 'nullable', 'integer'],
            'items.*.item_discount_amount' => ['sometimes', 'nullable', 'numeric'],
            'items.*.cart_discount_amount' => ['sometimes', 'nullable', 'numeric'],
            'items.*.total_discount_amount' => ['sometimes', 'nullable', 'numeric'],
            'items.*.total_tax_amount' => ['sometimes', 'nullable', 'numeric'],
            'items.*.original_price_per_unit' => ['sometimes', 'nullable', 'numeric'],
            'items.*.price_paid_per_unit' => ['sometimes', 'nullable', 'numeric'],
            'items.*.total_price_paid' => ['sometimes', 'nullable', 'numeric'],

            'return_items' => ['sometimes', 'nullable', 'array'],
            'return_items.*.sale_item_id' => ['required', 'integer'],
            'return_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'return_items.*.sale_return_details' => ['required', 'array'],
            'return_items.*.sale_return_details.*.quantity' => ['required', 'numeric'],
            'return_items.*.sale_return_details.*.sale_return_reason_id' => ['required', 'integer'],
            'return_items.*.sale_return_details.*.total_price_paid' => ['sometimes', 'nullable', 'numeric'],
            'return_items.*.sale_return_details.*.cart_discount_amount' => ['sometimes', 'nullable', 'numeric'],
            'return_items.*.sale_return_details.*.item_discount_amount' => ['sometimes', 'nullable', 'numeric'],
            'return_items.*.sale_return_details.*.total_discount_amount' => ['sometimes', 'nullable', 'numeric'],
            'return_items.*.sale_return_details.*.total_tax_amount' => ['sometimes', 'nullable', 'numeric'],
            'bill_reference_number' => ['sometimes', 'nullable', 'string'],
            'change_due' => ['sometimes', 'nullable', 'numeric'],
            'round_off' => ['sometimes', 'nullable', 'numeric'],
            'total_tax_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'cart_discount_amount' => ['sometimes', 'nullable', 'numeric'],
            'items_discount_amount' => ['sometimes', 'nullable', 'numeric'],
            'total_discount_amount' => ['sometimes', 'nullable', 'numeric'],
            'total_amount_paid' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'store_manager_id' => ['sometimes', 'nullable', 'integer'],
            'store_manager_passcode' => ['sometimes', 'nullable', 'string'],
            'store_manager_authorization_code' => ['sometimes', 'nullable', 'string'],
            'reason' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
